<?php
/**
 * @package Song Book
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die('Restricted access');


/**
 * Song Book Component Model
 *
 * @package     Joomla.Site
 * @subpackage  com_songbook
 */
class SongbookModelTag extends JModelList
{
  /**
   * Method to get a list of items.
   *
   * @return  mixed  An array of objects on success, false on failure.
   */

  /**
   * Constructor.
   *
   * @param   array  An optional associative array of configuration settings.
   * @see     JController
   * @since   1.6
   */
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 's.id',
	      'title', 's.title',
	      'author', 's.author',
	      'created', 's.created',
	      'catid', 's.catid', 'category_title',
	      'modified', 's.modified',
	      'published', 's.published',
	      'tm.ordering',
	      'publish_up', 's.publish_up',
	      'publish_down', 's.publish_down',
      );
    }

    parent::__construct($config);
  }


  /**
   * Method to auto-populate the model state.
   *
   * Note. Calling getState in this method will result in recursion.
   *
   * @since   1.6
   */
  protected function populateState($ordering = null, $direction = null)
  {
    $app = JFactory::getApplication('site');

    //Get and set the current tag id.
    $pk = $app->input->getInt('id');
    $this->setState('tag.id', $pk);

    //getParams function return global parameters overrided by the menu parameters (if any).
    //Note: Some specific parameters of this menu are not returned.
    $params = $app->getParams();

    $menuParams = new JRegistry;

    //Get the menu with its specific parameters.
    if($menu = $app->getMenu()->getActive()) {
      $menuParams->loadString($menu->params);
    }

    //Merge Global and Menu Item params into a new object.
    $mergedParams = clone $menuParams;
    $mergedParams->merge($params);

    // Load the parameters in the session.
    $this->setState('params', $mergedParams);

    // process show_noauth parameter

    //The user is not allowed to see the registered songs unless he has the proper view permissions.
    if(!$params->get('show_noauth')) {
      //Set the access filter to true. This way the SQL query checks against the user
      //view permissions and fetchs only the songs this user is allowed to see.
      $this->setState('filter.access', true);
    }
    //The user is allowed to see any of the registred songs (ie: intro_text as a teaser). 
    else {
      //The user is allowed to see all the songs or some of them.
      //All of the songs are returned and it's up to the layout to 
      //deal with the access (ie: redirect the user to login form when Read more
      //button is clicked).
      $this->setState('filter.access', false);
    }

    // Set limit for query. If list, use parameter. If blog, add blog parameters for limit.
    //Important: The pagination limit box must be hidden to use the limit value based upon the layout.
    if(!$params->get('show_pagination_limit') && (($app->input->get('layout') === 'blog') || $params->get('layout_type') === 'blog')) {
      $limit = $params->get('num_leading_songs') + $params->get('num_intro_songs') + $params->get('num_links');
    }
    else { // list layout or blog layout with the pagination limit box shown.
      //Get the number of songs to display per page.
      $limit = $params->get('display_num', 10);

      if($params->get('show_pagination_limit')) {
	//Gets the limit value from the pagination limit box.
	$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $limit, 'uint');
      }
    }

    $this->setState('list.limit', $limit);

    //Get the limitstart variable (used for the pagination) from the form variable.
    $limitstart = $app->input->get('limitstart', 0, 'uint');
    $this->setState('list.start', $limitstart);

    // Optional filter text
    $filterSearch = $this->getUserStateFromRequest($this->context.'.list.filter_search', 'filter_search');
    $this->setState('list.filter_search', $filterSearch);
    // Filter.order
    $filterOrdering = $this->getUserStateFromRequest($this->context.'.list.filter_ordering', 'filter_ordering');
    $this->setState('list.filter_ordering', $filterOrdering);

    //Check if the user is root. 
    $user = JFactory::getUser();
    if(!$user->get('isRoot')) {
      // Limit to published for people who are not super user.
      $this->setState('filter.published', 1);

      // Filter by start and end dates.
      $this->setState('filter.publish_date', true);
    }
    else {
      //Super users can access published, unpublished and archived songs.
      $this->setState('filter.published', array(0, 1, 2));
    }

    $this->setState('filter.language', JLanguageMultilang::isEnabled());
  }


  /**
   * Method to get a list of items.
   *
   * @return  mixed  An array of objects on success, false on failure.
   */
  public function getItems()
  {
    // Invoke the parent getItems method (using the getListQuery method) to get the main list
    $items = parent::getItems();

    if(!$items) {
      return array();
    }

    $input = JFactory::getApplication()->input;

    //Get some user data.
    $user = JFactory::getUser();
    $userId = $user->get('id');
    $guest = $user->get('guest');
    $groups = $user->getAuthorisedViewLevels();

    // Convert the params field into an object, saving original in _params
    foreach($items as $key => $item) {
      //Get the song parameters only.
      $songParams = new JRegistry;
      $songParams->loadString($item->params);
      //Set the params attribute, eg: the merged global and menu parameters set
      //in the populateState function.
      $item->params = clone $this->getState('params');

      // For Blog layout, song params override menu item params only if menu param='use_song'.
      // Otherwise, menu item params control the layout.
      // If menu item is 'use_song' and there is no song param, use global.
      if($input->getString('layout') == 'blog' || $this->getState('params')->get('layout_type') == 'blog') {
	// Create an array of just the params set to 'use_song'
	$menuParamsArray = $this->getState('params')->toArray();
	$songArray = array();

	foreach($menuParamsArray as $key => $value) {
	  if($value === 'use_song') {
	    // If the song has a value, use it
	    if($songParams->get($key) != '') {
	      // Get the value from the song
	      $songArray[$key] = $songParams->get($key);
	    }
	    else {
	      // Otherwise, use the global value
	      $songArray[$key] = $globalParams->get($key);
	    }
	  }
	}

	// Merge the selected song params
	if(count($songArray) > 0) {
	  $songParams = new JRegistry;
	  $songParams->loadArray($songArray);
	  $item->params->merge($songParams);
	}
      }
      else { //Default layout (list).
	// Merge all of the song params.
	//Note: Song params (if they are defined) override global/menu params.
	$item->params->merge($songParams);
      }

      // Compute the asset access permissions.
      // Technically guest could edit a song, but lets not check that to improve performance a little.
      if(!$guest) {
	$asset = 'com_songbook.song.'.$item->id;

	// Check general edit permission first.
	if($user->authorise('core.edit', $asset)) {
	  $item->params->set('access-edit', true);
	}
	// Now check if edit.own is available.
	elseif(!empty($userId) && $user->authorise('core.edit.own', $asset)) {
	  // Check for a valid user and that they are the owner.
	  if($userId == $item->created_by) {
	    $item->params->set('access-edit', true);
	  }
	}
      }

      $access = $this->getState('filter.access');
      //Set the access view parameter.
      if($access) {
	// If the access filter has been set, we already have only the songs this user can view.
	$item->params->set('access-view', true);
      }
      else { // If no access filter is set, the layout takes some responsibility for display of limited information.
	if($item->catid == 0 || $item->category_access === null) {
	  //In case the song is not linked to a category, we just check permissions against the song access.
	  $item->params->set('access-view', in_array($item->access, $groups));
	}
	else { //Check the user permissions against the song access as well as the category access.
	  $item->params->set('access-view', in_array($item->access, $groups) && in_array($item->category_access, $groups));
	}
      }

      //Set the type of date to display, (default layout only).
      if($this->getState('params')->get('layout_type') != 'blog'
	  && $this->getState('params')->get('list_show_date')
	  && $this->getState('params')->get('order_date')) {
	switch($this->getState('params')->get('order_date')) {
	  case 'modified':
		  $item->displayDate = $item->modified;
		  break;

	  case 'published':
		  $item->displayDate = ($item->publish_up == 0) ? $item->created : $item->publish_up;
		  break;

	  default: //created
		  $item->displayDate = $item->created;
	}
      }

      // Get the tags
      $item->tags = new JHelperTags;
      $item->tags->getItemTags('com_songbook.song', $item->id);
    }

    return $items;
  }


  /**
   * Method to build an SQL query to load the list data (song items).
   *
   * @return  string    An SQL query
   * @since   1.6
   */
  protected function getListQuery()
  {
    $user = JFactory::getUser();
    $groups = implode(',', $user->getAuthorisedViewLevels());

    // Create a new query object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // Select required fields from the categories.
    $query->select($this->getState('list.select', 's.id,s.title,s.alias,s.intro_text,s.full_text,s.catid,'.
	                           'tm.tag_id,s.published,s.checked_out,s.checked_out_time,s.created,'.
				   's.created_by,s.access,s.params,s.metadata,s.metakey,s.metadesc,s.hits,'.
				   's.main_tag_id,s.publish_up,s.publish_down,s.language,s.modified,s.modified_by'))
	  ->from($db->quoteName('#__songbook_song').' AS s')
	  ->join('LEFT', '#__songbook_song_tag_map AS tm ON s.id=tm.song_id')
	  //Display songs labeled with the current tag.
	  ->where('tm.tag_id='.(int)$this->getState('tag.id'));

    // Join on tag table.
    $query->select('ta.title AS tag_title, ta.alias AS tag_alias')
	  ->join('LEFT', '#__tags AS ta ON ta.id='.(int)$this->getState('tag.id'))
	  //Ensure the current tag is published.
	  ->where('ta.published=1');

    // Join over the tags to get the parent tag title.
    $query->select('tag_parent.title AS tag_parent_title, tag_parent.id AS tag_parent_id,'.
		   'tag_parent.path AS tag_parent_route, tag_parent.alias AS tag_parent_alias')
	  ->join('LEFT', '#__tags AS tag_parent ON tag_parent.id = ta.parent_id');

    // Join over the tags to get the main tag title.
    $query->select('main_tag.title AS main_tag_title, main_tag.path AS main_tag_route,'.
		   'main_tag.alias AS main_tag_alias')
	  ->join('LEFT', '#__tags AS main_tag ON main_tag.id = s.main_tag_id');

    // Join on category table.
    $query->select('ca.title AS category_title, ca.alias AS category_alias, ca.access AS category_access')
	  ->join('LEFT', '#__categories AS ca ON ca.id = s.catid')
	  //Ensure the category the song is in is published.
	  ->where('ca.published=1');

    // Join over the categories to get parent category title.
    $query->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias')
	  ->join('LEFT', '#__categories AS parent ON parent.id = ca.parent_id');

    // Join over the users.
    $query->select('us.name AS author')
	  ->join('LEFT', '#__users AS us ON us.id = s.created_by');

    // Join over the asset groups.
    $query->select('al.title AS access_level');
    $query->join('LEFT', '#__viewlevels AS al ON al.id = s.access');

    // Filter by access level.
    //Note: if($access = $this->getState('filter.access')) is a shorthand for:
    //      $access = $this->getState('filter.access')    
    //      if($access) { ...
    if($access = $this->getState('filter.access')) {
      $query->where('s.access IN ('.$groups.')')
	    //Category access is also taken in account.
	    ->where('ca.access IN ('.$groups.')');
    }

    // Filter by state
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      //Users are only allowed to see published songs.
      $query->where('s.published='.(int)$published);
    }
    elseif(is_array($published)) {
      //Only super users are allowed to see songs with different states.
      JArrayHelper::toInteger($published);
      $published = implode(',', $published);
      $query->where('s.published IN ('.$published.')');
    }

    //Do not show expired songs to users who are not Root.
    if($this->getState('filter.publish_date')) {
      // Filter by start and end dates.
      $nullDate = $db->quote($db->getNullDate());
      $nowDate = $db->quote(JFactory::getDate()->toSql());

      $query->where('(s.publish_up = '.$nullDate.' OR s.publish_up <= '.$nowDate.')')
	    ->where('(s.publish_down = '.$nullDate.' OR s.publish_down >= '.$nowDate.')');
    }

    // Filter by language
    if($this->getState('filter.language')) {
      $query->where('s.language IN ('.$db->quote(JFactory::getLanguage()->getTag()).','.$db->quote('*').')');
    }

    // Filter by search in title
    $filterSearch = $this->getState('list.filter_search');
    //Get the field to search by.
    $field = $this->getState('params')->get('filter_field');
    if(!empty($filterSearch)) {
      $filterSearch = $db->quote('%'.$db->escape($filterSearch, true).'%');
      $query->where('(s.'.$field.' LIKE '.$filterSearch.')');
    }

    //Get the songs ordering by default set in the menu options. (Note: sec stands for secondary). 
    $songOrderBy = $this->getState('params')->get('orderby_sec', 'rdate');
    //If songs are sorted by date (ie: date, rdate), order_date defines
    //which type of date should be used (ie: created, modified or publish_up).
    $songOrderDate = $this->getState('params')->get('order_date');
    //Get the field to use in the ORDER BY clause according to the orderby_sec option.
    $orderBy = SongbookHelperQuery::orderbySecondary($songOrderBy, $songOrderDate);

    //Filter by order (ie: the select list set by the end user).
    $filterOrdering = $this->getState('list.filter_ordering');
    //If the end user has define an order, we override the ordering by default.
    if(!empty($filterOrdering)) {
      $orderBy = SongbookHelperQuery::orderbySecondary($filterOrdering, $songOrderDate);
    }

    //Ordering songs against the song/tag mapping table.
    if(preg_match('#^tm.ordering( DESC)?$#', $orderBy, $matches)) {
      //Note: Songs with NULL order value are placed at the end of the list.
      $query->select('ISNULL(tm.ordering), tm.ordering AS tm_ordering');
      $orderBy = 'ISNULL(tm.ordering) ASC, tm_ordering';

      //Check for DESC direction.
      if(isset($matches[1])) {
	$orderBy .= $matches[1]; 
      }
    }

    $query->order($orderBy);

    return $query;
  }


  //Get the current tag.
  public function getTag()
  {
    $tagId = $this->getState('tag.id');
    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('*')
	  ->from('#__tags')
	  ->where('id='.(int)$tagId);
    $db->setQuery($query);
    $tag = $db->loadObject();

    $this->setState('tag.level', $tag->level);

    $images = new JRegistry;
    $images->loadString($tag->images);
    $tag->images = $images;

    return $tag;
  }


  public function getChildren()
  {
    $tagId = $this->getState('tag.id');
    $user = JFactory::getUser();
    $groups = implode(',', $user->getAuthorisedViewLevels());

    //Add one to the start level as we don't want the current tag in the result.
    $startLevel = $this->getState('tag.level', 1) + 1;
    $endLevel = $this->getState('params')->get('tag_max_level', 0);

    if($endLevel > 0) { //Compute the end level from the start level.
      $endLevel = $startLevel + $endLevel;
    }
    elseif($endLevel == -1) { //Display all the subtags.
      $endLevel = 10;
    }

    //Ensure subtags are required.
    if($endLevel) {
      //Get the tag order type.
      $tagOrderBy = $this->getState('params')->get('orderby_pri');
      $orderBy = SongbookHelperQuery::orderbyPrimary($tagOrderBy);
      //Remove the comma and space from the string.
      $orderBy = substr($orderBy, 0, -2);

      $db = $this->getDbo();
      $query = $db->getQuery(true);
      $query->select('DISTINCT n.*')
	    ->from('#__tags AS n, #__tags AS p')
	    ->where('n.lft BETWEEN p.lft AND p.rgt')
	    ->where('n.level >= '.(int)$startLevel.' AND n.level <= '.(int)$endLevel)
	    ->where('n.access IN('.$groups.')')
	    ->where('n.published=1')
	    ->where('p.id='.(int)$tagId);

      if(!empty($orderBy)) {
	$query->order($orderBy);
      }

      $db->setQuery($query);
      $children = $db->loadObjectList();

      if(empty($children)) {
        return $children;
      }

      if($this->getState('params')->get('show_tagged_num_songs', 0)) {
	//Get the tag children ids.
	$ids = array();
	foreach($children as $child) {
	  $ids[] = $child->id;
	}

	//Compute the number of songs for each tag.
	$query->clear()
	      ->select('tm.tag_id, COUNT(*) AS numitems')
	      ->from('#__songbook_song_tag_map AS tm')
	      ->join('LEFT', '#__songbook_song AS s ON s.id=tm.song_id')
	      ->join('LEFT', '#__categories AS ca ON ca.id=s.catid')
	      ->where('s.access IN('.$groups.')')
	      ->where('ca.access IN('.$groups.')');

	// Filter by state
	$published = $this->getState('filter.published');
	if(is_numeric($published)) {
	  //Only published songs are counted when user is not Root.
	  $query->where('s.published='.(int)$published);
	}
	elseif(is_array($published)) {
	  //Songs with different states are also taken in account for super users.
	  JArrayHelper::toInteger($published);
	  $published = implode(',', $published);
	  $query->where('s.published IN ('.$published.')');
	}

	//Do not count expired songs when user is not Root.
	if($this->getState('filter.publish_date')) {
	  // Filter by start and end dates.
	  $nullDate = $db->quote($db->getNullDate());
	  $nowDate = $db->quote(JFactory::getDate()->toSql());

	  $query->where('(s.publish_up = '.$nullDate.' OR s.publish_up <= '.$nowDate.')')
		->where('(s.publish_down = '.$nullDate.' OR s.publish_down >= '.$nowDate.')');
	}

	$query->where('tm.tag_id IN('.implode(',', $ids).') GROUP BY tm.tag_id');
	$db->setQuery($query);
	$tags = $db->loadObjectList('tag_id');

	//Set the numitems attribute.
	foreach($children as $child) {
	  $child->numitems = 0;

	  if(isset($tags[$child->id])) {
	    $child->numitems = $tags[$child->id]->numitems;
	  }
	}
      }

      return $children;
    }

    return array();
  }


  /**
   * Increment the hit counter for the tag.
   *
   * @param   int  $pk  Optional primary key of the tag to increment.
   *
   * @return  boolean True if successful; false otherwise and internal error set.
   *
   * @since   3.2
   */
  public function hit($pk = 0)
  {
    $input = JFactory::getApplication()->input;
    $hitcount = $input->getInt('hitcount', 1);

    if($hitcount) {
      $pk = (!empty($pk)) ? $pk : (int) $this->getState('tag.id');

      $table = JTable::getInstance('Tag', 'JTable');
      $table->load($pk);
      $table->hit($pk);
    }

    return true;
  }


  /**
   * Returns song title suggestions for a given search request.
   *
   * @param   int  $pk  	Optional primary key of the current tag.
   * @param   string $search 	The request search to get the matching title suggestions.
   *
   * @return  mixed		An array of suggestion results.
   *
   */
  public function getAutocompleteSuggestions($pk = null, $search)
  {
    $pk = (!empty($pk)) ? $pk : (int) $this->getState('tag.id');
    $results = array();

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('s.title AS value, s.id AS data')
	  ->from('#__songbook_song AS s')
	  ->join('LEFT', '#__songbook_song_tag_map AS tm ON s.id=tm.song_id')
	  ->where('tm.tag_id='.(int)$pk)
	  ->where('s.published=1')
	  ->where('s.title LIKE '.$db->Quote($search.'%'))
	  ->order('s.title DESC');
    $db->setQuery($query);
    //Requested to get the JQuery autocomplete working properly.
    $results['suggestions'] = $db->loadAssocList();

    return $results;
  }
}



