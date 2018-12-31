<?php
/**
 * @package Song Book
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('JPATH_BASE') or die('Restricted access');

$tags = $displayData['item']->tags->itemTags;
//Check for the id of the current tag (meaning we're in tag view).
$currentTagId = $parentTagId = 0;
if(isset($displayData['item']->tag_id)) {
  $currentTagId = $displayData['item']->tag_id;
  $parentTagId = $displayData['item']->tag_parent_id;
}
?>

<ul class="tags inline">
<?php foreach($tags as $tag) : ?> 
  <li class="tag-<?php echo $tag->tag_id; ?> tag-list0" itemprop="keywords">
    <?php if($currentTagId == $tag->tag_id) : //No need link for the current tag. ?> 
      <span class="label label-default"><?php echo $this->escape($tag->title); ?></span>
  <?php else : 
          $labelType = 'success';
	  if($tag->tag_id == $parentTagId) {
	    //Sets the parent tag to a different color.
	    $labelType = 'warning';
	  }
    ?> 
      <a href="<?php echo JRoute::_(SongbookHelperRoute::getTagRoute($tag->tag_id.':'.$tag->alias, $tag->language));?>" class="label label-<?php echo $labelType; ?>"><?php echo $this->escape($tag->title); ?></a>
  <?php endif; ?> 
  </li>
<?php endforeach; ?>
</ul>

