<?php

  /** 
   * dbscript -- restful openid framework
   * @version 0.6.0 -- 22-October-2008
   * @author Brian Hendrickson <brian@dbscript.net>
   * @link http://dbscript.net/
   * @copyright Copyright 2008 Brian Hendrickson
   * @package dbscript
   * @license http://www.opensource.org/licenses/mit-license.php MIT License
   */

  /**
   * Database
   * 
   * Connects to the database, fetches records
   * into data objects and performs CRUD operations.
   * 
   * Usage:
   * <code>
   * $db = new PostgreSQL(
   *   'host',
   *   'db',
   *   'user'
   * );
   * </code>
   * 
   * More info...
   * {@link http://dbscript.net/database}
   * 
   * @package dbscript
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @version 0.6.0 -- 22-October-2008
   */

class Database {
  
  /**
   * connection resource used to access the database
   * @var resource
   */
  var $conn;
  
  /**
   * true if the database is connected
   * @var bool
   */
  var $db_open;

  /**
   * array of data models
   * @var Model[]
   */
  var $models;

  /**
   * array of recordsets
   * @var RecordSet[]
   */
  var $recordsets;

  /**
   * tables
   * @var tables
   */
  var $tables;
  
  /**
   * maximum binary file size
   * @var integer
   */
  var $max_blob_length;

  /**
   * maximum string length
   * @var integer
   */
  var $max_string_length;

  /**
   * poss. values for boolean true
   * @var string[]
   */
  var $true_values;

  /**
   * datatype groupings
   * @var string[]
   */
  var $datatype_map;

  /**
   * file to upload after insert
   * @var string[]
   */  
  var $file_upload;
  
  /**
   * Get Record
   * 
   * return a Record object for the named table
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param string table
   * @return Record
   */
  function get_record($table) {
    trigger_before( 'get_record', $this, $this );
    $func_args = func_get_args();
    if (count($func_args) > 0 && count($func_args) < 4) {
      if (isset($func_args[2])) {
        return new Record($table,$this,$func_args[1],$func_args[2]);
      } elseif (isset($func_args[1])) {
        return new Record($table,$this,$func_args[1]);
      } else {
        return new Record($table,$this);
      }
    } else {
      return false;
    }
  }

  /**
   * Iterate Record
   * 
   * return a Record object from the Model's active result set
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param string table
   * @param string[] fields
   * @param recordset rs
   * @param integer id
   * @return Record
   */  
  function iterator_load_record($table,$fields,$rs,$id=NULL) {
    trigger_before( 'iterator_load_record', $this, $rs );
    //if ($this->models[$table]->custom_class) {
    //  $custom_class = $this->models[$table]->custom_class;
    //  $rec = new $custom_class( $this->models[$table] );
    //  if (!$rec) trigger_error( "error instantiating $custom_class", E_USER_ERROR );
    //  $rec->Record($table,$this);
    //  foreach ($rec->relationships as $key=>$val) {
    //    $this->models[$table]->set_relation( $key, $val );
    //  }
    //} else {
    //$rec = $this->get_record($table);
    //foreach ($rec->relationships as $key=>$val)
    //  $this->models[$table]->set_relation( $key, $val );
    //}
    
    $mdl =& $this->get_table($table);

    $rec = $mdl->base();

    if ( isset( $rs->relations[$fields[$rec->primary_key]] ) ) {
      foreach ( $rs->relations[$fields[$rec->primary_key]] as $reltable=>$relpkvalue ) {
        $rec->children[$reltable] = $relpkvalue;
      }
    }
    $rec->attributes[$rec->primary_key] = $fields[$rec->primary_key];
    $primary_key = $rec->primary_key;
    $rec->$primary_key =& $rec->attributes[$rec->primary_key];
    $this->set_attributes($rec,$fields);
    trigger_after( 'iterator_load_record', $this, $rec );
    return $rec;
  }

  /**
   * Get RecordSet
   * 
   * return a multi-graph RecordSet from a SQORP-formatted SQL join query
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param string sql
   * @return RecordSet
   */  
  function get_recordset($sql) {
    trigger_before( 'get_recordset', $this, $this );
    return new RecordSet($sql,$this);
  }

