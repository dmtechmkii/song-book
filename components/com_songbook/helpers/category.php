<?php
/**
 * @package SongBook
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/**
 * SongBook Component Category Tree
 *
 * @static
 * @package     Joomla.Site
 * @subpackage  com_songbook
 * @since       1.6
 */
class SongbookCategories extends JCategories
{
  public function __construct($options = array())
  {
    $options['table'] = '#__songbook_song';
    $options['extension'] = 'com_songbook';

    /* IMPORTANT: By default publish parent function invoke a field called "state" to
     *            publish/unpublish (but also archived, trashed etc...) an item.
     *            Since our field is called "published" we must informed the 
     *            JCategories publish function in setting the "statefield" index of the 
     *            options array
    */
    $options['statefield'] = 'published';

    parent::__construct($options);
  }
}
