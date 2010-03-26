<?php

  /** 
   * dbscript -- restful openid framework
   * @version 0.6.0 -- 22-October-2008
   * @author Brian Hendrickson <brian@dbscript.net>
   * @link http://dbscript.net/
   * @copyright Copyright 2009 Brian Hendrickson
   * @license http://www.opensource.org/licenses/mit-license.php MIT License
   * @package dbscript
   */

  /**
   * PostgreSQL
   * 
   * adapter for the PostgreSQL database system
   * 
   * Usage:
   * <code>
   * $db = new PostgreSQL ( 'hostname', 'database_name', 'username', 'password' );
   * </code>
   * 
   * More info...
   * {@link http://dbscript.net/postgresql}
   * 
   * @package dbscript
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @version 0.6.0 -- 22-October-2008
   * @todo support array datatypes
   */

class PostgreSQL extends Database {
  var $connstr;
  var $oid;
  var $host;
  var $user;
  var $pass;
  var $dbname;
  var $prefix;
  function PostgreSQL() {
    global $prefix; 
    $prefix = ''; 
    $this->prefix = $prefix; 
    $this->db_open = false;
    $this->models = array();
    $this->recordsets = array();
    $this->max_blob_length = 6144000;  // default max blob file size is 6MB
    $this->max_string_length = 1024000;  // default max string length is 1MB
    $this->datatype_map = array(
      
      'real' => 'float',
      'double precision' => 'float',

      'int' => 'int',      
      'integer' => 'int',
      'smallint' => 'int',
      'bigint' => 'int',
      'serial' => 'int',
      'serial primary key' => 'int',
      'bigserial' => 'int',
      'numeric' => 'int',

      'text' => 'text',

      'char' => 'char',
      'varchar' => 'char',
      'character' => 'char',
      'character varying' => 'char',

      'timestamp' => 'time',
      'timestamp without time zone' => 'time',
      'timestamp with time zone' => 'time',
      'time' => 'time',
      'time without time zone' => 'time',
      'time with time zone' => 'time',
      
      'date' => 'date',

      'boolean' => 'bool',
      
      'oid' => 'blob'

    );
    $func_args = func_get_args();
    $argnames = array('host','dbname','user','password','port');
    $this->true_values = array('t','true','1',true); 
    $this->alias_array = array();
    for ($i = 0; $i < count($argnames); $i++) {
      if (isset($func_args[$i]))
        $this->$argnames[$i] = $func_args[$i];
      else
        $this->$argnames[$i] = "";
    }
    for ($i = 0; $i < count($func_args); $i++) {
      if (strlen($func_args[$i]) > 0)
        $this->connstr .= $argnames[$i] . '=' . $func_args[$i] . ' ';
    }
    $this->connect();
  }
  function connect() { // establish a connection to the database
    trigger_before( 'connect', $this, $this );
    $this->conn = @pg_connect($this->connstr);
    if (!$this->conn) {
      $this->db_open = false;
      trigger_error("Sorry, the database connection failed. Please check your database connection settings.".@pg_last_error($this->conn), E_USER_ERROR );
    } else {
      $this->db_open = true;
    }
    return $this->db_open;
  }
  function escape_string($string) {
    trigger_before( 'escape_string', $this, $this );
    if (!(strlen($string) > 0)) { return ""; }
    $result = @pg_escape_string($string);
    if (!$result && !(is_numeric($string)))
      trigger_error("error in escape_string in postgresql.php".@pg_last_error($this->conn), E_USER_ERROR );
    return $result;
  }
  function get_result( $sql, $returnfalse = NULL ) { /* run an SQL query */
    trigger_before( 'get_result', $this, $this );
    global $request;
    if (isset($request->params)) {
      trigger_before( 'get_result', $request, $this );
    }
    $result = @pg_query( $this->conn, $sql );
    if (!$result && $returnfalse === NULL)
      trigger_error("error in get_result in postgresql.php".@pg_last_error($this->conn)." ".$sql, E_USER_ERROR );
    elseif (!$result && $returnfalse)
      return true;
    else
      return $result;
  }
  function next_primary_key( $table, $pkfield, $sequence_name=NULL ) {
    trigger_before( 'next_primary_key', $this, $this );
    global $prefix;
    if ($sequence_name == NULL) {
      $sql = "SELECT relname FROM pg_class WHERE relkind='S' and substr(relname,1,".strlen($prefix.$table).")='".$prefix."$table'";
      $result = $this->get_result($sql);
      if ($this->num_rows($result) > 0) {
        $seq = $this->result_value($result,0,"relname");
      } else {
        return '';
      }
    } else {
      $seq = $sequence_name;
    }
    $pk_result = $this->get_result("SELECT nextval('$seq')");
    if ($this->num_rows($result) > 0)
      $pkvalue = $this->result_value( $pk_result, 0, "nextval" );
    else
      trigger_error("error selecting nexval in next_primary_key in postgresql.php".@pg_last_error($this->conn), E_USER_ERROR );
    return $pkvalue;
  }
  function last_insert_id(&$result,$pkfield,$table) { // returns the id of the most recently modified record
    trigger_before( 'last_insert_id', $this, $this );
    global $prefix;
    $oid = @pg_last_oid($result);
    if (!$oid)
      trigger_error(@pg_last_error($this->conn), E_USER_ERROR );
    $sql = "SELECT ". $pkfield . " FROM " . $prefix.$table . " WHERE oid = " . $oid;
    $res = $this->get_result($sql);
    if (!$res)
      trigger_error("error in last_insert_id in postgresql.php".@pg_last_error($this->conn), E_USER_ERROR );
    else
      return $this->result_value($res,0,$pkfield);
  }
  function result_value(&$result,$resultindex,$field) { // get a single value from a result set
    trigger_before( 'result_value', $this, $this );
    $return = pg_fetch_result($result,$resultindex,$field);
    if (!$return && $return != 0)
      trigger_error("error in result_value in postgresql.php".@pg_last_error($this->conn), E_USER_ERROR );
    else
      return $return;
  }
  function close() {
    trigger_before( 'close', $this, $this );
    $args = func_get_args();
    pg_close( $this->conn );
    if ( isset( $args[0] ) ) {
      if ( strlen($args[0]) > 0 ) {
        header( "Location:" . $args[0] );
        exit;
      }
    }
  }
  function &get_table($table) {
    trigger_before( 'get_table', $this, $this );
    if ( isset( $this->models[$table] ) )
      if ($this->models[$table]->exists)
        return $this->models[$table];
    $custom_class = classify($table);
    if (!isset($this->models[$table]) && class_exists($custom_class)) {
      $this->models[$table] = new $custom_class();
    } elseif (!isset($this->models[$table])) {
      $this->models[$table] = new Model($table, $this);
    }
    if (!($this->models[$table]->exists))
      $this->models[$table]->register($table);
    return $this->models[$table];
  }
  function &model($model) {
    trigger_before( 'model', $this, $this );
    return $this->get_table(tableize($model));
  }
  function fetch_array( &$result, $row=NULL ) {
    trigger_before( 'fetch_array', $this, $this );
    if (is_numeric($row))
      $this->seek_row( $result, $row );
    return pg_fetch_array( $result, $row, PGSQL_ASSOC );
  }
  function fetch_row( &$result, $row=NULL ) {
    trigger_before( 'fetch_row', $this, $this );
    if ( ( is_numeric( $row ) ) )
      return pg_fetch_row( $result, $row );
    return pg_fetch_row( $result );
  }
  function seek_row(&$result,$row) {
    trigger_before( 'seek_row', $this, $this );
    return true;
  }
  function query_limit($limit,$offset) {
    trigger_before( 'query_limit', $this, $this );
    return " LIMIT " . $limit . " OFFSET " . $offset;
  }
  function blob_value( &$rec, $field, &$value ) {
    trigger_before( 'blob_value', $this, $this );
    return $value;
  }
  function sql_insert_for( &$rec ) {
    trigger_before( 'sql_insert_for', $this, $this );
    $sql = "INSERT INTO " . $this->prefix.$rec->table . " (";
    $comma = '';
    $fields = '';
    $values = '';
    foreach (array_unique($rec->modified_fields) as $modified_field) {
      $datatype = $this->get_mapped_datatype($this->models[$rec->table]->field_array[$modified_field]);
      $this->pre_insert( $rec, $modified_field, $datatype );
      if ( !( $datatype == 'blob' &&  ( !(strlen( $rec->attributes[$modified_field] ) > 0 ) ) ) ) {
        $fields .= $comma . $modified_field;
        $values .= $comma . $this->quoted_insert_value( $rec, $modified_field );;
        $comma = ',';
      }
    }
    $sql .= $fields . ") VALUES (" . $values . ")";

    return $sql;
  }
  function sql_update_for( &$rec ) {
    trigger_before( 'sql_update_for', $this, $this );
    $sql = "UPDATE ";
    $sql .= $this->prefix.$rec->table . ' SET ';
    $comma = '';
    foreach (array_unique($rec->modified_fields) as $modified_field) {
      $datatype = $this->get_mapped_datatype($this->models[$rec->table]->field_array[$modified_field]);
      $this->pre_update( $rec, $modified_field, $datatype );
      if ( !( $datatype == 'blob' &&  ( !(strlen( $rec->attributes[$modified_field] ) > 0 ) ) ) ) {
        $sql .= $comma . $this->quoted_update_value( $rec, $modified_field );
        $comma = ',';
      }
    }
    $sql .= " WHERE " . $rec->primary_key . "='" . $rec->attributes[$rec->primary_key] . "'";
    return $sql;
  }
  function sql_select_for( &$rec, $id ) {
    trigger_before( 'sql_select_for', $this, $this );
    return "SELECT ".$rec->selecttext." FROM ".$this->prefix.$rec->table." WHERE ".$rec->primary_key." = '".$id."'";
  }
  function sql_delete_for( &$rec ) {
    trigger_before( 'sql_delete_for', $this, $this );
    $pkfield = $rec->primary_key;
    foreach ($rec->attributes as $key=>$value) {
      $datatype = $this->get_mapped_datatype($this->models[$rec->table]->field_array[$key]);
      if ($datatype == 'blob' && strlen($rec->attributes[$rec->primary_key]) > 0) {
        $oid_result = $this->get_result("select ".$key." from ".$this->prefix.$rec->table." where ".$rec->primary_key." = '".$rec->attributes[$rec->primary_key]."'");
        $prev_oid = $this->fetch_array($oid_result,0,$key);
        if (isset($prev_oid[0]) && $prev_oid[0] > 0)
          $result = $this->large_object_delete($prev_oid[0]);
      }
    }
    $sql = 'DELETE FROM ' . $this->prefix.$rec->table . ' WHERE ' . $pkfield . ' = ' . $rec->$pkfield;
    return $sql;
  }
  function select_distinct( $field, $table, $orderby ) {
    trigger_before( 'select_distinct', $this, $this );
    return "SELECT DISTINCT $field, " . $this->models[$table]->primary_key . " FROM $this->prefix.$table ORDER BY $orderby DESC";
  }
  function quoted_update_value( &$rec, $modified_field ) {
    trigger_before( 'quoted_update_value', $this, $this );
    return $modified_field . "='" . $this->escape_string($rec->attributes[$modified_field]) . "'";
  }
  function quoted_insert_value( &$rec, $modified_field ) {
    trigger_before( 'quoted_insert_value', $this, $this );
    return "'" . $this->escape_string($rec->attributes[$modified_field]) . "'";
  }
  function pre_insert( &$rec, $modified_field, $datatype ) {
    trigger_before( 'pre_insert', $rec, $this );
    if (isset($this->models[$rec->table]->field_attrs[$modified_field]['required'])) {
      if (!(strlen( $rec->attributes[$modified_field] ) > 0))
        trigger_error( "$modified_field is a required field", E_USER_ERROR );
    }
    if (isset($this->models[$rec->table]->field_attrs[$modified_field]['unique'])) {
      $result = $this->get_result("select ".$modified_field." from ".$this->prefix.$rec->table." where ".$modified_field." = '".$rec->attributes[$modified_field]."'");
      if ($result && $this->num_rows($result) > 0)
        trigger_error( "Sorry, that $modified_field has already been taken.", E_USER_ERROR );
    }
    if ($datatype == 'time' && !(strlen($rec->attributes[$modified_field]) > 0))
      $rec->attributes[$modified_field] = date("Y-m-d H:i:s",strtotime("now"));
    if ($datatype == 'blob' && strlen($rec->attributes[$modified_field]) > 0) {
      if (environment('max_upload_mb')) {
        $max = 1048576*environment('max_upload_mb');
        $size = filesize($rec->attributes[$modified_field]);
        if ($size >$max)
          trigger_error('Sorry but that file is too big, the limit is '.environment('max_upload_mb').' megabytes', E_USER_ERROR);
      }
      $coll = environment('collection_cache');
      if (isset($coll[$request->resource]) && $coll[$request->resource]['location'] == 'aws') {
        $this->file_upload = array($modified_field,$rec->attributes[$modified_field]);
        $rec->set_value($modified_field,'');
      } elseif (isset($coll[$request->resource]) && $coll[$request->resource]['location'] == 'uploads') {
        $this->file_upload = $rec->attributes[$modified_field];
        $rec->set_value($modified_field,'');
      } else {
        $oid = $this->large_object_create($this->prefix.$rec->table,$rec->attributes[$modified_field]);
        if ($oid > 0)
          $rec->attributes[$modified_field] = $oid;
      }      
    }
    if ($datatype == 'bool') {
      if ( in_array( $rec->attributes[$modified_field], $this->true_values, true ) )
        $rec->attributes[$modified_field] = "true";
      else
        $rec->attributes[$modified_field] = "false";
    }
    if ($modified_field == $rec->primary_key) {
      if ( in_array( $rec->attributes[$rec->primary_key], array( '', 0, '0' ), true ))
        $rec->attributes[$modified_field] = $this->next_primary_key( $this->prefix.$rec->table, $modified_field);      
    }
  }
  function pre_update( &$rec, $modified_field, $datatype ) {
    trigger_before( 'pre_update', $rec, $this );
    if (isset($this->models[$rec->table]->field_attrs[$modified_field]['required'])) {
      if (!(strlen( $rec->attributes[$modified_field] ) > 0))
        trigger_error( "Sorry, you must provide a value for $modified_field", E_USER_ERROR );
    }
    if (isset($this->models[$rec->table]->field_attrs[$modified_field]['unique'])) {
      $result = $this->get_result("select ".$modified_field." from ".$this->prefix.$rec->table." where ".$modified_field." = '".$rec->attributes[$modified_field]."' and ".$rec->primary_key." != '".$rec->attributes[$rec->primary_key]."'");
      if ($this->num_rows($result) > 0)
        trigger_error( "Sorry, that $modified_field has already been taken.", E_USER_ERROR );
    }
    if ($datatype == 'blob' && (strlen( $rec->attributes[$modified_field] ) > 0 )) {
      if (environment('max_upload_mb')) {
        $max = 1048576*environment('max_upload_mb');
        $size = filesize($rec->attributes[$modified_field]);
        if ($size >$max)
          trigger_error('Sorry but that file is too big, the limit is '.environment('max_upload_mb').' megabytes', E_USER_ERROR);
      }
      global $request;
      $coll = environment('collection_cache');
      if (isset($coll[$request->resource]) && $coll[$request->resource]['location'] == 'aws') {
        $this->file_upload = array($modified_field,$rec->attributes[$modified_field]);
        $this->aws_delfile($rec,$rec->id);
        $this->aws_putfile($rec,$rec->id);
        $rec->set_value($modified_field,'');
      } elseif (isset($coll[$request->resource]) && $coll[$request->resource]['location'] == 'uploads') {
        update_uploadsfile($this->prefix.$rec->table,$rec->id,$rec->attributes[$modified_field]);
        $rec->set_value($modified_field,'');
      } else {
        unlink_cachefile($this->prefix.$rec->table,$rec->id,$coll);
        $oid_result = $this->get_result("select ".$modified_field." from ".$this->prefix.$rec->table." where ".$rec->primary_key." = '".$rec->attributes[$rec->primary_key]."'");
        if ($this->num_rows($oid_result) > 0) {
          $prev_oid = $this->fetch_array($oid_result);
          if (isset($prev_oid[0]) && $prev_oid[0] > 0)
            $result = $this->large_object_delete($prev_oid);
        }
        $oid = $this->large_object_create($this->prefix.$rec->table,$rec->attributes[$modified_field]);
        if ($oid > 0)
          $rec->attributes[$modified_field] = $oid;
      }
    
    }
  }
  function post_insert( &$rec, &$result ) {
    trigger_before( 'post_insert', $this, $this );
    if (is_array($this->file_upload))
      $this->aws_putfile($rec,$rec->id);
    elseif (!empty($this->file_upload))
      update_uploadsfile($this->prefix.$rec->table,$rec->id,$this->file_upload);
    if (!$result) { trigger_error("Sorry, the record could not be saved due to a database error.", E_USER_ERROR ); }
  }
  function affected_rows(&$result) {
    trigger_before( 'affected_rows', $this, $this );
    return @pg_affected_rows($result);
  }
  function fetch_object(&$result) {
    trigger_before( 'fetch_object', $this, $this );
    return @pg_fetch_object($result);
  }
  function fetch_field(&$result,$i) {
    trigger_before( 'fetch_field', $this, $this );
    $field = new dbfield();
    $field->name = pg_field_name($result,$i);
    $field->type = pg_field_type($result,$i);
    $field->size = pg_field_size($result,$i);
    return $field;
  }
  function num_rows(&$result) {
    trigger_before( 'num_rows', $this, $this );
    return @pg_num_rows($result);
  }
  function num_fields(&$result) {
    trigger_before( 'num_fields', $this, $this );
    return @pg_num_fields($result);
  }
  function field_name(&$result, $index) {
    trigger_before( 'field_name', $this, $this );
    return @pg_field_name($result, $index);
  }
  function large_object_create($table,$file) {
    trigger_before( 'large_object_create', $this, $this );
    $return = false;
    if (!(file_exists($file)))
      return $return;
    $filename = basename($file);
    if (!$filename) { trigger_error("Error determining base name of large object file $filename", E_USER_ERROR ); }
    $handle = fopen($file,"r");
    if (!$handle) { trigger_error("Error opening large object file $file", E_USER_ERROR ); }
    $buffer = fread($handle,filesize($file));
    if (!$buffer) { trigger_error("Error reading large object file $file", E_USER_ERROR ); }
    $result = fclose($handle);
    $result = @pg_query($this->conn, "BEGIN");
    if (!$result) { trigger_error("error starting l_o_c transaction: ".@pg_last_error($this->conn), E_USER_ERROR ); }
    $oid = @pg_lo_create($this->conn);
    if (!$oid) { trigger_error("error in pg_l_o_c: ".@pg_last_error($this->conn), E_USER_ERROR ); }
    #$result = pg_query($this->conn,"UPDATE $table SET $field = $oid WHERE $pkfield = '$pkvalue'");
    #if (!$result) { trigger_error("Error updating file OID", E_USER_ERROR ); }
    $handle = @pg_lo_open($this->conn, $oid, "w");
    if (!$handle) { trigger_error("error in pg_l_o_o: ".@pg_last_error($this->conn), E_USER_ERROR ); }
    $result = @pg_lo_write($handle, $buffer);
    if (!$result) { trigger_error("error in l_o_w: ".@pg_last_error($this->conn), E_USER_ERROR ); }
    $result = @pg_lo_close($handle);
    if (!$result) { trigger_error("error in l_o_close: ".@pg_last_error($this->conn), E_USER_ERROR ); }
    $result = @pg_query($this->conn, "COMMIT");
    if (!$result) { trigger_error("error committing l_o_c transaction: ".@pg_last_error($this->conn), E_USER_ERROR ); }
    else {
      $return = $oid;
    }
    return $return;
  }
  function large_object_fetch($oid, $return = false) {
    trigger_before( 'large_object_fetch', $this, $this );
    //$result = pg_query($this->conn,"SELECT $field FROM $table WHERE $");
    //if (!$result) { trigger_error("Error in select file OID", E_USER_ERROR ); }
    //$oid = pg_result($result,0,$fieldname);
    //if (!$oid) { trigger_error("Error in file OID result", E_USER_ERROR ); }
    $result = @pg_query($this->conn,"BEGIN");
    if (!$result) { trigger_error("error starting l_o_f transaction: ".@pg_last_error($this->conn), E_USER_ERROR ); }
    $handle = @pg_lo_open($this->conn, $oid, "r");
    if (!$handle) { trigger_error("error in l_o_f/l_o_o: ".@pg_last_error($this->conn), E_USER_ERROR ); }
    if ($return === true)
      return @pg_lo_read($handle,$this->max_blob_length);
    else
      @pg_lo_read_all($handle);
    if (!$buffer) { trigger_error("error in l_o_read_all: ".@pg_last_error($this->conn), E_USER_ERROR ); }
    $result = @pg_lo_close($handle);
    if (!$result) { trigger_error("error in l_o_close: ".@pg_last_error($this->conn), E_USER_ERROR ); }
    $result = @pg_query($this->conn,"COMMIT");
    if (!$result) { trigger_error("error committing l_o_f transaction: ".@pg_last_error($this->conn), E_USER_ERROR ); }
    return $return;
  }
  function large_object_delete($oid) {
    trigger_before( 'large_object_delete', $this, $this );
    $return = false;
    #$result = pg_query($this->conn,"SELECT $field FROM $table WHERE $pkfield = '$pkvalue'");
    #if (!$result) { trigger_error("Error in select file OID", E_USER_ERROR ); }
    #$oid = pg_result($result,0,$field);
    #if (!$oid) { trigger_error("Error in file OID result", E_USER_ERROR ); }
    $result = @pg_query($this->conn,"BEGIN");
    if (!$result) { trigger_error("error starting l_o_d transaction: ".@pg_last_error($this->conn), E_USER_ERROR ); }
    $result = @pg_lo_unlink($this->conn, $oid);
    if (!$result) { trigger_error("error in l_o_unlink: ".@pg_last_error($this->conn), E_USER_ERROR ); }
    $result = @pg_query($this->conn,"COMMIT");
    if (!$result) { trigger_error("error committing l_o_d transaction: ".@pg_last_error($this->conn), E_USER_ERROR ); }
    #$result = pg_query($this->conn,"DELETE FROM $table WHERE lo_oid = $oid");
    #if (!$result) { trigger_error("Error deleting file OID", E_USER_ERROR ); }
    else {
      $return = true;
    }
    return $return;
  }
  function add_table( $table, $field_array ) {
    trigger_before( 'add_table', $this, $this );
    $exists = $this->get_tables();
    if (in_array($this->prefix.$table,$exists))
      return true;
    if (!(count($field_array)>0)) trigger_error( "Error creating table. No fields are defined. Use \$model->auto_field and \$model->text_field etc.", E_USER_ERROR );
    $sql = "CREATE TABLE ". $this->prefix ."$table (";
    $comma = "";
    foreach ( $field_array as $field => $data_type ) {
      $sql .= "$comma $field $data_type";
      $comma = ",";
    }
    $sql .= ")";
    $result = $this->get_result($sql);
    if ($result)
      $this->tables[] = $table;
  }
  function add_field( $table, $field, $data_type ) {
    trigger_before( 'add_field', $this, $this );
    $sql = "ALTER TABLE " . $this->prefix ."$table ADD COLUMN $field $data_type";
    echo $sql."<br />";
    $result = $this->get_result($sql);
  }
  function has_table($t) {
    trigger_before( 'has_table', $this, $this );
    return in_array( $this->prefix.$t, $this->get_tables(), true );
  }
  function get_tables() {
    trigger_before( 'get_tables', $this, $this );
    $tables = array();
    #$sql  = "SELECT a.relname AS Name FROM pg_class a, pg_user b ";
    #$sql .= "WHERE ( relkind = 'r') and relname !~ '^pg_' AND relname !~ '^sql_' ";
    #$sql .= "AND relname !~ '^xin[vx][0-9]+' AND b.usesysid = a.relowner ";
    #$sql .= "AND NOT (EXISTS (SELECT viewname FROM pg_views WHERE viewname=a.relname))";
    $sql =  "SELECT tablename AS relname FROM pg_catalog.pg_tables";
    $sql .= " WHERE schemaname NOT IN ('pg_catalog', 'information_schema',";
    $sql .= " 'pg_toast') ORDER BY tablename";
    $result = $this->get_result($sql);
    while ($arr = $this->fetch_array($result)) {
      foreach($arr as $key=>$value) {
        if (!(in_array($value, array('db_sessions'))))
          $tables[] = $value;
      }
    }
    return $tables;
  }
  function get_fields($table) {
    trigger_before( 'get_fields', $this, $this );
    $datatypes = array();
    $fieldindex = array();
    $fieldindex[] = "";
    #$sql  = "SELECT column_name, data_type FROM information_schema.columns ";
    #$sql .= "WHERE table_schema = 'public' AND table = '$table'";
    $sql  = "SELECT a.attname, pg_catalog.format_type(a.atttypid, a.atttypmod)";
    $sql .= " as type FROM pg_catalog.pg_attribute a LEFT JOIN";
    $sql .= " pg_catalog.pg_attrdef adef ON a.attrelid=adef.adrelid AND";
    $sql .= " a.attnum=adef.adnum LEFT JOIN pg_catalog.pg_type t ON";
    $sql .= " a.atttypid=t.oid WHERE a.attrelid = (SELECT oid FROM";
    $sql .= " pg_catalog.pg_class WHERE relname='".$this->prefix."$table')";
    $sql .= " and a.attname != 'tableoid' and a.attname != 'oid'";
    $sql .= " and a.attname != 'xmax' and a.attname != 'xmin'";
    $sql .= " and a.attname != 'cmax' and a.attname != 'cmin'";
    $sql .= " and a.attname != 'ctid' and a.attname != 'otre'";
    $sql .= " and a.attname not ilike '%..%' order by a.attnum ASC";
    $result = $this->get_result($sql,true);
    if (!$result) return $datatypes;
    while ($arr = $this->fetch_array($result)) {
      foreach($arr as $key=>$value) {
        if ($key == "attname") {
          $field = $value;
          $fieldindex[] = $value;
        } elseif ($key == "type") {
          $type = $value;
        }
      }
      $datatypes[$field] = $type;
    }
    global $prefix;
    $sql = "SELECT idx.indkey, idx.indisunique, idx.indisprimary";
    $sql .= " FROM pg_catalog.pg_class c, pg_catalog.pg_class c2,";
    $sql .= " pg_catalog.pg_index idx";
    $sql .= " WHERE c.oid = idx.indrelid";
    $sql .= " AND idx.indexrelid = c2.oid";
    $sql .= " AND c.relname = '".$prefix."$table'";
    #$sql .= " AND idx.isprimary = true";
    $result = $this->get_result($sql);
    while ($row = pg_fetch_row($result)) {
      if (!(strstr($row[0], ' '))) 
        $datatypes[$table."_primary_key"] = $fieldindex[$row[0]];
    }
    return $datatypes;
  }
  
