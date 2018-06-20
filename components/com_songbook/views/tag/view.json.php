<?php
/**
 * @package Song Book
 * @copyright Copyright (c) 2016 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/**
 * JSON View class for the Song Book component. It's mainly used for Ajax request. 
 */
class SongbookViewTag extends JViewLegacy
{
  public function display($tpl = null)
  {
    $jinput = JFactory::getApplication()->input;
    $search = $jinput->get('search', '', 'str');

    // Get some data from the models
    $model = $this->getModel();
    $results = $model->getAutocompleteSuggestions($search);

    echo new JResponseJson($results);
  }
}

