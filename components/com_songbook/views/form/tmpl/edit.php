<?php
/**
 * @package Song Book
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die('Restricted access'); // No direct access

JHtml::_('behavior.keepalive');
JHtml::_('behavior.tabstate');
JHtml::_('behavior.calendar');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');

// Create shortcut to parameters.
$params = $this->state->get('params');
$uri = JUri::getInstance();
?>

<script type="text/javascript">
Joomla.submitbutton = function(task)
{
  if(task == 'song.cancel' || document.formvalidator.isValid(document.id('song-form'))) {
    Joomla.submitform(task, document.getElementById('song-form'));
  }
  else {
    alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
  }
}
</script>

<div class="edit-song <?php echo $this->pageclass_sfx; ?>">
  <?php if($params->get('show_page_heading')) : ?>
    <div class="page-header">
      <h1>
	<?php echo $this->escape($params->get('page_heading')); ?>
      </h1>
    </div>
  <?php endif; ?>

  <form action="<?php echo JRoute::_('index.php?option=com_songbook&s_id='.(int)$this->item->id); ?>" 
   method="post" name="adminForm" id="song-form" enctype="multipart/form-data" class="form-validate form-vertical">

      <div class="btn-toolbar">
	<div class="btn-group">
	  <button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('song.save')">
		  <span class="icon-ok"></span>&#160;<?php echo JText::_('JSAVE') ?>
	  </button>
	</div>
	<div class="btn-group">
	  <button type="button" class="btn" onclick="Joomla.submitbutton('song.cancel')">
		  <span class="icon-cancel"></span>&#160;<?php echo JText::_('JCANCEL') ?>
	  </button>
	</div>
	<?php if ($params->get('save_history', 0)) : ?>
	<div class="btn-group">
		<?php echo $this->form->getInput('contenthistory'); ?>
	</div>
	<?php endif; ?>
      </div>

      <fieldset>

	<ul class="nav nav-tabs">
		<li class="active"><a href="#details" data-toggle="tab"><?php echo JText::_('COM_SONGBOOK_TAB_DETAILS') ?></a></li>
		<li><a href="#publishing" data-toggle="tab"><?php echo JText::_('COM_SONGBOOK_TAB_PUBLISHING') ?></a></li>
		<li><a href="#language" data-toggle="tab"><?php echo JText::_('JFIELD_LANGUAGE_LABEL') ?></a></li>
		<li><a href="#metadata" data-toggle="tab"><?php echo JText::_('COM_SONGBOOK_TAB_METADATA') ?></a></li>
	</ul>

	<div class="tab-content">
	    <div class="tab-pane active" id="details">
	      <?php echo $this->form->renderField('title'); ?>
	      <?php echo $this->form->renderField('alias'); ?>

	      <?php if($this->form->getValue('id') != 0) : //Existing item. ?>

	      <?php endif; ?>

	      <?php
		echo $this->form->getControlGroup('songtext');
	      ?>
	      </div>

	      <div class="tab-pane" id="publishing">
		<?php echo $this->form->getControlGroup('catid'); 
		      echo $this->form->getControlGroup('tags'); 

		      if($this->item->id && !empty($this->item->tags->tags)) { //Shown only if one or more tags are already selected. 
			echo $this->form->getControlGroup('main_tag_id'); 
		      }

		      echo $this->form->getControlGroup('access'); ?>

		<?php if($this->item->params->get('access-change')) : 
			 echo $this->form->getControlGroup('published'); 
			 echo $this->form->getControlGroup('publish_up'); 
			 echo $this->form->getControlGroup('publish_down'); 
		      endif; ?>
	      </div>

	      <div class="tab-pane" id="language">
		<?php echo $this->form->getControlGroup('language'); ?>
	      </div>

	      <div class="tab-pane" id="metadata">
		<?php echo $this->form->getControlGroup('metadesc'); ?>
		<?php echo $this->form->getControlGroup('metakey'); ?>
	      </div>
	    </div>

    <input type="hidden" name="task" value="" />
    <input type="hidden" name="return" value="<?php echo $this->return_page; ?>" />
    <?php if($this->params->get('enable_category', 0) == 1) :?>
      <input type="hidden" name="jform[catid]" value="<?php echo $this->params->get('catid', 1); ?>" />
    <?php endif; ?>
    <?php echo JHtml::_('form.token'); ?>
    </fieldset>
  </form>
</div>

