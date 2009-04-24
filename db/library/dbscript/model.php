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
   * Data Model
   * 
   * Describes a database table: fields, rules and relationships.
   *
   * Automatically composes simple JOIN queries for relationships
   * described in the models via has_many, has_one, etc.
   * 
   * Usage:
   * <code>
   *   // define a new table named photos
   * $model =& $db->model( 'Photo' );
   *
   *   // define the fields in the table
   * $model->auto_field( 'id' );
   * $model->file_field( 'image' );
   * $model->char_field( 'title', array('len'=>255) );
   * $model->text_field( 'caption' );
   *
   *   // create the table in the database
   * $model->save();
   *
   *   // deny access to everybody, unless they are an administrator
   * $model->let_access( 'all:never all:admin' );
   *
   *   // function to test whether current user is an administrator
   * function admin() {
   *   if ( member_of( 'administrators' ))
   *     return true;
   *   return false;
   * }
   * </code>
   * 
   * More info...
   * {@link http://dbscript.net/model}
   * 
   * @package dbscript
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @version 0.6.0 -- 22-October-2008
   */

class Model {
  
  /**
   * name of the database table
   * @var string
   */
  var $table;
  
  /**
   * true if table exists in database
   * @var boolean
   */
  var $exists;
  
  /**
   * list of field names
   * @var string[]
   */
  var $field_array;
  
  /**
   * list of field attributes
   * @var string[]
   */
  var $field_attrs;
  
  /**
   * list of security access rules
   * @var string[]
   */
  var $access_list;
  
  /**
   * table has entry_id field
   * @var boolean
   */
  
  var $has_metadata;
  
  /**
   * name of primary key field
   * @var string
   */
  var $primary_key;
    
  /**
   * name of collection key field
   * @var string
   */
  var $uri_key;
  
  /**
   * list of relationships to other tables
   * @var string[]
   */
  var $relations;
  
  /**
   * proper CamelCase name of data model object
   * @var string
   */
  var $custom_class;
  
  /**
   * default blob field for the model
   * @var string
   */
  var $blob;
  
  /**
   * list of public methods
   * @var string[]
   */
  var $allowed_methods;
  
  /**
   * when querying with find(), offset by x records
   * @var integer
   */
  var $offset;

  /**
   * limit query to x records
   * @var integer
   */
  var $limit;

  /**
   * order query by this column
   * @var string
   */
  var $orderby;

  /**
   * order query ASC or DESC
   * @var string
   */
  var $order;
  
  /**
   * list of Collection/Feed params for layout
   * @var string[]
   */
  var $params;
  
  /**
   * hide this table
   * @var boolean
   */
  var $hidden;
  
  var $find_by;
  
  function insert_from_post( &$req ) {
    
    trigger_before( 'insert_from_post', $this, $req );
    
    global $db;
    
    $fields = $this->fields_from_request($req);
    
    foreach ($fields as $table=>$fieldlist) {
      
      // for each table in the submission do
      $pkfield = $db->models[$table]->primary_key;
      
      // blank record
      $rec = $db->models[$table]->base();
      
      $content_type = 'text/html';
      // set attributes
      
      $mdl =& $db->get_table($table);
      
      if (!($mdl->can_create( $table )))
        trigger_error( "Sorry, you do not have permission to " . $req->action . " " . $table, E_USER_ERROR );
      
      foreach ( $fieldlist as $field=>$type ) {
        if ($this->has_metadata && is_blob($table.'.'.$field)) {
          if (isset($_FILES[strtolower(classify($table))]['name'][$field]))
            $content_type = type_of( $_FILES[strtolower(classify($table))]['name'][$field] );
        }
        $rec->set_value( $field, $req->params[strtolower(classify($table))][$field] );
      }
      // save
      
      if ($table != $this->table) {
        $relfield = strtolower(classify($this->table))."_id";
        if (isset($mdl->field_array[$relfield])) {
          if ($req->params['id'] > 0) {
            $rec->set_value($relfield,$req->params['id']);
          }
        }
      }
      
      $result = $rec->save_changes();
      
      if ( !$result )
        trigger_error( "The record could not be saved into the database.", E_USER_ERROR );
      
      if ( $this->has_metadata ) {
        $atomentry = $this->set_metadata($rec,$content_type,$table,$pkfield);
        if (($rec->table == $this->table) && isset($rec->id)) {
          $this->set_categories($rec,$req,$atomentry);
        }
      }
      
      
    }
    
    trigger_after( 'insert_from_post', $this, $rec );
    
  }
  
  function set_metadata(&$rec,$content_type,$table,$pkfield) {
    global $db;
    $atomentry = $db->models['entries']->base();
    if ($atomentry) {
      $atomentry->set_value( 'etag', getEtag( $rec->$pkfield ) );
      $atomentry->set_value( 'resource', $table );
      $atomentry->set_value( 'record_id', $rec->$pkfield );
      $atomentry->set_value( 'content_type', $content_type );
      $atomentry->set_value( 'last_modified', timestamp() );
      $atomentry->set_value( 'person_id', get_person_id() );
      $aresult = $atomentry->save_changes();
      if ($aresult) {
        if ( array_key_exists( 'entry_id', $rec->attributes ))
          $rec->set_value( 'entry_id', $atomentry->id );
        if ( array_key_exists( 'person_id', $rec->attributes ))
          $rec->set_value( 'person_id', get_person_id() );
        $rec->save_changes();
      }
    }
    return $atomentry;
  }
  
  function set_categories(&$rec,&$req,&$atomentry) {
    global $db;
    $req->set_param( 'id', $rec->id );
    $req->id = $rec->id;
    $Category =& $db->model('Category');
    $Entry =& $db->model('Entry');
    foreach($req->params as $cname=>$catval) {
      if (substr($cname,0,8) == 'category') {
        $added = array();
        if (!in_array($req->$cname, $added)) {
          $join =& $db->get_table($Entry->join_table_for('categories', 'entries'));
          $j = $join->base();
          $j->set_value('entry_id',$atomentry->id);
          $c = $Category->find_by('term',$req->$cname);
          if ($c) {
            $j->set_value('category_id',$c->id);
            $j->save_changes();
            $added[] = $req->$cname;
          } elseif (!empty($req->$cname)) {
            if (isset_admin_email()) {
              $c = $Category->base();
              $c->set_value( 'name', $req->$cname);
              $c->set_value( 'term', strtolower($req->$cname));
              $c->save();
              $j->set_value('category_id',$c->id);
              $j->save_changes();
              $added[] = $req->$cname;
              admin_alert( "created a new category: ".$req->$cname." at ".$req->base );
            }
          }
        }
      }
    }
  }
  
  function update_from_post( &$req ) {
    
    trigger_before( 'update_from_post', $this, $req );
    
    global $db;
    
    $fields = $this->fields_from_request($req);
    
    if (isset($fields[$req->resource]))
      $fieldsarr = $fields[$req->resource];
    
    if (!(isset($fieldsarr)))
      trigger_error( "The fields were not found in the request.".print_r($fields), E_USER_ERROR );
      
    if ( $this->has_metadata ) {
      
      $Person =& $db->model('Person');
      $Group =& $db->model('Group');
      
      if (!(isset($req->params['entry']['etag'])))
        trigger_error( "Sorry, the etag was not submitted with the database entry", E_USER_ERROR );
      
      $atomentry = $db->models['entries']->find_by( 'etag', $req->params['entry']['etag'] );
      
      if (!$atomentry->exists) {
        
        $atomentry = $db->models['entries']->base();

        $atomentry->set_value( 'etag', getEtag( srand(date("s")) ) );
        $atomentry->set_value( 'resource', $req->resource );
        $atomentry->set_value( 'record_id', $rec->$pkfield );
        $atomentry->set_value( 'content_type', $content_type );
        $atomentry->set_value( 'last_modified', timestamp() );
        $atomentry->set_value( 'person_id', get_person_id() );
        
        $aresult = $atomentry->save_changes();
        
      }
      
      $p = $Person->find( get_person_id() );
      
      if (!($p->id == $atomentry->attributes['person_id']) && !$this->can_superuser($req->resource))
        trigger_error( "Sorry, your id does not match the owner of the database entry", E_USER_ERROR );
      
      $recid = $atomentry->attributes['record_id'];
      
      if (empty($recid))
        trigger_error('The input form eTag did not match a record_id in entries.', E_USER_ERROR);
      
    } else {
      
      $recid = $req->id;

      if (empty($recid))
        trigger_error('The record id was not found in the "id" form field.', E_USER_ERROR);
      
    }
    
    $rec = $this->find( $recid );
    
    foreach ( $fieldsarr as $field=>$type ) {
      if ($this->has_metadata && is_blob($rec->table.'.'.$field)) {
        if (isset($_FILES[strtolower(classify($rec->table))]['name'][$field])) {
          if ( $this->has_metadata ) {
            $content_type = type_of( $_FILES[strtolower(classify($rec->table))]['name'][$field] );
            $atomentry->set_value( 'content_type', $content_type );
          }
        }
      }
      $rec->set_value( $field, $req->params[strtolower(classify($rec->table))][$field] );
    }
    
    $result = $rec->save_changes();
    
    foreach ($fields as $table=>$fieldlist) {
      // for each table in the submission do
      $mdl =& $db->get_table($table);
      if (!($mdl->can_write_fields( $fieldlist )))
        trigger_error( "Sorry, you do not have permission to " . $req->action . " " . $table, E_USER_ERROR );
      
      if (!(in_array( $table, array('entries',$rec->table), true ))) {
        $rel = $rec->FirstChild( $table );
        foreach ($fieldlist as $field=>$type)
          $rel->set_value( $field, $req->params[strtolower(classify($table))][$field] );
        $rel->save_changes();
      }
      
    }
    
    if ($result) {
      $req->set_param( 'id', $rec->id );
      
      if ( $this->has_metadata ) {
        $atomentry->set_value( 'last_modified', timestamp() );
        $atomentry->save_changes();
      }
      
    } else {
      trigger_error( "The record could not be updated in the database.", E_USER_ERROR );
    }
    
    trigger_after( 'update_from_post', $this, $rec );
    
  }
  
  function delete_from_post( &$req ) {
    
    trigger_before( 'delete_from_post', $this, $req );
    
    global $db;
    
    if ($this->has_metadata && !(isset($req->params['entry']['etag'])))
        trigger_error( "Sorry, the etag was not submitted with the database entry", E_USER_ERROR );
    
    $fields = $this->fields_from_request($req);
    
    if ($this->has_metadata) {
      $atomentry = $db->models['entries']->find_by( 'etag', $req->params['entry']['etag'] );
      $recid = $atomentry->attributes['record_id'];
    } else {
      $recid = $req->id;
    }
    
    $rec = $this->find( $recid );
    
    if ($this->has_metadata) {
      $Person =& $db->model('Person');
      $Group =& $db->model('Group');
      $p = $Person->find( get_person_id() );
      if (!($p->id == $atomentry->attributes['person_id']) && !$this->can_superuser($req->resource))
        trigger_error( "Sorry, your id does not match the owner of the database entry", E_USER_ERROR );
    }
    
    $coll = environment('collection_cache');
    
    if ($this->has_metadata && isset($coll[$req->resource]) && $coll[$req->resource]['location'] == 'aws') {
      $ext = extension_for($atomentry->content_type);
      $pkname = $rec->primary_key;
      global $prefix;
      $aws_file = $prefix.$rec->table . $rec->$pkname . "." . $ext;
      lib_include( 'S3' );
      $s3 = new S3( environment('awsAccessKey'), environment('awsSecretKey') );
      if (!$s3)
        trigger_error( 'Sorry, there was a problem connecting to Amazon Web Services', E_USER_ERROR );
      if ($s3->getBucket(environment('awsBucket'))
      && $s3->getObject(environment('awsBucket'),urlencode($aws_file))) {
        $result = $s3->deleteObject(environment('awsBucket'), urlencode($aws_file));
        if (!$result) 
          trigger_error( 'Sorry, there was a problem deleting the file from Amazon Web Services', E_USER_ERROR );
      }
    }
    
    $result = $db->delete_record($rec);
    
    trigger_after( 'delete_from_post', $this, $req );
    
  }
  
  function fields_from_request( &$req ) {
    trigger_before('fields_from_request',$this,$this);
    global $db;
    $fields = array();
    $obj = strtolower(classify($this->table));
    foreach ($this->field_array as $fieldname=>$datatypename) {
      if (isset($req->params[$obj][$fieldname])) {
        $fields[$this->table][$fieldname] = $datatypename;
      }
    }
    foreach ($this->field_array as $fieldname=>$datatypename) {
      if (isset($_FILES[$obj]['name'][$fieldname])) {
        $fields[$this->table][$fieldname] = $datatypename;
        $req->params[$obj][$fieldname] = $_FILES[$obj]['tmp_name'][$fieldname];
      }
    }
    foreach ($this->relations as $table=>$vals) {
      if ( isset( $db->models[$table] )) {
        $obj = strtolower(classify($table));
        foreach ( $db->models[$table]->field_array as $fieldname=>$datatypename ) {
          if (!($table == 'entries') && isset($req->params[$obj][$fieldname]))
            $fields[$table][$fieldname] = $datatypename;
        }
        foreach ( $db->models[$table]->field_array as $fieldname=>$datatypename ) {
          if (!($table == 'entries') && isset($_FILES[$obj]['name'][$fieldname])){
            $fields[$table][$fieldname] = $datatypename;
            $req->params[$obj][$fieldname] = $_FILES[$obj]['tmp_name'][$fieldname];
          }
        }
      }
    }
    return $fields;
  }
  
  function db_field( $field, $alias ) {
    trigger_before('db_field',$this,$this);
    if (!(isset($this->$alias) && isset($this->data_array[$field])))
      $this->$alias =& $this->data_array[$field];
  }
  
  function exists() {
    return ( count( $this->field_array ) > 0 );
  }

  function base() {
    trigger_before('base',$this,$this);
    global $db;
    return new Record($this->table,$db);
  }
  
  function register( $table ) {
    trigger_before('register',$this,$this);
    global $db;
    if (!(isset($this->table)))
      $this->table = $table;
    if (!(isset($this->access_list)))
      $this->access_list = array();
    if (!(isset($this->relations)))
      $this->relations = array();
    if (!(isset($this->allowed_methods)))
      $this->allowed_methods = array( 'get', 'post', 'put', 'delete' );
    if (!(isset($this->field_array)))
      $this->field_array = $db->get_fields( $this->table );
    if ( array_key_exists( 'entry_id', $this->field_array ))
      $this->has_metadata = true;
    else
      $this->has_metadata = false;
    $this->hidden = false;
    if ( count( $this->field_array ) > 0 )
      $this->exists = true;
    else
      $this->exists = false;
    if (isset($this->field_array[$this->table."_primary_key"])) {
      $this->set_primary_key( $this->field_array[$this->table."_primary_key"] );
      $this->field_array = drop_array_element($this->field_array,$this->table."_primary_key");
    }
    
    if (!(in_array($this->table,array('db_sessions'))))
      $this->save();
    
    foreach ($this->relations as $table=>$vals) {
      //$this->field_array[$field]
      $custom_class = classify($table);
      if (class_exists($custom_class)) {
        if ($table != 'entries') {
          if (isset($db->tables[$table]))
            $obj =& $db->tables[$table];
          else
            $obj = new $custom_class();
          if ( array_key_exists( 'target_id', $obj->field_array )) {
            if ( array_key_exists( 'entry_id', $this->field_array )) {
              $k = 'entry_id';
              $fk = $table.'.target_id';
              $this->relations[$table]['col'] = $k;
              $this->relations[$table]['fkey'] = $fk;
            }
          }
        }
      }
    }
  
  }
  
  function class_init() {
    trigger_before('class_init',$this,$this);
    if (method_exists( $this, 'init' ))
      $this->init();
  }
  
  function set_field( $field, $data_type, $arr=NULL ) {
    $this->field_array[$field] = $data_type;
    if (!($arr == NULL))
      $this->field_attributes( $field, $arr );
  }
  
  function set_uri_key( $field ) {
    $this->uri_key = $field;
  }
  
  function set_primary_key( $field ) {
    trigger_before('set_primary_key',$this,$this);
    $this->primary_key = $field;
    if (!$this->uri_key)
      $this->uri_key = $field;
  }
  
  function field_attributes( $field, $arr ) {
    $this->set_attribute( $arr, $field );
  }
  
  function let_access( $fields ) {
    trigger_before('let_access',$this,$this);
    $this->let_read( $fields );
    $this->let_write( $fields );
    $this->let_create( $fields );
    $this->let_delete( $fields );
    $this->let_superuser( $fields );
  }
  
  function let_read( $fields ) {
    trigger_before('let_read',$this,$this);
    $args = explode( " ", $fields );
    if (!(count($args)>0)) trigger_error( "invalid data model access rule", E_USER_ERROR );
    foreach ( $args as $str) {
      $pair = split( ":", $str );
      if (!(count($pair)==2)) trigger_error( "invalid data model access rule", E_USER_ERROR );
      if ($pair[0] == 'all') {
        foreach ( $this->field_array as $field => $data_type ) {
          if (!(in_array($pair[1],$this->access_list['read'][$field])))
            $this->access_list['read'][$field][] = $pair[1];
        }
      } else {
        if (!(in_array($pair[1],$this->access_list['read'][$pair[0]])))
          $this->access_list['read'][$pair[0]][] = $pair[1];
      }
    }
  }
  
  function let_write( $fields ) {
    trigger_before('let_write',$this,$this);
    $args = explode( " ", $fields );
    if (!(count($args)>0)) trigger_error( "invalid data model access rule", E_USER_ERROR );
    foreach ( $args as $str) {
      $pair = split( ":", $str );
      if (!(count($pair)==2)) trigger_error( "invalid data model access rule", E_USER_ERROR );
      if ($pair[0] == 'all') {
        foreach ( $this->field_array as $field => $data_type ) {
          if (!(in_array($pair[1],$this->access_list['write'][$field])))
            $this->access_list['write'][$field][] = $pair[1];
        }
      } else {
        if (!(in_array($pair[1],$this->access_list['write'][$pair[0]])))
          $this->access_list['write'][$pair[0]][] = $pair[1];
      }
    }
  }
  
  function let_create( $fields ) {
    trigger_before('let_create',$this,$this);
    $args = explode( " ", $fields );
    if (!(count($args)>0)) trigger_error( "invalid data model access rule", E_USER_ERROR );
    $this->let_write( $fields );
    foreach ( $args as $str) {
      $pair = split( ":", $str );
      if (!(count($pair)==2)) trigger_error( "invalid data model access rule", E_USER_ERROR );
      if (!(isset($this->access_list['create'][$this->table][$pair[1]])))
        $this->access_list['create'][$this->table][] = $pair[1];
    }
  }

  function let_post( $fields ) {
    trigger_before('let_post',$this,$this);
    $this->let_create( $fields );
  }
  
  function let_modify( $fields ) {
    trigger_before('let_modify',$this,$this);
    $this->let_write( $fields );
  }
  
  function let_put( $fields ) {
    trigger_before('let_put',$this,$this);
    $this->let_write( $fields );
  }
  
  function let_delete( $fields ) {
    trigger_before('let_delete',$this,$this);
    $args = explode( " ", $fields );
    if (!(count($args)>0)) trigger_error( "invalid data model access rule", E_USER_ERROR );
    foreach ( $args as $str) {
      $pair = split( ":", $str );
      if (!(count($pair)==2)) trigger_error( "invalid data model access rule", E_USER_ERROR );
      if (!(isset($this->access_list['delete'][$this->table][$pair[1]])))
        $this->access_list['delete'][$this->table][] = $pair[1];
    }
  }

  function let_superuser( $fields ) {
    trigger_before('let_superuser',$this,$this);
    $args = explode( " ", $fields );
    if (!(count($args)>0)) trigger_error( "invalid data model access rule", E_USER_ERROR );
    foreach ( $args as $str) {
      $pair = split( ":", $str );
      if (!(count($pair)==2)) trigger_error( "invalid data model access rule", E_USER_ERROR );
      if (!(isset($this->access_list['superuser'][$this->table][$pair[1]])))
        $this->access_list['superuser'][$this->table][] = $pair[1];
    }
  }

  function can($action) {
    if (in_array($action,array('read','write'))) {
      $func = "can_".$action."_fields";
      if (!($this->$func($this->field_array)))
        return false;
      return true;
    }
    if (in_array($action,array('create','delete'))) {
      $func = "can_".$action;
      if (!($this->$func($this->table)))
        return false;
      return true;
    }
  }

  // config.perms
  
  function permission_mask( $perm,$value,$group ) {
    
    if (in_array($perm,array('read','write'))) {
      foreach($this->access_list[$perm] as $field=>$vals) {
        $found = false;
        foreach($vals as $idx=>$g) {
          if ($group == $g) {
            if (!$value)
              unset($this->access_list[$perm][$field][$idx]);
            $found = true;
          }
        }
        if (!$found && $value) {
          foreach ( $this->field_array as $field => $data_type ) {
            $this->access_list[$perm][$field][] = $group;
          }
        }
      }
    } else {
      
      foreach($this->access_list[$perm][$this->table] as $idx=>$g) {
        $found = false;
        if ($group == $g) {
          $found = true;
          if (!$value)
            unset($this->access_list[$perm][$this->table][$idx]);
        }
        if (!$found && $value)
          $this->access_list[$perm][$this->table][] = $group;
      }      
      
    }
    
  }
  
  function set_action( $method ) {
    trigger_before('set_action',$this,$this);
    $this->allowed_methods[] = $method;
  }
    
  function set_param( $param, $value ) {
    $this->$param = $value;
  }
  
  function set_blob( $value ) {
    $this->blob = $value;
  }
  
  function is_allowed( $method ) {
    trigger_before('is_allowed',$this,$this);
    return in_array( $method, $this->allowed_methods, true );
    return false;
  }

  function has_and_belongs_to_many( $relstring ) {
    $this->set_relation( $relstring, 'child-many' );
  }

  function belongs_to( $relstring ) {
    $this->set_relation( $relstring, 'child-one' );
  }
  
  function has_many( $relstring ) {
    $this->set_relation( $relstring, 'parent-many' );
  }
  
  function has_one( $relstring ) {
    $this->set_relation( $relstring, 'parent-one' );
  }
  
  function validates_presence_of( $field ) {
    $this->set_attribute( 'required', $field );
  }
  
  function validates_uniqueness_of( $field ) {
    $this->set_attribute( 'unique', $field );
  }
  
  function is_unique_value( $value, $field ) {
    global $db;
    $value = $db->escape_string($value);
    $result = $db->get_result("select $field from ".$db->prefix.$this->table." where ".$field." = '$value'");
    return (!($result && $db->num_rows($result) > 0));
  }
  
  function set_attribute( $attr, $field ) {
    if (is_array($attr))
      $this->field_attrs[$field]['values'] = $attr;
    else
      $this->field_attrs[$field][$attr] = true;
  }
  
  function set_hidden() {
    $this->hidden = true;
  }
  
  function foreign_key_for( $table ) {
    trigger_before('foreign_key_for',$this,$this);
    global $db;
    if (!(isset($db->models[$table]))) {
      $fields = $db->get_fields( $table );
      if (isset($fields[$table."_primary_key"]))
        $pk = $fields[$table."_primary_key"];
      else
        $pk = 'id';
    } else {
      $fields =& $db->models[$table]->field_array;
      $pk = $db->models[$table]->primary_key;
    }
    if (array_key_exists(
        strtolower(classify($table))."_".$pk, $this->field_array )) {
      return $pk; 
    } elseif ( array_key_exists(
        strtolower(classify($this->table))."_".$this->primary_key, $fields )) {
      return strtolower(classify($this->table))."_".$this->primary_key;
    } else {
      return $pk;
    }
  }
  
  function join_table_for( $t1, $t2 ) {
    trigger_before('join_table_for',$this,$this);
    if ($t1 < $t2)
      return $t1 . "_" . $t2;
    else
      return $t2 . "_" . $t1;
  }

  
  function set_relation( $relstring, $type ) {
    //echo $this->table . " " .$relstring."<BR>";
    if (!(isset($this->table)))
      $this->table = tableize( get_class( $this ));
    global $db;
    $f = split( ":", $relstring );
    if (count($f)==2) {
      $k = $f[0];
      $fk = $f[1];
    } else {
      if ( array_key_exists( $relstring.'_id', $this->field_array ))
        $k = $relstring.'_id';
      else
        $k = $this->primary_key;
      $fk = $relstring;
    }
    $fo = split("\.",$fk);
    if (count($fo)==2) {
      $table = tableize($fo[0]);
      $fkk = $fo[1];
    } else {
      $table = tableize($fk);
      $fk = $table.".".$this->foreign_key_for( $table );
    }
    if ($type == 'child-many') {
      $jtab = $this->join_table_for($table, $this->table);
      if (!(isset($db->tables)))
        $db->tables = $db->get_tables();
      if ( !( in_array( $db->prefix.$jtab, $db->tables ) ) ) {
        $join =& $db->get_table($this->join_table_for($table, $this->table));
        if (!($join->exists)) {
          $join->int_field( strtolower(classify($this->table))."_".$k );
          $join->int_field( strtolower(classify($table))."_".$this->foreign_key_for( $table) );
          $join->save();
        }
      }
    }
    if (!(isset($this->relations[$table]))) {
      $this->relations[$table]['type'] = $type;
      $this->relations[$table]['col'] = $k;
      $this->relations[$table]['fkey'] = $fk;
      $this->relations[$table]['tab'] = $table;
    }
  }
  
  function can_write_fields( $fields ) {
    $return = false;
    foreach( $fields as $key=>$val ) {
      if ( $this->can_write( $key ) ) {
        $return = true;
      } else {
        return false;
      }
    }
    return $return;
  }
  
  function can_read_fields( $fields ) {
    $return = false;
    // array of field=>datatype
    foreach( $fields as $key=>$val ) {
      if ( $this->can_read( $key ) ) {
        $return = true;
      } else {
        return false;
      }
    }
    return $return;
  }
  
  
  
  function can_read( $resource ) {
    if (!(isset($this->access_list['read'][$resource]))) return false;
    foreach ( $this->access_list['read'][$resource] as $callback ) {
      if ( function_exists( $callback ) ) {
        if ($callback())
          return true;
      } else {
        if ( member_of( $callback ))
          return true;
      }
    }
    return false;
  }
  
  function can_write( $resource ) {
    if (!(isset($this->access_list['write'][$resource]))) return false;
    foreach ( $this->access_list['write'][$resource] as $callback ) {
      if ( function_exists( $callback ) ) {
        if ($callback())
          return true;
      } else {
        if ( member_of( $callback ))
          return true;
      }
    }
    return false;
  }

  function can_create( $resource ) {
    if (!(isset($this->access_list['create'][$resource]))) return false;
    foreach ( $this->access_list['create'][$resource] as $callback ) {
      if ( function_exists( $callback ) ) {
        if ($callback())
          return true;
      } else {
        if ( member_of( $callback ))
          return true;
      }
    }
    return false;
  }

  function can_delete( $resource ) {
    if (!(isset($this->access_list['delete'][$resource]))) return false;
    foreach ( $this->access_list['delete'][$resource] as $callback ) {
      if ( function_exists( $callback ) ) {
        if ($callback())
          return true;
      } else {
        if ( member_of( $callback ))
          return true;
      }
    }
    return false;
  }

  function can_superuser( $resource ) {
    if (!(isset($this->access_list['superuser'][$resource]))) return false;
    foreach ( $this->access_list['superuser'][$resource] as $callback ) {
      if ( function_exists( $callback ) ) {
        if ($callback())
          return true;
      } else {
        if ( member_of( $callback ))
          return true;
      }
    }
    return false;
  }

  function rewind() {
    $this->MoveFirst();
  }

  function set_routes($table) {
    trigger_before('set_routes',$this,$this);
    global $request;
    if (empty($table))
      trigger_error( 'no table name when creating a route in set_routes', E_USER_ERROR );
    $request->connect(
      $table,
      array(
        'requirements' => array ( '[a-z]+' ),
        'resource' => $table
      )
    );
    if ($request->resource == $table && $request->id > 1)
    $request->connect(
      classify($table),
      array(
        'requirements' => array ( '[a-z]+', '[0-9]+' ),
        'resource' => $table,
        'id' => $request->id
      )
    );
  }
  
  function save() {
    
    trigger_before('save',$this,$this);
    global $db;
    trigger_before( 'save', $this, $db );
    
    if (!(isset($this->table)))
      $this->table = tableize( get_class( $this ));
    
    if ($this->table == 'models')
      return;
    
    if (!(isset($db->tables)))
      $db->tables = $db->get_tables();

    if ( !( in_array( $db->prefix.$this->table, $db->tables ) ) ) {
      if (count($this->field_array)>0) {
        if (!(isset($this->primary_key)))
          $this->auto_field( 'id' );
        $db->add_table( $this->table, $this->field_array );
        $this->class_init();
      } else {
        return NULL;
      }
    }
    if ( !( isset( $this->primary_key )) && (!strstr($this->table,'db_sessions')))
      trigger_error("The ".$this->table." table must have a primary key. Example: ".$this->table."->set_primary_key('field')".@mysql_error($this->conn), E_USER_ERROR );
    $this->exists = true;
    $this->set_routes( $this->table );
    trigger_after( 'save', $this, $db );
  }
  
  function migrate() {
    
    // schema sync
    
    global $db;
    
    if (empty($this->table))
      return;
    
    if (!(isset($db->tables)))
      $db->tables = $db->get_tables();

    if ( ( in_array( $this->table, $db->tables ) ) ) {
    
    $fields = $db->get_fields( $this->table );
    foreach ( $this->field_array as $field => $data_type ) {
      if ( !( array_key_exists( $field, $fields ) ) ) {
        $db->add_field( $this->table, $field, $data_type );
      }
    }
  }
    #if ( !( isset( $this->primary_key ))) {
    # if (isset($fields[$this->table."_primary_key"]))
    #    $this->set_primary_key( $fields[$this->table."_primary_key"] );
    #}
    #foreach ( $fields as $field => $type ) {
    #  if ( !( array_key_exists( $field, $this->field_array ) ) ) {
    #    if ( !( $this->table."_primary_key" == $field ) ) {
    #      $this->set_field( $field, $type );
    #    }
    #  }
    #}

    
    
  }
  
  function is_blob($field) {
    trigger_before('is_blob',$this,$this);
    global $db;
    return ( $db->get_mapped_datatype( $this->field_array[$field] ) === 'blob' );
  }
  
  function find( $id=NULL, $find_by=NULL ) {
    trigger_before('find',$this,$this);
    
    global $db;
    global $request;
    
    trigger_before( 'find', $this, $db );
    
    if (isset($this->find_by) && $find_by == NULL)
      $find_by = $this->find_by;
    
    if (isset($this->id) && $id == NULL)
      $find_by = $this->find_by;
    
    if ($id != NULL)
      $id = $db->escape_string($id);

    if ($find_by != NULL)
      foreach ($find_by as $k=>$v)
        $v = $db->escape_string($v);
    
    // special index-find subselect behavior for (metadata) tables (tables with a target_id field)
    if (( strstr( $request->action, "index" )) && array_key_exists( 'target_id', $this->field_array ))
      $find_by = 'target_id';
    
    $db->recordsets[$this->table] = $db->get_recordset($this->get_query($id, $find_by));
    $rs =& $db->recordsets[$this->table];
    
    unset($this->find_by);
    unset($this->id);
    
    if (!$rs) return false;
    if ( $id != NULL && $rs->rowcount > 0 )
      if ( $find_by != NULL )
        return $rs->Load( $this->table, 0 );
      else
        return $rs->Load( $this->table, $rs->rowmap[$this->table][$id] );
    trigger_after( 'find', $this, $db );
    return false;
  }
  
  function find_by( $col, $val = 1 ) {
    trigger_before('find_by',$this,$this);
    return $this->find( $val, $col );
  }
  
  function MoveFirst() {
    trigger_before('MoveFirst',$this,$this);
    global $db;
    if (!(isset($db->recordsets[$this->table])))
      $this->find();
    $rs =& $db->recordsets[$this->table];
    if (!$rs) return false;
    return $rs->MoveFirst( $this->table );
  }
  
  function MoveNext() {
    trigger_before('MoveNext',$this,$this);
    global $db;
    if (!(isset($db->recordsets[$this->table])))
      $this->find();
    $rs =& $db->recordsets[$this->table];
    if (!$rs) return false;
    return $rs->MoveNext( $this->table );
  }
  
  function session_exists() {
    if (isset($_SESSION[$this->table."_submission"]))
      return true;
    return false;
  }
  
  function set_limit( $limit ) {
    if ( $limit > 0 ) $this->limit = $limit;
  }
  
  function set_offset( $offset ) {
    if ( $offset > 0 ) $this->offset = $offset;
  }
  
  function set_groupby( $col ) {
    global $db;
    if ( strlen( $col ) > 0 ) $this->groupby = $db->prefix.$this->table . "." . $col;
  }
  
  function set_orderby( $col ) {
    global $db;
    if ( strlen( $col ) > 0 ) $this->orderby = $db->prefix.$this->table . "." . $col;
  }
  
  function set_order( $order ) {
    if ( strlen( $order ) > 0 ) $this->order = $order;
  }
  
  function rowcount() {
    global $db;
    $rs =& $db->recordsets[$this->table];
    if (!$rs) return 0;
    return $rs->num_rows( $this->table );
  }
  
  function auto_field( $field, $options = null ) {
    global $db;
    $db->auto_field( $field, $this, $options );
  }
  
  function enum_field( $field, $options = null ) {
    global $db;
    $db->enum_field( $field, $this, $options );
  }
  
  function float_field( $field, $options = null ) {
    global $db;
    $db->float_field( $field, $this, $options  );
  }
  
  function bool_field( $field, $options = null ) {
    global $db;
    $db->bool_field( $field, $this, $options  );
  }
  
  function char_field( $field, $options = null ) {
    global $db;
    $db->char_field( $field, $this, $options );
  }
  
  function date_field( $field, $options = null ) {
    global $db;
    $db->date_field( $field, $this, $options  );
  }
  
  function file_field( $field, $options = null ) {
    global $db;
    $db->file_field( $field, $this, $options  );
  }
  
  function int_field( $field, $options = null ) {
    global $db;
    $db->int_field( $field, $this, $options );
  }
  
  function text_field( $field, $options = null ) {
    global $db;
    $db->text_field( $field, $this, $options  );
  }
  
  function time_field( $field, $options = null ) {
    global $db;
    $db->time_field( $field, $this, $options  );
  }
  
  function get_query( $id=NULL, $find_by=NULL ) {
    global $db;
    return $db->get_query( $id, $find_by, $this );
  }
  
}

?>