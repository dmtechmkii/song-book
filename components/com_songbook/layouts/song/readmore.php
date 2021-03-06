<?php
/**
 * @package Song Book
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('JPATH_BASE') or die('Restricted access');

$params = $displayData['params'];
$item = $displayData['item'];
?>

<p class="readmore">
	<a class="btn" href="<?php echo $displayData['link']; ?>" itemprop="url">
		<span class="icon-chevron-right"></span>
		<?php if (!$params->get('access-view')) :
			echo JText::_('COM_SONGBOOK_REGISTER_TO_READ_MORE');
		else :
			echo JText::_('COM_SONGBOOK_READ_MORE');
		endif; ?>
	</a>
</p>

