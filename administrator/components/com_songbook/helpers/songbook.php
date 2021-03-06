<?php
/**
 * @package Song Book
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die('Restricted access'); // No direct access to this file.


class SongbookHelper
{
  //Create the tabs bar ($viewName = name of the active view).
  public static function addSubmenu($viewName)
  {
    JHtmlSidebar::addEntry(JText::_('COM_SONGBOOK_SUBMENU_SONGS'),
				      'index.php?option=com_songbook&view=songs', $viewName == 'songs');

    JHtmlSidebar::addEntry(JText::_('COM_SONGBOOK_SUBMENU_CATEGORIES'),
				      'index.php?option=com_categories&extension=com_songbook', $viewName == 'categories');

    if($viewName == 'categories') {
      $document = JFactory::getDocument();
      $document->setTitle(JText::_('COM_SONGBOOK_ADMINISTRATION_CATEGORIES'));
    }
  }


  //Get the list of the allowed actions for the user.
  public static function getActions($catIds = array())
  {
    $user = JFactory::getUser();
    $result = new JObject;

    $actions = array('core.admin', 'core.manage', 'core.create', 'core.edit',
		     'core.edit.own', 'core.edit.state', 'core.delete');

    //Get from the core the user's permission for each action.
    foreach($actions as $action) {
      //Check permissions against the component. 
      if(empty($catIds)) { 
	$result->set($action, $user->authorise($action, 'com_songbook'));
      }
      else {
	//Check permissions against the component categories.
	foreach($catIds as $catId) {
	  if($user->authorise($action, 'com_songbook.category.'.$catId)) {
	    $result->set($action, $user->authorise($action, 'com_songbook.category.'.$catId));
	    break;
	  }

	  $result->set($action, $user->authorise($action, 'com_songbook.category.'.$catId));
	}
      }
    }

    return $result;
  }


  //Build the user list for the filter.
  public static function getUsers($itemName)
  {
    // Create a new query object.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('u.id AS value, u.name AS text');
    $query->from('#__users AS u');
    //Get only the names of users who have created items, this avoids to
    //display all of the users in the drop down list.
    $query->join('INNER', '#__songbook_'.$itemName.' AS i ON i.created_by = u.id');
    $query->group('u.id');
    $query->order('u.name');

    // Setup the query
    $db->setQuery($query);

    // Return the result
    return $db->loadObjectList();
  }


  public static function checkSelectedFilter($filterName, $unique = false)
  {
    $post = JFactory::getApplication()->input->post->getArray();

    //Ensure the given filter has been selected.
    if(isset($post['filter'][$filterName]) && !empty($post['filter'][$filterName])) {
      //Ensure that only the given filter has been selected.
      if($unique) {
	$filter = 0;
	foreach($post['filter'] as $value) {
	  if(!empty($value)) {
	    $filter++;
	  }
	}

	if($filter > 1) {
	  return false;
	}
      }

      return true;
    }

    return false;
  }


  public static function mappingTableOrder($pks, $tagId, $limitStart)
  {
    //Check first the user can edit state.
    $user = JFactory::getUser();
    if(!$user->authorise('core.edit.state', 'com_songbook')) {
      return false;
    }

    //Start ordering from 1 by default.
    $ordering = 1;

    //When pagination is used set ordering from limitstart value.
    if($limitStart) {
      $ordering = (int)$limitStart + 1;
    }

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Update the ordering values of the mapping table. 
    foreach($pks as $pk) {
      $query->clear();
      $query->update('#__songbook_song_tag_map')
	    //Update the item ordering via the mapping table.
	    ->set('ordering='.$ordering)
	    ->where('song_id='.(int)$pk)
	    ->where('tag_id='.(int)$tagId);
      $db->setQuery($query);
      $db->query();

      $ordering++;
    }

    return true;
  }


  public static function removeTagsOnTheFly(&$newTags)
  {
    foreach($newTags as $key => $tagId) {
      //Check for newly created tags (ie: id=#new#Title of the tag)
      if(substr($tagId, 0, 5) == '#new#') {
	//Remove the new tag from the tag data.
	unset($newTags[$key]);
      }
    }

    //Don't return an empty array. Return null instead.
    if(empty($newTags)) {
      $newTags = null;
    }

    return;
  }


  public static function checkMainTags($pks)
  {

    $ids = array();
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    foreach($pks as $pk) {
      // Find node and all children keys
      $query->clear();
      $query->select('c.id')
	    ->from('#__tags AS node')
	    ->leftJoin('#__tags AS c ON node.lft <= c.lft AND c.rgt <= node.rgt')
	    ->where('node.id = '.(int)$pk);
      $db->setQuery($query);
      $results = $db->loadColumn();

      $ids = array_unique(array_merge($ids,$results), SORT_REGULAR);
    }

    //Checks that no song item is using one of the tags as main tag.
    $query->clear();
    $query->select('COUNT(*)')
	  ->from('#__songbook_song')
	  ->where('main_tag_id IN('.implode(',', $ids).')');
    $db->setQuery($query);

    if($db->loadResult()) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_SONGBOOK_WARNING_TAG_USED_AS_MAIN_TAG'), 'warning');
      return false;
    }

    return true;
  }
}


