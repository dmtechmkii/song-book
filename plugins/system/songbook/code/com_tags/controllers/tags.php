<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_tags
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * The Tags List Controller
 *
 * @since  3.1
 */
class TagsControllerTags extends JControllerAdmin
{
	/**
	 * Proxy for getModel
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  An optional associative array of configuration settings.
	 *
	 * @return  JModelLegacy  The model.
	 *
	 * @since   3.1
	 */
	public function getModel($name = 'Tag', $prefix = 'TagsModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Rebuild the nested set tree.
	 *
	 * @return  boolean  False on failure or error, true on success.
	 *
	 * @since   3.1
	 */
	public function rebuild()
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$this->setRedirect(JRoute::_('index.php?option=com_tags&view=tags', false));

		$model = $this->getModel();

		if ($model->rebuild())
		{
			// Rebuild succeeded.
			$this->setMessage(JText::_('COM_TAGS_REBUILD_SUCCESS'));

			return true;
		}
		else
		{
			// Rebuild failed.
			$this->setMessage(JText::_('COM_TAGS_REBUILD_FAILURE'));

			return false;
		}
	}


	/**
	 * Method to publish a list of items
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function publish()
	{
	  /** - Song Book Override - **/

	  JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
	  // Get items to publish from the request.
	  $cid = $this->input->get('cid', array(), 'array');
	  $task = $this->getTask();

	  if(!empty($cid)) {
	    //Includes the Songbook helper class.
	    JLoader::register('SongbookHelper', JPATH_ADMINISTRATOR.'/components/com_songbook/helpers/songbook.php');

	    if(($task == 'trash' || $task == 'archive') && !SongbookHelper::checkMainTags($cid)) {
	      $this->setRedirect(JRoute::_('index.php?option=com_tags&view=tags', false));
	      return false;
	    }
	  }

	  return parent::publish();
	}
}
