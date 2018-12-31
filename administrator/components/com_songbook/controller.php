<?php
/**
 * @package Song Book 
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die('Restricted access'); // No direct access.


class SongbookController extends JControllerLegacy
{
  public function display($cachable = false, $urlparams = false) 
  {
    require_once JPATH_COMPONENT.'/helpers/songbook.php';

    //Display the submenu.
    SongbookHelper::addSubmenu($this->input->get('view', 'songs'));

    //Set the default view.
    $this->input->set('view', $this->input->get('view', 'songs'));

    //Display the view.
    parent::display();
  }
}


