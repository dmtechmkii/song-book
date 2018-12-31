<?php
/**
 * @package Song Book
 * @copyright Copyright (c) 2017 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die('Restricted access');
require_once('helpers/route.php');


/**
 * Routing class of com_songbook
 *
 * @since  3.3
 */
class SongbookRouter extends JComponentRouterView
{
  protected $noIDs = false;


  /**
   * Song Book Component router constructor
   *
   * @param   JApplicationCms  $app   The application object
   * @param   JMenu            $menu  The menu object to work with
   */
  public function __construct($app = null, $menu = null)
  {
    $params = JComponentHelper::getParams('com_songbook');
    $this->noIDs = (bool) $params->get('sef_ids');

    $tags = new JComponentRouterViewconfiguration('tags');
    $tags->setKey('id');
    $this->registerView($tags);
    $tag = new JComponentRouterViewconfiguration('tag');
    $tag->setKey('id')->setParent($tags, 'tag_id')->setNestable()->addLayout('blog');
    $this->registerView($tag);

    $song = new JComponentRouterViewconfiguration('song');
    $song->setKey('id')->setParent($tag, 'tag_id');
    $this->registerView($song);
    $form = new JComponentRouterViewconfiguration('form');
    $form->setKey('s_id');
    $this->registerView($form);

    parent::__construct($app, $menu);

    $this->attachRule(new JComponentRouterRulesMenu($this));

    if($params->get('sef_advanced', 0)) {
      $this->attachRule(new JComponentRouterRulesStandard($this));
      $this->attachRule(new JComponentRouterRulesNomenu($this));
    }
    else {
      JLoader::register('SongbookRouterRulesLegacy', __DIR__.'/helpers/legacyrouter.php');
      $this->attachRule(new SongbookRouterRulesLegacy($this));
    }
  }


  /**
   * Method to get the segment(s) for a tag 
   *
   * @param   string  $id     ID of the tag to retrieve the segments for
   * @param   array   $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   */
  public function getTagSegment($id, $query)
  {
    $tag = SongbookHelperRoute::getTag($id);

    if($tag) {
      $path = SongbookHelperRoute::getTagPath($id);
      $path[0] = '1:root';

      if($this->noIDs) {
	foreach($path as &$segment) {
	  list($id, $segment) = explode(':', $segment, 2);
	}
      }

      return $path;
    }

    return array();
  }


  /**
   * Method to get the segment(s) for a tag
   *
   * @param   string  $id     ID of the tag to retrieve the segments for
   * @param   array   $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   */
  public function getTagsSegment($id, $query)
  {
    return $this->getTagSegment($id, $query);
  }


  /**
   * Method to get the segment(s) for a song 
   *
   * @param   string  $id     ID of the song to retrieve the segments for
   * @param   array   $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   */
  public function getSongSegment($id, $query)
  {
    if(!strpos($id, ':')) {
      $db = JFactory::getDbo();
      $dbquery = $db->getQuery(true);
      $dbquery->select($dbquery->qn('alias'))
	      ->from($dbquery->qn('#__songbook_song'))
	      ->where('id='.$dbquery->q((int) $id));
      $db->setQuery($dbquery);

      $id .= ':'.$db->loadResult();
    }

    if($this->noIDs) {
      list($void, $segment) = explode(':', $id, 2);

      return array($void => $segment);
    }

    return array((int) $id => $id);
  }


  /**
   * Method to get the segment(s) for a form
   *
   * @param   string  $id     ID of the song form to retrieve the segments for
   * @param   array   $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   *
   * @since   3.7.3
   */
  public function getFormSegment($id, $query)
  {
    return $this->getSongSegment($id, $query);
  }


  /**
   * Method to get the id for a tag
   *
   * @param   string  $segment  Segment to retrieve the ID for
   * @param   array   $query    The request that is parsed right now
   *
   * @return  mixed   The id of this item or false
   */
  public function getTagId($segment, $query)
  {
    if(isset($query['id'])) {
      $tag = SongbookHelperRoute::getTag($query['id'], false);

      if($tag) {
	$children = SongbookHelperRoute::getTagChildren($query['id']);

	foreach($children as $child) {
	  if($this->noIDs) {
	    if($child->alias == $segment) {
	      return $child->id;
	    }
	  }
	  else {
	    if($child->id == (int)$segment) {
	      return $child->id;
	    }
	  }
	}
      }
    }

    return false;
  }


  /**
   * Method to get the id for a tag
   *
   * @param   string  $segment  Segment to retrieve the ID for
   * @param   array   $query    The request that is parsed right now
   *
   * @return  mixed   The id of this item or false
   */
  public function getTagsId($segment, $query)
  {
    return $this->getTagId($segment, $query);
  }


  /**
   * Method to get the id for a song
   *
   * @param   string  $segment  Segment of the song to retrieve the ID for
   * @param   array   $query    The request that is parsed right now
   *
   * @return  mixed   The id of this item or false
   */
  public function getSongId($segment, $query)
  {
    if($this->noIDs) {
      $db = JFactory::getDbo();
      $dbquery = $db->getQuery(true);
      $dbquery->select('id')
	      ->from($dbquery->qn('#__songbook_song'))
	      //Note: Alias is unique for each item.
	      ->where('alias='.$dbquery->q($segment));

      $db->setQuery($dbquery);

      return (int)$db->loadResult();
    }

    return (int)$segment;
  }
}


/**
 * Song router functions
 *
 * These functions are proxys for the new router interface
 * for old SEF extensions.
 *
 * @param   array  &$query  An array of URL arguments
 *
 * @return  array  The URL arguments to use to assemble the subsequent URL.
 *
 * @deprecated  4.0  Use Class based routers instead
 */
function SongbookBuildRoute(&$query)
{
  $app = JFactory::getApplication();
  $router = new SongbookRouter($app, $app->getMenu());

  return $router->build($query);
}


/**
 * Song router functions
 *
 * These functions are proxys for the new router interface
 * for old SEF extensions.
 *
 * @param   array  $segments  The segments of the URL to parse.
 *
 * @return  array  The URL attributes to be used by the application.
 *
 * @deprecated  4.0  Use Class based routers instead
 */
function SongbookParseRoute($segments)
{
  $app = JFactory::getApplication();
  $router = new SongbookRouter($app, $app->getMenu());

  return $router->parse($segments);
}

