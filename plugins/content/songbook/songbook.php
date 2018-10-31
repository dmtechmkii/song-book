<?php
/**
 * @package Song Book
 * @copyright Copyright (c)2016 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access
defined('_JEXEC') or die('Restricted access');
// Import the JPlugin class
jimport('joomla.plugin.plugin');
require_once JPATH_ROOT.'/administrator/components/com_songbook/helpers/songbook.php';


class plgContentSongbook extends JPlugin
{
  /**
   * Constructor.
   *
   * @param   object  &$subject  The object to observe
   * @param   array   $config    An optional associative array of configuration settings.
   *
   * @since   3.7.0
   */
  public function __construct(&$subject, $config)
  {
    //Loads the component language.
    $lang = JFactory::getLanguage();
    $langTag = $lang->getTag();
    $lang->load('com_songbook', JPATH_ROOT.'/administrator/components/com_songbook', $langTag);

    parent::__construct($subject, $config);
  }


  public function onContentBeforeSave($context, $data, $isNew)
  {
    //Removes tags created on the fly from any component.
    if(!$this->params->get('tags_on_the_fly', 0)) {
      //Check we have tags before treating data.
      if(isset($data->newTags)) {
	SongbookHelper::removeTagsOnTheFly($data->newTags);
      }
    }

    return true;
  }


  public function onContentBeforeDelete($context, $data)
  {
    if($context == 'com_tags.tag') {
      //Ensures that the deleted tag is not used as main tag by one or more songs.
      if(!SongbookHelper::checkMainTags(array($data->id))) {
	return false;
      }
      else {
	$db = JFactory::getDbo();
	$query = $db->getQuery(true);

	//Delete all the rows linked to the tag id. 
	$query->delete('#__songbook_song_tag_map')
	      ->where('tag_id='.(int)$data->id);
	$db->setQuery($query);
	$db->query();
      }
    }

    return true;
  }


  //Since the id of a new item is not known before being saved, the code which
  //links item ids to other item ids should be placed here.
  public function onContentAfterSave($context, $data, $isNew)
  {
    //Filter the sent event.

    if($context == 'com_songbook.song' || $context == 'com_songbook.form') { 
      //Check for song order.
      $this->setOrderByTag($context, $data, $isNew);
    }
    else { //Hand over to Joomla.
      return;
    }
  }


  public function onContentAfterDelete($context, $data)
  {
    //Filter the sent event.

    if($context == 'com_songbook.song') {
      // Create a new query object.
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      //Delete all the rows linked to the item id. 
      $query->delete('#__songbook_song_tag_map')
	    ->where('song_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      return;
    }
    elseif($context == 'com_tags.tag') {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      //Delete all the rows linked to the item id. 
      $query->delete('#__songbook_song_tag_map')
	    ->where('tag_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      return;
    }
    else { //Hand over to Joomla.
      return;
    }
  }


  public function onContentChangeState($context, $pks, $value)
  {
    //Filter the sent event.

    if($context == 'com_songbook.song') {
      return true;
    }
    else { //Hand over to Joomla.
      return true;
    }
  }


  /**
   * Create (or update) a row whenever an song is tagged.
   * The song/tag mapping allows to order the song against a given tag. 
   *
   * @param   string   $context  The context of the content passed to the plugin (added in 1.6)
   * @param   object   $data     A JTableContent object
   * @param   boolean  $isNew    If the content is just about to be created
   *
   * @return  void
   *
   */
  private function setOrderByTag($context, $data, $isNew)
  {
    // Create a new query object.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Check we have tags before treating data.
    if(isset($data->newTags)) {
      //Retrieve all the rows matching the item id.
      $query->select('m.song_id, m.tag_id, IFNULL(m.ordering, "NULL") AS ordering')
	    ->from('#__songbook_song_tag_map AS m')
	    //Inner in case meanwhile a tag has been deleted.
	    ->join('INNER', '#__tags AS t ON t.id=m.tag_id')
	    ->where('m.song_id='.(int)$data->id);
      $db->setQuery($query);
      $tags = $db->loadObjectList();
      $values = array();

      foreach($data->newTags as $tagId) {
	$newTag = true; 
	//In order to preserve the ordering of the old tags we check if 
	//they match those newly selected.
	foreach($tags as $tag) {
	  if($tag->tag_id == $tagId) {
	    $values[] = $tag->song_id.','.$tag->tag_id.','.$tag->ordering;
	    $newTag = false; 
	    break;
	  }
	}

	if($newTag) {
	  $values[] = $data->id.','.$tagId.',NULL';
	}
      }

      //Delete all the rows matching the item id.
      $query->clear();
      $query->delete('#__songbook_song_tag_map')
	    ->where('song_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();

      $columns = array('song_id', 'tag_id', 'ordering');
      //Insert a new row for each tag linked to the item.
      $query->clear();
      $query->insert('#__songbook_song_tag_map')
	    ->columns($columns)
	    ->values($values);
      $db->setQuery($query);
      $db->query();
    }
    else { //No tags selected or tags removed.
      //Delete all the rows matching the item id.
      $query->delete('#__songbook_song_tag_map')
	    ->where('song_id='.(int)$data->id);
      $db->setQuery($query);
      $db->query();
    }

    return;
  }
}