  /**
   * Get Inline Url
   * 
   * deprecated
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param string[] blob_location
   * @param string content_type
   * @return string
   */  
  function get_inline_url($blob_location,$content_type) {
    trigger_before( 'get_inline_url', $this, $this );
    $url = "?action=get_file";
    if (is_array($blob_location)) {
      $url .= "&i=" . $blob_location['id'];
      $url .= "&k=" . $blob_location['primary_key'];
      $url .= "&t=" . $blob_location['table'];
      $url .= "&f=" . $blob_location['field'];
    } else {
      $url .= "&o=" . $blob_location;
    }
    $url .= "&c=" . urlencode($content_type);
    return $url;
  }

  /**
   * Max Upload Megabytes
   * 
   * set the file upload size limit
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param integer megabytes
   */ 
  function max_upload_megabytes( $megabytes ) {
    trigger_before( 'max_upload_megabytes', $this, $this );
    $this->max_blob_length = ( $megabytes * 1024000 );
  }
  
  /**
   * Skeletor
   * 
   * create a skeleton from an existing Record
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param Record rec
   * @return Record
   */ 
  function skeletor(&$rec) {
    trigger_before( 'skeletor', $this, $this );
    $table = $rec->table;
    $pk = $rec->primary_key;
    $fields = array();
    foreach ($rec->attributes as $key=>$val) {
      $fields[$key] = "";
    }
    $fields[$rec->primary_key] = 0;
    $skeleton = $this->get_record($table);
    $skeleton->is_skeleton();
    $this->set_attributes($skeleton,$fields);
    return $skeleton;
  }
  
  /**
   * Distinct Values
   * 
   * get a list of distinct values
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param string table
   * @param string field
   * @param string orderby
   * @return string[]
   */
  function distinct_values( $table, $field, $orderby="" ) {
    trigger_before( 'distinct_values', $this, $this );
    $values = array();
    if (!(strlen($orderby) > 0)) { $orderby = $field; }
    $sql = $this->select_distinct( $field, $table, $orderby );
    $result = $this->get_result($sql);
    if (!($this->num_rows($result) > 0)) {
      return false;
    }
    while ( $row = $this->fetch_array( $result ) ) {
      $values[$row[$this->models[$table]->primary_key]] = $row[$field];
    }
    return $values;
  }
  
  /**
   * Fetch Record
   * 
   * fetch a record
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param Record rec
   * @param integer id
   */
  function fetch_record(&$rec,$id) {
    trigger_before( 'fetch_record', $this, $rec );
    $sql = $this->sql_select_for( $rec, $id );
    $result = $this->get_result($sql);
    if (!($this->num_rows($result) > 0)) {
      return false;
    }
    $pkfield = $rec->primary_key;
    $rec->attributes[$pkfield] = $id;
    $rec->$pkfield = $id;
    $field_array = $this->fetch_array($result);
    $this->set_attributes($rec,$field_array);
    trigger_after( 'fetch_record', $this, $rec );
  }

  /**
   * Get Mapped Datatype
   * 
   * return the abstract type for a given native type
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param string raw_datatype
   * @param string
   */
  function get_mapped_datatype( $raw_datatype ) {
    trigger_before( 'get_mapped_datatype', $this, $this );
    if (strstr($raw_datatype,"(")) {
      $raw_datatype = substr($raw_datatype, 0, strpos($raw_datatype,"("));
    }
    if (array_key_exists( $raw_datatype, $this->datatype_map )) {
      return $this->datatype_map[$raw_datatype];
    } else {
      if (ignore_errors()) return 'char';
      trigger_error( "Error, the $raw_datatype datatype is not listed in dbscript's datatype map.", E_USER_NOTICE );
    }
  }


  /**
   * Get Resource
   * 
   * return the resource's data model objects in an array
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @return Model[]
   */
  function &get_resource() {
    trigger_before( 'get_resource', $this, $this );
    global $request;
    $objects = array();
    if (isset($request->resource)) {
      $t = $request->resource;
      $objects[classify($t)] =& $this->get_table( $t );
      

      
      foreach ($this->models as $table=>$obj) {
        
        
        $objects[classify($table)] =& $this->models[$table];
      }
      
    }
    return $objects;
  }


