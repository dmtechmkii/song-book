<?php
/**
 * @package SongBook
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');

//Prevent params layout (layouts/joomla/edit/params.php) to display twice some fieldsets.
$this->ignore_fieldsets = array('details', 'permissions', 'jmetadata');
?>

<script type="text/javascript">
Joomla.submitbutton = function(task)
{
  if(task == 'song.cancel' || document.formvalidator.isValid(document.getElementById('song-form'))) {
    Joomla.submitform(task, document.getElementById('song-form'));
  }
}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_songbook&view=song&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="song-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_SONGBOOK_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span9">
	    <div class="form-vertical">
	      <?php echo $this->form->getControlGroup('songtext'); ?>
	    </div>
	</div>
	<div class="span3">
	  <?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>

	  <?php if($this->item->id && !empty($this->item->tags->tags)) : //Shown only if one or more tags are already selected. ?>
	    <div class="form-vertical">
	      <?php echo $this->form->getControlGroup('main_tag_id'); ?>
	    </div>
	  <?php endif; ?>

	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>


      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('JGLOBAL_FIELDSET_PUBLISHING', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6">
	  <?php echo JLayoutHelper::render('joomla.edit.publishingdata', $this); ?>
	</div>
	<div class="span6">
	  <?php echo JLayoutHelper::render('joomla.edit.metadata', $this); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JLayoutHelper::render('joomla.edit.params', $this); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'permissions', JText::_('COM_SONGBOOK_TAB_PERMISSIONS', true)); ?>
	      <?php echo $this->form->getInput('rules'); ?>
	      <?php echo $this->form->getInput('asset_id'); ?>
      <?php echo JHtml::_('bootstrap.endTab'); ?>
  </div>

  <input type="hidden" name="task" value="" />
  <?php echo JHtml::_('form.token'); ?>
</form>

