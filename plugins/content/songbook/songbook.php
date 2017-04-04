<?php
/**
 * @package SongBook
 * @copyright Copyright (c)2016 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access
defined('_JEXEC') or die('Restricted access');
// Import the JPlugin class
jimport('joomla.plugin.plugin');


class plgContentSongbook extends JPlugin
{

  public function onContentBeforeSave($context, $data, $isNew)
  {
    return true;
  }


  public function onContentBeforeDelete($context, $data)
  {
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
    //Get the jform data.
    $jform = JFactory::getApplication()->input->post->get('jform', array(), 'array');

    // Create a new query object.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Check we have tags before treating data.
    if(isset($jform['tags'])) {
      //Retrieve all the rows matching the item id.
      $query->select('song_id, tag_id, IFNULL(ordering, "NULL") AS ordering')
	    ->from('#__songbook_song_tag_map')
	    ->where('song_id='.(int)$data->id);
      $db->setQuery($query);
      $tags = $db->loadObjectList();

      $values = array();
      foreach($jform['tags'] as $tagId) {
	//Check for newly created tags (ie: id=#new#Title of the tag)
	if(substr($tagId, 0, 5) == '#new#') {
	  //Get the title tag then turn it into alias.
	  $title = substr($tagId, 5);
	  $alias = JFilterOutput::stringURLSafe($title);
	  //Get the id of the new tag from its alias.
	  $query->clear();
	  $query->select('id')
		->from('#__tags')
		->where('alias='.$db->Quote($alias));
	  $db->setQuery($query);
	  $tagId = $db->loadResult();
	  //Skip the tag in case of error.
	  if(is_null($tagId)) {
	    continue;
	  }
	}

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