  /**
   * Get Objects
   * 
   * return the data model objects in an array
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @return Model[]
   */
  function &get_objects() {
    trigger_before( 'get_objects', $this, $this );
    $objects = array();
    $skip = array( '.', '..' );
    $paths = array(model_path());
    if (isset($GLOBALS['PATH']['apps'])) {
      foreach($GLOBALS['PATH']['apps'] as $k=>$v) {
        $paths[] = $v['model_path'];
      }
    }
    foreach ($paths as $path) {
      if ( $handle = opendir( $path )) {
        while ( false !== ( $file = readdir( $handle ))) {
          if (!(in_array($file, $skip)) && substr( $file, -4 ) == '.php' ) {
            $o = substr( $file, 0, -4 );
            $objects[$o] =& $this->get_table( tableize($o) );
          }
        }
      }
      foreach( $objects as $name=>$model )
        $model->save();
      closedir($handle);
    }
    return $objects;
  }


  /**
   * Table Exists
   * 
   * return whether a table exists
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @return Model[]
   */
  function table_exists( $table ) {
    trigger_before( 'table_exists', $this, $this );
    return isset( $this->models[$table] );
  }
  
  function set_param( $param, $value ) {
    $this->$param = $value;
  }
  
  /**
   * Set Data Array
   * 
   * populate a data object's attributes array
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param Record rec
   * @param string[] fields
   */
  function set_attributes(&$rec,&$fields) {
    global $datatypelist;
    trigger_before( 'set_attributes', $this, $this );
    foreach ($fields as $field=>$value) {
      if ($field == $rec->primary_key && $value == "") $value = 0;
      if (!is_numeric($field)) {
        if (!isset($datatypelist[$rec->table][$field])) {
          if (!(is_array($datatypelist)))
            $datatypelist = array();
          $datatypelist[$rec->table][$field] = $this->get_mapped_datatype($this->models[$rec->table]->field_array[$field]);
        }
        $datatype = $datatypelist[$rec->table][$field];
        if ($datatype == 'blob') {
          $value = $this->blob_value( $rec, $field, $value ); // oid (pgsql) or array (mysql)
        }
        if ($datatype == 'bool') {
          if ( in_array( $value, $this->true_values, true ) ) {
            $value = true;
          } else {
            $value = false;
          }
        }
        $rec->attributes[$field] = $value;
        if ( !( isset( $rec->$field ) ) )
          $rec->$field =& $rec->attributes[$field];
      }
    }
    if (!(in_array($rec->attributes[$rec->primary_key], array('',0), true)))
      $rec->exists = true;
    $rec->table = $rec->table;
    $rec->exists = $rec->exists;
  }
  
