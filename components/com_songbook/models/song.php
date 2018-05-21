<?php
/**
 * @package Song Book
 * @copyright Copyright (c) 2016 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.


class SongbookModelSong extends JModelItem
{

  protected $_context = 'com_songbook.song';

  /**
   * Method to auto-populate the model state.
   *
   * Note. Calling getState in this method will result in recursion.
   *
   * @since   1.6
   *
   * @return void
   */
  protected function populateState()
  {
    $app = JFactory::getApplication('site');

    // Load state from the request.
    $pk = $app->input->getInt('id');
    $this->setState('song.id', $pk);

    //Load the global parameters of the component.
    $params = $app->getParams();
    $this->setState('params', $params);

    $this->setState('filter.language', JLanguageMultilang::isEnabled());
  }


  //Returns a Table object, always creating it.
  public function getTable($type = 'Song', $prefix = 'SongbookTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  /**
   * Method to get a single record.
   *
   * @param   integer  $pk  The id of the primary key.
   *
   * @return  mixed    Object on success, false on failure.
   *
   * @since   12.2
   */
  public function getItem($pk = null)
  {
    $pk = (!empty($pk)) ? $pk : (int)$this->getState('song.id');
    $user = JFactory::getUser();

    if($this->_item === null) {
      $this->_item = array();
    }

    if(!isset($this->_item[$pk])) {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      $query->select($this->getState('list.select', 's.id,s.title,s.alias,s.intro_text,s.full_text,s.main_tag_id,s.catid,s.published,'.
				     's.checked_out,s.checked_out_time,s.created,s.created_by,s.access,s.params,s.metadata,'.
				     's.metakey,s.metadesc,s.hits,s.publish_up,s.publish_down,s.language,s.modified,s.modified_by'))
	    ->from($db->quoteName('#__songbook_song').' AS s')
	    ->where('s.id='.$pk);

      // Join over the tags to get the main tag title.
      $query->select('main_tag.title AS main_tag_title, main_tag.path AS main_tag_route,'.
		     'main_tag.alias AS main_tag_alias')
	    ->join('LEFT', '#__tags AS main_tag ON main_tag.id = s.main_tag_id');

      // Join on category table.
      $query->select('ca.title AS category_title, ca.alias AS category_alias, ca.access AS category_access')
	    ->join('LEFT', '#__categories AS ca on ca.id = s.catid');

      // Join on user table.
      $query->select('us.name AS author')
	    ->join('LEFT', '#__users AS us on us.id = s.created_by');

      // Join over the categories to get parent category titles
      $query->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias')
	    ->join('LEFT', '#__categories as parent ON parent.id = ca.parent_id');

      // Filter by language
      if($this->getState('filter.language')) {
	$query->where('s.language in ('.$db->quote(JFactory::getLanguage()->getTag()).','.$db->quote('*').')');
      }

      if((!$user->authorise('core.edit.state', 'com_songbook')) && (!$user->authorise('core.edit', 'com_songbook'))) {
	// Filter by start and end dates.
	$nullDate = $db->quote($db->getNullDate());
	$nowDate = $db->quote(JFactory::getDate()->toSql());
	$query->where('(s.publish_up = '.$nullDate.' OR s.publish_up <= '.$nowDate.')')
	      ->where('(s.publish_down = '.$nullDate.' OR s.publish_down >= '.$nowDate.')');
      }

      $db->setQuery($query);
      $data = $db->loadObject();

      if(is_null($data)) {
	JFactory::getApplication()->enqueueMessage(JText::_('COM_SONGBOOK_ERROR_SONG_NOT_FOUND'), 'error');
	return false;
      }

      // Convert parameter fields to objects.
      $registry = new JRegistry;
      $registry->loadString($data->params);

      $data->params = clone $this->getState('params');
      $data->params->merge($registry);

      $user = JFactory::getUser();
      // Technically guest could edit an article, but lets not check that to improve performance a little.
      if(!$user->get('guest')) {
	$userId = $user->get('id');
	$asset = 'com_songbook.song.'.$data->id;

	// Check general edit permission first.
	if($user->authorise('core.edit', $asset)) {
	  $data->params->set('access-edit', true);
	}

	// Now check if edit.own is available.
	elseif(!empty($userId) && $user->authorise('core.edit.own', $asset)) {
	  // Check for a valid user and that they are the owner.
	  if($userId == $data->created_by) {
	    $data->params->set('access-edit', true);
	  }
	}
      }

      // Get the tags
      $data->tags = new JHelperTags;
      $data->tags->getItemTags('com_songbook.song', $data->id);

      $this->_item[$pk] = $data;
    }

    return $this->_item[$pk];
  }


  /**
   * Increment the hit counter for the song.
   *
   * @param   integer  $pk  Optional primary key of the song to increment.
   *
   * @return  boolean  True if successful; false otherwise and internal error set.
   */
  public function hit($pk = 0)
  {
    $input = JFactory::getApplication()->input;
    $hitcount = $input->getInt('hitcount', 1);

    if($hitcount) {
      $pk = (!empty($pk)) ? $pk : (int) $this->getState('song.id');

      $table = JTable::getInstance('Song', 'SongbookTable');
      $table->load($pk);
      $table->hit($pk);
    }

    return true;
  }
}

