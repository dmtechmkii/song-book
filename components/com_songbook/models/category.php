<?php
/**
 * @package SongBook
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

require_once JPATH_COMPONENT_SITE.'/helpers/query.php';

/**
 * SongBook Component Model
 *
 * @package     Joomla.Site
 * @subpackage  com_songbook
 */
class SongbookModelCategory extends JModelList
{
  
  /**
   * Category items data
   *
   * @var array
   */
  protected $_item = null;

  protected $_songs = null;

  protected $_siblings = null;

  protected $_children = null;

  protected $_parent = null;

  /**
   * Model context string.
   *
   * @var		string
   */
  protected $_context = 'com_songbook.category';

  /**
   * The category that applies.
   *
   * @access    protected
   * @var        object
   */
  protected $_category = null;

  /**
   * The list of other song categories.
   *
   * @access    protected
   * @var        array
   */
  protected $_categories = null;


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
	      'ordering', 's.ordering',
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

    //Get and set the current category id.
    $pk = $app->input->getInt('id');
    $this->setState('category.id', $pk);

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
      //All of the songs are returned and it's up to thelayout to 
      //deal with the access (ie: redirect the user to login form when Read more
      //button is clicked).
      $this->setState('filter.access', false);
    }

    // List state information
    //Get the number of songs to display per page.
    //The display_num variable (set in the component or menu config) is taken as default
    //Note: The LIMIT clause is added in the setQuery function: libraries/joomla/database/query/driver.php
    $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $params->get('display_num', 10), 'uint');
    $this->setState('list.limit', $limit);

    //Get the limitstart variable (used for the pagination) from the form variable.
    $limitstart = $app->input->get('limitstart', 0, 'uint');
    $this->setState('list.start', $limitstart);

    // Optional filter text
    $this->setState('list.filter', $app->input->getString('filter-search'));
    //Get the value of the select list and load it in the session.
    $this->setState('list.filter_ordering', $app->input->getString('filter-ordering'));

    $user = JFactory::getUser();
    $asset = 'com_songbook';

    if($pk) {
      $asset .= '.category.'.$pk;
    }

    //Check against the category permissions.
    if((!$user->authorise('core.edit.state', $asset)) && (!$user->authorise('core.edit', $asset))) {
      // limit to published for people who can't edit or edit.state.
      $this->setState('filter.published', 1);

      // Filter by start and end dates.
      $this->setState('filter.publish_date', true);
    }
    else {
      //User can access published, unpublished and archived songs.
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
    $input = JFactory::getApplication()->input;

    //Get some user data.
    $user = JFactory::getUser();
    $userId = $user->get('id');
    $guest = $user->get('guest');
    $groups = $user->getAuthorisedViewLevels();

    // Convert the params field into an object, saving original in _params
    foreach($items as $item) {
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
    $query->select($this->getState('list.select', 's.id,s.title,s.alias,s.intro_text,s.full_text,s.catid,s.published,'.
	                           's.checked_out,s.checked_out_time,s.created,s.created_by,s.access,s.params,s.metadata,'.
				   's.metakey,s.metadesc,s.hits,s.publish_up,s.publish_down,s.language,s.modified,s.modified_by'))
	  ->from($db->quoteName('#__songbook_song').' AS s')
	  //Display songs of the current category.
	  ->where('s.catid='.(int)$this->getState('category.id'));

    // Join on category table.
    $query->select('ca.title AS category_title, ca.alias AS category_alias, ca.access AS category_access')
	  ->join('LEFT', '#__categories AS ca on ca.id = s.catid');

    // Join over the categories to get parent category titles
    $query->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias')
	  ->join('LEFT', '#__categories as parent ON parent.id = ca.parent_id');

    // Join over the users.
    $query->select('us.name AS author')
	  ->join('LEFT', '#__users AS us ON us.id = s.created_by');

    // Join over the asset groups.
    $query->select('al.title AS access_level');
    $query->join('LEFT', '#__viewlevels AS al ON al.id = s.access');

    // Filter by access level.
    if($access = $this->getState('filter.access')) {
      $query->where('s.access IN ('.$groups.')')
	    ->where('ca.access IN ('.$groups.')');
    }

    // Filter by state
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      //User is only allowed to see published songs.
      $query->where('s.published='.(int)$published);
    }
    elseif(is_array($published)) {
      //User is allowed to see songs with different states.
      JArrayHelper::toInteger($published);
      $published = implode(',', $published);
      $query->where('s.published IN ('.$published.')');
    }

    //Do not show expired songs to users who can't edit or edit.state.
    if($this->getState('filter.publish_date')) {
      // Filter by start and end dates.
      $nullDate = $db->quote($db->getNullDate());
      $nowDate = $db->quote(JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true));

      $query->where('(s.publish_up = '.$nullDate.' OR s.publish_up <= '.$nowDate.')')
	    ->where('(s.publish_down = '.$nullDate.' OR s.publish_down >= '.$nowDate.')');
    }

    // Filter by language
    if($this->getState('filter.language')) {
      $query->where('s.language IN ('.$db->quote(JFactory::getLanguage()->getTag()).','.$db->quote('*').')');
    }

    // Filter by search in title
    $search = $this->getState('list.filter');
    //Get the field to search by.
    $field = $this->getState('params')->get('filter_field');
    if(!empty($search)) {
      $search = $db->quote('%'.$db->escape($search, true).'%');
      $query->where('(s.'.$field.' LIKE '.$search.')');
    }

    //Get the songs ordering by default set in the menu options. (Note: sec stands for secondary). 
    $songOrderBy = $this->getState('params')->get('orderby_sec', 'rdate');
    //If songs are sorted by date (ie: date, rdate), order_date defines
    //which type of date should be used (ie: created, modified or publish_up).
    $songOrderDate = $this->getState('params')->get('order_date');
    //Get the field to use in the ORDER BY clause according to the orderby_sec option.
    $orderBy = SongbookHelperQuery::orderbySecondary($songOrderBy, $songOrderDate);

    //Filter by order (eg: the select list set by the end user).
    $filterOrdering = $this->getState('list.filter_ordering');
    //If the end user has define an order, we override the ordering by default.
    if(!empty($filterOrdering)) {
      $orderBy = SongbookHelperQuery::orderbySecondary($filterOrdering, $songOrderDate);
    }

    $query->order($orderBy);

    return $query;
  }


  /**
   * Method to get category data for the current category
   *
   * @param   integer  An optional ID
   *
   * @return  object
   * @since   1.5
   */
  public function getCategory()
  {
    if(!is_object($this->_item)) {
      $app = JFactory::getApplication();
      $menu = $app->getMenu();
      $active = $menu->getActive();
      $params = new JRegistry;

      if($active) {
	$params->loadString($active->params);
      }

      $options = array();
      $options['countItems'] = $params->get('show_cat_num_songs_cat', 1) || $params->get('show_empty_categories', 0);
      $categories = JCategories::getInstance('Songbook', $options);
      $this->_item = $categories->get($this->getState('category.id', 'root'));

      // Compute selected asset permissions.
      if(is_object($this->_item)) {
	$user = JFactory::getUser();
	$asset = 'com_songbook.category.'.$this->_item->id;

	// Check general create permission.
	if($user->authorise('core.create', $asset)) {
	  $this->_item->getParams()->set('access-create', true);
	}

	$this->_children = $this->_item->getChildren();
	$this->_parent = false;

	if($this->_item->getParent()) {
	  $this->_parent = $this->_item->getParent();
	}

	$this->_rightsibling = $this->_item->getSibling();
	$this->_leftsibling = $this->_item->getSibling(false);
      }
      else {
	$this->_children = false;
	$this->_parent = false;
      }
    }

    // Get the tags
    $this->_item->tags = new JHelperTags;
    $this->_item->tags->getItemTags('com_songbook.category', $this->_item->id);

    return $this->_item;
  }

  /**
   * Get the parent category
   *
   * @param   integer  An optional category id. If not supplied, the model state 'category.id' will be used.
   *
   * @return  mixed  An array of categories or false if an error occurs.
   */
  public function getParent()
  {
    if(!is_object($this->_item)) {
      $this->getCategory();
    }

    return $this->_parent;
  }

  /**
   * Get the sibling (adjacent) categories.
   *
   * @return  mixed  An array of categories or false if an error occurs.
   */
  function &getLeftSibling()
  {
    if(!is_object($this->_item)) {
      $this->getCategory();
    }

    return $this->_leftsibling;
  }

  function &getRightSibling()
  {
    if(!is_object($this->_item)) {
      $this->getCategory();
    }

    return $this->_rightsibling;
  }

  /**
   * Get the child categories.
   *
   * @param   integer  An optional category id. If not supplied, the model state 'category.id' will be used.
   *
   * @return  mixed  An array of categories or false if an error occurs.
   * @since   1.6
   */
  function &getChildren()
  {
    if(!is_object($this->_item)) {
      $this->getCategory();
    }

    // Order subcategories
    if(count($this->_children)) {
      $params = $this->getState()->get('params');

      if($params->get('orderby_pri') == 'alpha' || $params->get('orderby_pri') == 'ralpha') {
	jimport('joomla.utilities.arrayhelper');
	JArrayHelper::sortObjects($this->_children, 'title', ($params->get('orderby_pri') == 'alpha') ? 1 : -1);
      }
    }

    return $this->_children;
  }

  /**
   * Increment the hit counter for the category.
   *
   * @param   int  $pk  Optional primary key of the category to increment.
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
      $pk = (!empty($pk)) ? $pk : (int) $this->getState('category.id');

      $table = JTable::getInstance('Category', 'JTable');
      $table->load($pk);
      $table->hit($pk);
    }

    return true;
  }
}