  function just_get_objects() {
    if ( isset( $_GET['dbscript_xml_error_continue'] )) {
      $path = $GLOBALS['PATH']['models'];
      if (is_dir($path)) {
        if ($handle = opendir($path)) {
          while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..' && substr($file,-3) == 'php') {
              $table = tableize(substr($file,0,-4));
              if ( !( in_array( $table, $this->get_tables() ) ) ) {
                $tab = $this->get_table($table);
              }
            }
          }
          closedir($handle);
        }
      }
      $this->get_objects();
      return true;
    }
    
    return false;
    
  }
  
  function primary_key_for( $table ) {
    trigger_before( 'primary_key_for', $this, $this );
    if (!$this->models[$table]->exists) {
      $fields = $this->get_fields( $table );
      if (isset($fields[$table."_primary_key"]))
        $pk = $fields[$table."_primary_key"];
      else
        $pk = 'id';
    } else {
      $pk = $this->models[$table]->primary_key;
    }
    return $pk;
  }
  
  /**
   * Save Record
   * 
   * save a record's attributes into the database
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param Record rec
   */
  function save_record( &$rec ) {
    trigger_before( 'save_record', $rec, $this->models[$rec->table] );
    if ( !$rec->modified_fields && ( $rec->exists )) {
      return true; // nothing to save!
    }
    if ( $rec->exists ) {
      // update
      if ( array_key_exists( 'last_modified', $rec->attributes ))
        $rec->set_value( 'last_modified', timestamp() );
      if ( array_key_exists( 'modified_at', $rec->attributes ))
        $rec->set_value( 'modified_at', timestamp() );
      if ( array_key_exists( 'modified', $rec->attributes ))
        $rec->set_value( 'modified', timestamp() );
      if ( array_key_exists( 'access_time', $rec->attributes ))
        $rec->set_value( 'access_time', timestamp() );
      $result = $this->get_result( $this->sql_update_for( $rec ));
    } else {
      // insert
      if ( array_key_exists( 'created_at', $rec->attributes ))
        $rec->set_value( 'created_at', timestamp() );
      if ( array_key_exists( 'created', $rec->attributes ))
        $rec->set_value( 'created', timestamp() );
      if ( array_key_exists( 'issued', $rec->attributes ))
        $rec->set_value( 'issued', timestamp() );
      $result = $this->get_result( $this->sql_insert_for( $rec ));
      $this->post_insert( $rec, $result );
    }
    $rec->exists = true;
    $rec->modified_fields = array();
    trigger_after( 'save_record', $rec, $this, $this->models[$rec->table] );
    return true;
  }

  /**
   * Delete Record
   * 
   * delete a record from the database
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param Record rec
   * @return boolean
   */
  function delete_record( &$rec ) {
    $return = false;
    trigger_before( 'delete_record', $this, $rec );
    if ($rec->exists) {
      if (isset($rec->attributes['entry_id']) && $this->table_exists('entries')) {
        $Entry =& $this->model('Entry');
        $e = $Entry->find_by(array('resource'=>$rec->table,'record_id'=>$rec->id));
        if ($e) {
          $join =& $this->get_table($Entry->join_table_for('categories', 'entries'));
          $join->find_by('entry_id',$e->id);
          while ($j = $join->MoveNext())
            $jdel = $this->get_result( $this->sql_delete_for( $j ) );
        }
      }
      if (strlen($rec->attributes[$rec->primary_key]) > 0) {
        $result = $this->get_result( $this->sql_delete_for( $rec ) );
      }
      if (!$result) {
        $return = false;
      } else {
        $rec->exists = false;
        $return = true;
      }
    }
    trigger_after( 'delete_record', $this, $rec );
    return $return;
  }


  function aws_delfile(&$rec, $pkvalue) {
    $ext = extension_for(type_of( $_FILES[strtolower(classify($rec->table))]['name'][$this->file_upload[0]] ));
    $aws_file = $rec->table . $pkvalue . "." . $ext;
    lib_include( 'S3' );
    $s3 = new S3( environment('awsAccessKey'), environment('awsSecretKey') );
    if (!$s3)
      trigger_error( 'Sorry, there was a problem connecting to Amazon Web Services', E_USER_ERROR );
    if (!($s3->deleteObject(environment('awsBucket'), urlencode($aws_file))))
      trigger_error( 'Sorry, there was a problem deleting the file from Amazon Web Services', E_USER_ERROR );
  }
  
  
  function aws_putfile(&$rec, $pkvalue) {
    global $request;
    $file = $rec->table . $pkvalue . "." . extension_for(type_of( $_FILES[strtolower(classify($rec->table))]['name'][$this->file_upload[0]] ));
    lib_include( 'S3' );
    $s3 = new S3( environment('awsAccessKey'), environment('awsSecretKey') );
    if (!$s3)
      trigger_error( 'Sorry, there was a problem connecting to Amazon Web Services', E_USER_ERROR );
    $result = $s3->putBucket( environment('awsBucket'), 'public-read' );
    if (!$result)
      trigger_error( 'Sorry, there was a problem creating the bucket '.environment('awsBucket').' at Amazon Web Services', E_USER_ERROR );
    if (file_exists($this->file_upload[1])) {
      if (!($s3->putObjectFile( $this->file_upload[1] , environment('awsBucket'), $file, 'public-read' )))
        trigger_error( 'Sorry, there was a problem uploading the file to Amazon Web Services', E_USER_ERROR );
      unlink($this->file_upload[1]);
    }
    $this->file_upload = false;
  }

}

?>