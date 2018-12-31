<?php
/**
 * @package Song Book
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die('Restricted access');
JHtml::_('behavior.framework');

//Create shortcut for params.
$params = $this->item->params;
?>

<div class="song-item">
  <?php echo JLayoutHelper::render('song.title', array('item' => $this->item, 'params' => $params, 'now_date' => $this->nowDate)); ?>

  <?php echo JLayoutHelper::render('song.icons', array('item' => $this->item, 'user' => $this->user, 'uri' => $this->uri)); ?>

  <?php $useDefList = ($params->get('show_modify_date') || $params->get('show_publish_date') || $params->get('show_create_date')
		       || $params->get('show_hits') || $params->get('show_category') || $params->get('show_parent_category')
		       || $params->get('show_author') ); ?>

  <?php if ($useDefList) : ?>
    <?php echo JLayoutHelper::render('song.info_block', array('item' => $this->item, 'params' => $params)); ?>
  <?php endif; ?>

  <?php if($params->get('show_tags') && !empty($this->item->tags->itemTags)) : ?>
    <?php echo JLayoutHelper::render('song.tags', array('item' => $this->item)); ?>
  <?php endif; ?>

  <?php echo $this->item->intro_text; ?>

  <?php if($params->get('show_readmore') && !empty($this->item->full_text)) :
	  if($params->get('access-view')) :
	    $link = JRoute::_(SongbookHelperRoute::getSongRoute($this->item->slug, $this->item->tagid, $this->item->language));
	  else : //Redirect the user to the login page.
	    $menu = JFactory::getApplication()->getMenu();
	    $active = $menu->getActive();
	    $itemId = $active->id;
	    $link = new JUri(JRoute::_('index.php?option=com_users&view=login&Itemid='.$itemId, false));
	    $link->setVar('return', base64_encode(JRoute::_(SongbookHelperRoute::getSongRoute($this->item->slug, $this->item->tagid, $this->item->language), false)));
	  endif; ?>

	<?php echo JLayoutHelper::render('song.readmore', array('item' => $this->item, 'params' => $params, 'link' => $link)); ?>

  <?php endif; ?>
</div>

