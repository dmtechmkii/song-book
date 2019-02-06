<?php
/**
 * @package Song Book
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die('Restricted access'); // No direct access.

//Registers the component helper files. They will be loaded automatically later as soon
//as an helper class is instantiate.
JLoader::register('SongbookHelperRoute', JPATH_SITE.'/components/com_songbook/helpers/route.php');
JLoader::register('SongbookHelperQuery', JPATH_SITE.'/components/com_songbook/helpers/query.php');

$controller = JControllerLegacy::getInstance('Songbook');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();