  function create_openid_tables() {
    
    if (in_array('openid_identities',$this->tables))
      return;
      
    $result = $this->get_result("CREATE TABLE openid_identities ( uurl_id int NOT NULL, user_id int NOT NULL default '0', url text, hash char(32) )");

    $result = $this->get_result("CREATE TABLE oauth_consumers (consumer_key CHAR(255) PRIMARY KEY, secret CHAR(40), description CHAR(40))");

    $result = $this->get_result("CREATE TABLE oauth_tokens (consumer_key CHAR(255), token CHAR(40), secret CHAR(40), token_type CHAR(7), nonce CHAR(40), user_id INT DEFAULT 0, expires INT DEFAULT 0)");

    $result = $this->get_result("INSERT INTO oauth_consumers (consumer_key, secret, description) VALUES ('DUMMYKEY', '', 'Unidentified Consumer')");
//;

    $result = $this->get_result("CREATE TABLE openid_nonces (server_url VARCHAR(255), timestamp INTEGER, ".
            "salt CHAR(40), UNIQUE (server_url, timestamp, salt))");

        
    $result = $this->get_result("CREATE TABLE openid_associations (server_url VARCHAR(255), handle VARCHAR(255), ".
            "secret BYTEA, issued INTEGER, lifetime INTEGER, ".
            "assoc_type VARCHAR(64), PRIMARY KEY (server_url, handle), ".
            "CONSTRAINT secret_length_constraint CHECK ".
            "(LENGTH(secret) <= 128))");
  }

