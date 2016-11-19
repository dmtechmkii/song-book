<?php
/**
 * @package SongBook 
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access.
defined('_JEXEC') or die; 
//Allows to keep the tab state identical in edit form after saving.
JHtml::_('behavior.tabstate');

//Check against the user permissions.
if(!JFactory::getUser()->authorise('core.manage', 'com_songbook')) {
  return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

// Include dependancies
jimport('joomla.application.component.controller');

$controller = JControllerLegacy::getInstance('Songbook');

//Execute the requested task (set in the url).
//If no task is set then the "display' task will be executed.
$controller->execute(JRequest::getCmd('task'));

$controller->redirect();



