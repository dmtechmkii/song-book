<?php
/**
 * @package Song Book 
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die('Restricted access'); // No direct access.
//Allows to keep the tab state identical in edit form after saving.
JHtml::_('behavior.tabstate');

//Check against the user permissions.
if(!JFactory::getUser()->authorise('core.manage', 'com_songbook')) {
  JFactory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
  return false;
}

//Registers the component helper file. It will be loaded automatically later as soon
//as the helper class is instantiate.
JLoader::register('SongbookHelper', JPATH_ADMINISTRATOR.'/components/com_songbook/helpers/songbook.php');

$controller = JControllerLegacy::getInstance('Songbook');

//Execute the requested task (set in the url).
//If no task is set then the "display' task will be executed.
$controller->execute(JFactory::getApplication()->input->get('task'));

$controller->redirect();



