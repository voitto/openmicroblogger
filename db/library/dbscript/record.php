<?php

  /** 
   * dbscript -- restful openid framework
   * @version 0.6.0 -- 22-October-2008
   * @author Brian Hendrickson <brian@dbscript.net>
   * @link http://dbscript.net/
   * @copyright Copyright 2009 Brian Hendrickson
   * @package dbscript
   * @license http://www.opensource.org/licenses/mit-license.php MIT License
   */

  /**
   * Record
   * 
   * an item in a database table.
   * 
   * Usage:
   * <code>
   * // INSERT
   * $User = $db->get_record( 'people' );
   * $User->save();
   * // UPDATE
   * $User = $db->get_record( 'people', $req->userid );
   * $User->set_value( 'first_name', $req->first_name );
   * // DELETE
   * $User = $db->get_record( 'people', $req->userid );
   * $db->delete_record( $User );
   * // SELECT ONE
   * $User = $db->get_record( 'people', $req->userid );
   * print "Hello, $User->first_name!";
   * // SELECT MULTIPLE
   * $people = $db->get_recordset( "SELECT * FROM people WHERE disk_usage > 1000" );
   * </code>
   * 
   * More info...
   * {@link http://dbscript.net/record}
   * 
   * @package dbscript
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @version 0.6.0 -- 22-October-2008
   */

class Record {

  /**
   * attribute names and data from the database field
   * @var string[]
   */
  var $attributes;

  /**
   * list of changed fields since last save_changes
   * @var string[]
   */
  var $modified_fields;
  
  /**
   * primary key field
   * @var string
   */
  var $primary_key;

  /**
   * arguments from Record object instantiation
   * @var string[]
   */
  var $func_args;

  /**
   * fields to SELECT if not *
   * @var string[]
   */
  var $select;

  /**
   * list of related records returned by query
   * @var string[]
   */
  var $children;

  /**
   * sql query used to fetch this record
   * @var string
   */
  var $selecttext;
  
  /**
   * true if this is an unsaved skeleton record
   * @var boolean
   */
  var $skeleton;

  /**
   * true if this record is an unsaved skeleton record
   * @var string[]
   */
  var $relationships;

  /**
   * name of database table
   * @var string
   */
  var $table;

  /**
   * true if the record exists in the database
   * @var string
   */
  var $exists;
  
  var $last_modified;
  
  var $etag;
  
  /**
   * Record
   * 
   * initialize a new record data object
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param string table
   */
  function Record( $table, &$db ) {
    trigger_before( 'Record', $this, $db );
    $func_args = func_get_args();
    // check to see if field names were passed via the awesome "dot notation"
    // otherwise we select all fields, using all system memory until crash.
    $select = explode(".",$table);
    if (count($select) > 1) {
      for ($p=1;$p<count($select);$p++) {
        if ($p == 1) {
          $this->selecttext = $select[$p];
        } else {
          $this->selecttext .= "," . $select[$p];
        }
      }
      $this->table = $select[0];
      $table = $select[0];
    } else {
      $this->table = $table;
      $this->selecttext = "*";
    }
    
    // the record does not exist in the database
    $this->exists = false;
    // check for a primary key as the fourth argument
    if (isset($func_args[3])) {
      $this->primary_key = $func_args[3];
    } elseif (isset($db->models[$table]->primary_key)) {
      $this->primary_key = $db->models[$table]->primary_key;
    } else {
      $this->primary_key = "id";
    }
    // if a record ID was passed, fetch the record
    if (isset($func_args[2])) {
      $db->fetch_record($this,$func_args[2]);
      if ( isset( $db->models[$table]->primary_key ) ) {
        $this->primary_key = $db->models[$table]->primary_key;
      }
    } else {
      // otherwise a new blank record is created
      $this->attributes = array();
      $this->modified_fields = array();
      $this->relationships = array();
      $this->children = array();
      $this->skeleton = false;
      $this->set_value($this->primary_key,"");
      if ( isset( $db->models[$table] ) ) {
        $f = array();
        foreach ($db->models[$table]->field_array as $field=>$data_type) {
          $f[$field] = "";
        }
        $db->set_attributes( $this, $f );
      }
    }
    trigger_after( 'Record', $this, $db );
  }

  /**
   * Field Names
   * 
   * get a list of Record attributes
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @return string[]
   */
  function field_names() {
    $arr = array();
    foreach ( array_keys( $this->attributes ) as $field ) {
      if ($field != $this->primary_key) {
        $arr[] = $field;
      }
    }
    return $arr;
  }

  /**
   * Set Etag
   * 
   * if it's empty, set a valid Etag in the entries table
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @return string[]
   */
  
