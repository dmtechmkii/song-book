<?php
/**
 * @package SongBook 
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; // No direct access.

jimport('joomla.application.component.controller');


class SongbookController extends JControllerLegacy
{
  public function display($cachable = false, $urlparams = false) 
  {
    require_once JPATH_COMPONENT.'/helpers/songbook.php';

    //Display the submenu.
    SongbookHelper::addSubmenu(JRequest::getCmd('view', 'songs'));

    //Set the default view.
    JRequest::setVar('view', JRequest::getCmd('view', 'songs'));

    //Display the view.
    parent::display();
  }
}


