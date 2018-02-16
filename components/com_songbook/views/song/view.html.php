<?php
/**
 * @package Song Book
 * @copyright Copyright (c) 2016 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.view');
require_once JPATH_COMPONENT_SITE.'/helpers/route.php';

/**
 * HTML View class for the Song Book component.
 */
class SongbookViewSong extends JViewLegacy
{
  protected $state;
  protected $item;
  protected $nowDate;
  protected $user;
  protected $uri;

  public function display($tpl = null)
  {
    // Initialise variables
    $this->state = $this->get('State');
    $this->item = $this->get('Item');
    $user = JFactory::getUser();

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JFactory::getApplication()->enqueueMessage($errors, 'error');
      return false;
    }

    // Compute the main tag slug.
    $this->item->maintagslug = $this->item->main_tag_alias ? ($this->item->main_tag_id.':'.$this->item->main_tag_alias) : $this->item->main_tag_id;
    //Get the possible extra class name.
    $this->pageclass_sfx = htmlspecialchars($this->item->params->get('pageclass_sfx'));

    //Get the user object and the current url, (needed in the song edit layout).
    $this->user = JFactory::getUser();
    $this->uri = JUri::getInstance();

    //Increment the hits for this song.
    $model = $this->getModel();
    $model->hit();

    $this->nowDate = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);

    //$this->setDocument();

    parent::display($tpl);
  }


  protected function setDocument() 
  {
    //Include css files (if needed).
    //$doc = JFactory::getDocument();
    //$doc->addStyleSheet(JURI::base().'components/com_songbook/css/songbook.css');
  }
}