  function set_etag($person_id = NULL) {
    global $db;
    $Entry =& $db->get_table('entries');
    $atomentry = $Entry->find_by( array('resource'=>$this->table, 'record_id'=>$this->id), $this->id );
    if ($atomentry)
      return true;
    $atomentry = $Entry->base();
    if ($person_id == NULL)
      $person_id = get_person_id();
    if ($atomentry) {
      $id = $this->primary_key;
      $atomentry->set_value( 'etag', getEtag( $this->$id ) );
      $atomentry->set_value( 'resource', $this->table );
      $atomentry->set_value( 'record_id', $this->$id );
      $atomentry->set_value( 'content_type', 'text/html' );
      $atomentry->set_value( 'last_modified', timestamp() );
      $atomentry->set_value( 'person_id', $person_id );
      $aresult = $atomentry->save_changes();
      if ($aresult && array_key_exists('entry_id',$this->attributes)) {
        $this->set_value( 'entry_id', $atomentry->id );
        $this->save_changes();
      }
    }
  }
  
  
  /**
   * Set Value
   * 
   * change a Record attribute value, and
   * register the change in the database
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param string field_name
   * @param string value
   */
  function set_value($field,$value) {
    global $db;
    trigger_before( 'set_value', $this, $db );
    if (!(isset($this->attributes[$this->primary_key]))) {
      $pkfield = $this->primary_key;
      $this->attributes[$pkfield] = "";
      $this->$pkfield =& $this->attributes[$pkfield];
    }
    if ($this->validate_field($field,$value)) {
      if ($db->models[$this->table]->is_blob($field) && is_array($value))
        $value = $value['tmp_name'];
      $this->attributes[$field] = $value;
      if ( !( isset( $this->$field ) ) )
        $this->$field =& $this->attributes[$field];
      $this->modified_fields[] = $field;
    } else {
      trigger_error("the new value for $field is invalid", E_USER_ERROR );
    }
    trigger_after( 'set_value', $this, $db );
  }

  /**
   * Save Changes
   * 
   * Save attributes changed via ->set_value( field, new_value )
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   */
  function save_changes() {
    global $db;
    $result = $db->save_record($this);
    if ($result)
      $this->exists = true;
    trigger_after('save_changes',$db,$this);
    return $result;
  }
  
  /**
   * Save
   * 
   * Save all attributes into the database
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   */
  function save() {
    global $db;
    foreach ( array_keys( $this->attributes ) as $field ) {
      $this->modified_fields[] = $field;
    }
    $result = $db->save_record($this);
    if ($result)
      $this->exists = true;
    return $result;
  }
  
  /**
   * First Child
   * 
   * get the first Record returned from a related table
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param string table
   * @return Record
   */  
  function FirstChild( $table=NULL ) {
    global $db;
    if (!(isset($db->models[$table]))) return false;
    if (!(isset($this->children[$table]))) return false;
    $rs =& $db->recordsets[$this->table];
    if (!$rs) return false;
    if (!($this->ChildCount( $table ) > 0)) {
      return $db->get_record( $table );
    }
    if ( isset( $rs->relations[$this->attributes[$this->primary_key]][$table] ) )
      return $rs->FirstChild( $this->attributes[$this->primary_key], $table );
    return false;
  }

  /**
   * Next Child
   * 
   * Iterate to the next Record returned from a related table
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param string table
   * @return Record
   */
  function NextChild( $table=NULL ) {
    global $db;
    $rs =& $db->recordsets[$this->table];
    if (!$rs) return false;
    if ( isset( $rs->relations[$this->attributes[$this->primary_key]][$table] ) )
      return $rs->NextChild( $this->attributes[$this->primary_key], $table );
    return false;
  }

  /**
   * Child Count
   * 
   * Count the number of related records from a specific table
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param string table
   * @return Record
   */    
  function ChildCount( $table ) {
    if ( isset( $this->children[$table] ) )
      return count( $this->children[$table] );
    return 0;
  }

  /**
   * Has and Belongs To Many
   * 
   * set a 'child-many' relationship
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param string relstring
   */
  function has_and_belongs_to_many( $relstring ) {
    $this->relationships[$relstring] = 'child-many';
  }

  /**
   * Belongs To
   * 
   * set a 'child-one' relationship
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param string relstring
   */
  function belongs_to( $relstring ) {
    $this->relationships[$relstring] = 'child-one';
  }

  /**
   * Has Many
   * 
   * set a 'parent-many' relationship
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param string relstring
   */
  function has_many( $relstring ) {
    $this->relationships[$relstring] = 'parent-many';
  }

  /**
   * Has One
   * 
   * set a 'parent-one' relationship
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param string relstring
   */
  function has_one( $relstring ) {
    $this->relationships[$relstring] = 'parent-one';
  }

  /**
   * Is Skeleton
   * 
   * Set the skeleton flag on a record
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   */
  function is_skeleton() {
    $this->skeleton = true;
  }

  /**
   * Validate Field
   * 
   * Look for a callback function for this field
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param string field_name
   * @param string value
   */
  function validate_field($field,&$value) {
    $function_name = "validate_" . $this->table . "_" . $field;
    if (function_exists($function_name)) {
      return $function_name($value);
    } else {
      return true;
    }
  }

}

?>