<?php
/**
 * @package Song Book
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die('Restricted access');

JHtml::_('formbehavior.chosen', 'select');
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');
?>
<script type="text/javascript">
var songbook = {
  clearSearch: function() {
    document.getElementById('filter_search').value = '';
    songbook.submitForm();
  },

  submitForm: function() {
    var action = document.getElementById('siteForm').action;
    //Set an anchor on the form.
    document.getElementById('siteForm').action = action+'#siteForm';
    document.getElementById('siteForm').submit();
  }
};
</script>

<div class="blog<?php echo $this->pageclass_sfx;?>">
  <?php if($this->params->get('show_page_heading')) : ?>
	  <h1>
	    <?php echo $this->escape($this->params->get('page_heading')); ?>
	  </h1>
  <?php endif; ?>
  <?php if($this->params->get('show_tag_title', 1)) : ?>
	  <h2 class="category-title">
	      <?php echo JHtml::_('content.prepare', $this->tag->title, ''); ?>
	  </h2>
  <?php endif; ?>

  <?php if($this->params->get('show_tag_description') || $this->params->def('show_tag_image')) : ?>
	  <div class="category-desc">
		  <?php if($this->params->get('show_tag_image') && $this->tag->images->get('image_intro')) : ?>
			  <img src="<?php echo $this->tag->images->get('image_intro'); ?>"/>
		  <?php endif; ?>
		  <?php if($this->params->get('show_tag_description') && $this->tag->description) : ?>
			  <?php echo JHtml::_('content.prepare', $this->tag->description, ''); ?>
		  <?php endif; ?>
		  <div class="clr"></div>
	  </div>
  <?php endif; ?>

  <form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="siteForm" id="siteForm">

  <?php if($this->params->get('filter_field') != 'hide' || $this->params->get('show_pagination_limit') || $this->params->get('filter_ordering'))
: ?>
    <div class="songbook-toolbar clearfix">
    <?php
            //Gets the filter fields.
	    $fieldset = $this->filterForm->getFieldset('filter');

	    //Loops through the fields.
	    foreach($fieldset as $field) {
	      $filterName = $field->getAttribute('name');

	      if($filterName == 'filter_search' && $this->params->get('filter_field') != 'hide') { ?>
		<div class="btn-group input-append span6">
	      <?php
	            //Sets the proper hint to display according to the chosen filter (ie: title or author).
		    $hint = JText::_('COM_SONGBOOK_'.$this->params->get('filter_field').'_FILTER_LABEL');
		    $this->filterForm->setFieldAttribute($filterName, 'hint', $hint); 
		    //Displays only the input tag (without the div around).
		    echo $this->filterForm->getInput($filterName, null, $this->state->get('list.'.$filterName));
		    //Adds the search and clear buttons.  ?>
		<button type="submit" onclick="songbook.submitForm();" class="btn hasTooltip"
			title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_SUBMIT'); ?>">
		    <i class="icon-search"></i></button>

		<button type="button" onclick="songbook.clearSearch()" class="btn hasTooltip js-stools-btn-clear"
			title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_CLEAR'); ?>">
		    <?php echo JText::_('JSEARCH_FILTER_CLEAR');?></button>
		</div>
      <?php	}
	      elseif(($filterName == 'filter_ordering' && $this->params->get('filter_ordering')) ||
		     ($filterName == 'limit' && $this->params->get('show_pagination_limit'))) {
		//Sets the field value to the currently selected value.
		$field->setValue($this->state->get('list.'.$filterName));
		echo $field->renderField(array('hiddenLabel' => true, 'class' => 'span3 songbook-filters'));
	      }
	    }
     ?>
     </div>
    <?php endif; ?>

    <?php if(empty($this->lead_items) && empty($this->link_items) && empty($this->intro_items)) : ?>
      <?php if($this->params->get('show_no_tagged_songs')) : ?>
	      <p><?php echo JText::_('COM_SONGBOOK_NO_SONGS'); ?></p>
      <?php endif; ?>
    <?php endif; ?>

    <?php $leadingcount = 0; ?>
    <?php if(!empty($this->lead_items)) : ?>
	    <div class="items-leading clearfix">
	  <?php foreach($this->lead_items as &$item) : ?>
		  <div class="leading-<?php echo $leadingcount; ?><?php echo $item->published == 0 ? ' system-unpublished' : null; ?>"
			  itemprop="blogPost" itemscope itemtype="http://schema.org/BlogPosting">
			  <?php
			  $this->item = & $item;
			  echo $this->loadTemplate('item');
			  ?>
		  </div>
		  <?php $leadingcount++; ?>
	  <?php endforeach; ?>
	    </div><!-- end items-leading -->
    <?php endif; ?>

    <?php
    $introcount = (count($this->intro_items));
    $counter = 0;
    ?>

    <?php if(!empty($this->intro_items)) : ?>
      <?php foreach($this->intro_items as $key => &$item) : ?>
	  <?php $rowcount = ((int) $key % (int) $this->columns) + 1; ?>
	  <?php if($rowcount == 1) : ?>
		  <?php $row = $counter / $this->columns; ?>
		  <div class="items-row cols-<?php echo (int) $this->columns; ?> <?php echo 'row-'.$row; ?> row-fluid clearfix">
	  <?php endif; ?>
	  <div class="span<?php echo round((12 / $this->columns)); ?>">
		  <div class="item column-<?php echo $rowcount; ?><?php echo $item->published == 0 ? ' system-unpublished' : null; ?>"
		      itemprop="blogPost" itemscope itemtype="http://schema.org/BlogPosting">
		      <?php
		      $this->item = & $item;
		      echo $this->loadTemplate('item');
		      ?>
		  </div>
		  <!-- end item -->
		  <?php $counter++; ?>
	  </div><!-- end span -->
	  <?php if(($rowcount == $this->columns) or ($counter == $introcount)) : ?>
		  </div><!-- end row -->
	  <?php endif; ?>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if(!empty($this->link_items)) : ?>
	    <div class="items-more">
	      <?php echo $this->loadTemplate('links'); ?>
	    </div>
    <?php endif; ?>

    <?php if(($this->params->def('show_pagination', 2) == 1  || ($this->params->get('show_pagination') == 2)) && ($this->pagination->pagesTotal > 1)) : ?>
    <div class="pagination">
	    <?php echo $this->pagination->getListFooter(); ?>

	    <?php if ($this->params->def('show_pagination_results', 1) || $this->params->def('show_pagination_pages', 1)) : ?>
	      <div class="songbook-results">
		  <?php if ($this->params->def('show_pagination_results', 1)) : ?>
		      <p class="counter pull-left small">
			<?php echo $this->pagination->getResultsCounter(); ?>
		      </p>
		  <?php endif; ?>
		  <?php if ($this->params->def('show_pagination_pages', 1)) : ?>
		      <p class="counter pull-right small">
			<?php echo $this->pagination->getPagesCounter(); ?>
		      </p>
		  <?php endif; ?>
	      </div>
	    <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if(!empty($this->children) && $this->tagMaxLevel != 0) : ?>
	    <div class="cat-children">
	      <h3><?php echo JTEXT::_('COM_SONGBOOK_SUBTAGS_TITLE'); ?></h3>
	      <?php echo $this->loadTemplate('children'); ?>
	    </div>
    <?php endif; ?>

    <input type="hidden" name="limitstart" value="" />
    <input type="hidden" id="token" name="<?php echo JSession::getFormToken(); ?>" value="1" />
    <input type="hidden" id="tag-id" name="tag_id" value="<?php echo $this->tag->id; ?>" />
    <input type="hidden" name="task" value="" />
  </form>
</div><!-- blog -->

<?php

if($this->params->get('filter_field') == 'title') {
  //Loads the JQuery autocomplete file.
  JHtml::_('script', 'media/jui/js/jquery.autocomplete.min.js');
  //Loads our js script.
  $doc = JFactory::getDocument();
  $doc->addScript(JURI::base().'components/com_songbook/js/autocomplete.js');
}