  function auto_field( $field, &$model ) {
    $model->set_field( $field, "serial primary key" );
    $model->set_primary_key( $field );
  }

  function enum_field( $field, $values, &$model ) {
    $model->set_field( $field, $values );
  }

  function float_field( $field, &$model ) {
    $model->set_field( $field, "double precision" );
  }

  function bool_field( $field, &$model ) {
    $model->set_field( $field, "boolean" );
  }

  function char_field( $field, &$model, $options ) {
    if (!(isset($options['len'])))
      $options = array('len'=>255);
    $model->set_field( $field, "varchar(".$options['len'].")" );
  }

  function date_field( $field, &$model ) {
    $model->set_field( $field, "date" );
  }

  function file_field( $field, &$model ) {
    $model->set_field( $field, "oid" );
  }

  function int_field( $field, &$model ) {
    $model->set_field( $field, "int" );
  }

  function text_field( $field, &$model ) {
    $model->set_field( $field, "text" );
  }

  function time_field( $field, &$model ) {
    $model->set_field( $field, "timestamp with time zone" );
  }

  function get_query( $id=NULL, $find_by=NULL, &$model ) {
    if (isset($model->query)) {
      $q = $model->query;
      unset($model->query);
      return $q;
    }
    $model->set_param('id',$id);
    $model->set_param('find_by',$find_by);
    trigger_before( 'get_query', $model, $this );
    $pkfield = $model->primary_key;
    if ($model->find_by == NULL)
      $model->set_param('find_by', $model->primary_key);
    $relfields = array();
    $relfields = $model->relations;
    $table = $this->prefix.$model->table;
    $fieldstring = '';
    $sql = "SELECT " . "\n";
    if (!array_key_exists($pkfield,$model->field_array))
      $sql .= "$table.$pkfield as \"$table.$pkfield\", " . "\n";
    foreach ($model->field_array as $fieldname=>$datatypename) {
      if (strpos($fieldname,".") === false)
        $fieldname = $table . "." . $fieldname;
      $fieldstring .= "$fieldname as \"$fieldname\", " . "\n";
    }
    $leftsql = "";
    $first = true;
  
  
    if (count($relfields) > 0) {
      foreach ($relfields as $key=>$val) {
        $spl = split("\.",$val["fkey"]);
        if (!($this->models[$spl[0]]->exists))
          $$spl[0] =& $this->get_table($spl[0]);
        $leftsql .= "(";
      }

      foreach ($relfields as $key=>$val) {
        $spl = split("\.",$val["fkey"]);
        if (($val["type"] == 'child-many')) {
          $join =& $this->get_table($model->join_table_for($table, $val['tab']));
          $spl[0] = $this->prefix.$join->table;
          $val["fkey"] = $this->prefix.$join->table.'.'.strtolower(classify($table))."_".$model->foreign_key_for( $table);
        }else{
          foreach ($this->models[$spl[0]]->field_array as $fieldname=>$datatypename) {
            $fieldstring .= $this->prefix.$spl[0].".".$fieldname." as \"".$this->prefix.$spl[0].".".$fieldname."\", " . "\n";
          }
        }
        if ($first)
          $leftsql .= $table;
        $leftsql .= " left join " . $this->prefix.$spl[0] . " on ".$table.".".$val["col"]." = " . $val["fkey"];
        $leftsql .= ")";
        $first = false;
      }
    }
    $fieldstring = substr($fieldstring,0,-3) . " " . "\n";
    $sql .= $fieldstring;
    $sql .= "FROM ";
  
    $sql .= $leftsql;
  
    if (!(strlen($leftsql) > 1))
      $sql .= $table;
  
    if (is_array($model->find_by)) {

      $findfirst = true;
      $op = "AND";
      $eq = '=';
      foreach( $model->find_by as $col=>$val ) {
        if (is_array($val))
            list($col,$val) = each($val);
        if ($col == 'op') {
          $op = $val;
        } elseif ($col =='eq') {
          $eq = $val;
        } else {
          
          if (strpos($col,".") === false)
            $field = "$table.$col";
          else
            $field = $this->prefix.$col;
          
          if ($findfirst) {
            $sql .= " WHERE $field $eq '$val' ";
          } else {
            $sql .= " $op $field $eq '$val' ";
          }
          $findfirst = false;
          
        }
      }
    } elseif ($model->id != NULL) {
      if (strpos($model->find_by,".") === false)
        $field = $table.".".$model->find_by;
      else
        $field = $model->find_by;
      $sql .= " WHERE $field = '".$model->id."' ";
    }

    if (!(isset($model->orderby)))
      $model->orderby = $table . "." . $pkfield;

    if (!(isset($model->order)))
      $model->order = "DESC";
  
    if (!(isset($model->offset)))
      $model->offset = 0;

    if (!(isset($model->limit)))
      $model->limit = 20;

    if (isset($model->groupby))
      $sql .= " GROUP BY " . $model->groupby . " ";

    $sql .= " ORDER BY " . $model->orderby . " ";

    $sql .= $model->order . $this->query_limit($model->limit,$model->offset);

    trigger_after( 'get_query', $model, $this );
  
    return $sql;

  }

}

?>