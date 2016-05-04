<?php
/**
 * @package SongBook
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
 
// import Joomla table library
jimport('joomla.database.table');
 
use Joomla\Registry\Registry;

/**
 * Song table class
 */
class SongbookTableSong extends JTable
{
  /**
   * Constructor
   *
   * @param object Database connector object
   */
  function __construct(&$db) 
  {
    parent::__construct('#__songbook_song', 'id', $db);
    //Needed to use the Joomla tagging system with the song items.
    JTableObserverTags::createObserver($this, array('typeAlias' => 'com_songbook.song'));
  }


  /**
   * Overloaded bind function to pre-process the params.
   *
   * @param   mixed  $array   An associative array or object to bind to the JTable instance.
   * @param   mixed  $ignore  An optional array or space separated list of properties to ignore while binding.
   *
   * @return  boolean  True on success.
   *
   * @see     JTable:bind
   * @since   1.5
   */
  public function bind($array, $ignore = '')
  {
    if(isset($array['params']) && is_array($array['params'])) {
      // Convert the params field to a string.
      $registry = new JRegistry;
      $registry->loadArray($array['params']);
      $array['params'] = (string) $registry;
    }

    if(isset($array['metadata']) && is_array($array['metadata'])) {
      $registry = new JRegistry;
      $registry->loadArray($array['metadata']);
      $array['metadata'] = (string) $registry;
    }

    // Search for the {readmore} tag and split the text up accordingly.
    if(isset($array['songtext'])) {
      $pattern = '#<hr\s+id=("|\')system-readmore("|\')\s*\/*>#i';
      $tagPos = preg_match($pattern, $array['songtext']);

      if($tagPos == 0) {
	$this->intro_text = $array['songtext'];
	$this->full_text = '';
      }
      else {
	//Split songtext field data in 2 parts with the "readmore" tag as a separator.
	//Note: The "readmore" tag is not included in either part.
	list($this->intro_text, $this->full_text) = preg_split($pattern, $array['songtext'], 2);
      }
    }

    // Bind the rules. 
    if(isset($array['rules']) && is_array($array['rules'])) {
      $rules = new JAccessRules($array['rules']);
      $this->setRules($rules);
    }

    return parent::bind($array, $ignore);
  }


  /**
   * Overrides JTable::store to set modified data and user id.
   *
   * @param   boolean  $updateNulls  True to update fields even if they are null.
   *
   * @return  boolean  True on success.
   *
   * @since   11.1
   */
  public function store($updateNulls = false)
  {
    //Get current date and time (equal to NOW() in SQL).
    $now = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);
    $user = JFactory::getUser();

    if($this->id) { // Existing item
      $this->modified = $now;
      $this->modified_by = $user->get('id');
    }
    else {
      // New song. A song created and created_by field can be set by the user,
      // so we don't touch either of these if they are set.
      if(!(int)$this->created) {
	$this->created = $now;
      }

      if(empty($this->created_by)) {
	$this->created_by = $user->get('id');
      }
    }

    //Set the alias of the song.
    
    //Create a sanitized alias, (see stringURLSafe function for details).
    $this->alias = JFilterOutput::stringURLSafe($this->alias);
    //In case no alias has been defined, create a sanitized alias from the title field.
    if(empty($this->alias)) {
      $this->alias = JFilterOutput::stringURLSafe($this->title);
    }

    // Verify that the alias is unique
    $table = JTable::getInstance('Song', 'SongbookTable', array('dbo', $this->getDbo()));

    if($table->load(array('alias' => $this->alias, 'catid' => $this->catid)) && ($table->id != $this->id || $this->id == 0)) {
      $this->setError(JText::_('COM_SONGBOOK_DATABASE_ERROR_SONG_UNIQUE_ALIAS'));
      return false;
    }

    return parent::store($updateNulls);
  }


  /**
   * Method to return the title to use for the asset table.
   *
   * @return  string
   *
   * @since   11.1
   */
  protected function _getAssetTitle()
  {
    return $this->title;
  }


  /**
   * Method to compute the default name of the asset.
   * The default name is in the form table_name.id
   * where id is the value of the primary key of the table.
   *
   * @return  string
   *
   * @since   11.1
   */
  protected function _getAssetName()
  {
    $k = $this->_tbl_key;
    return 'com_songbook.song.'.(int) $this->$k;
  }


  /**
   * We provide our global ACL as parent
   * @see JTable::_getAssetParentId()
   */

  //Note: The component categories ACL override the items ACL, (whenever the ACL of a
  //      category is modified, changes are spread into the items ACL).
  //      This is the default com_content behavior. see: libraries/legacy/table/content.php
  protected function _getAssetParentId(JTable $table = null, $id = null)
  {
    $assetId = null;

    // This is a song under a category.
    if($this->catid) {
      // Build the query to get the asset id for the parent category.
      $query = $this->_db->getQuery(true)
              ->select($this->_db->quoteName('asset_id'))
              ->from($this->_db->quoteName('#__categories'))
              ->where($this->_db->quoteName('id').' = '.(int) $this->catid);

      // Get the asset id from the database.
      $this->_db->setQuery($query);

      if($result = $this->_db->loadResult()) {
        $assetId = (int) $result;
      }
    }

    // Return the asset id.
    if($assetId) {
      return $assetId;
    }
    else {
      return parent::_getAssetParentId($table, $id);
    }
  }
}


