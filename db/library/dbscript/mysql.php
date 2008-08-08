<?php

  /** 
   * dbscript -- restful openid framework
   * @version 0.5.0 -- 8-August-2008
   * @author Brian Hendrickson <brian@dbscript.net>
   * @link http://dbscript.net/
   * @copyright Copyright 2008 Brian Hendrickson
   * @license http://www.opensource.org/licenses/mit-license.php MIT License
   * @package dbscript
   */  

  /**
   * MySQL
   * 
   * adapter for the MySQL database system
   * 
   * Usage:
   * <code>
   * $db = new MySQL ( 'hostname', 'database_name', 'username', 'password' );
   * </code>
   * 
   * More info...
   * {@link http://dbscript.net/mysql}
   * 
   * @package dbscript
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @version 0.5.0 -- 8-August-2008
   * @todo support array datatypes
   */

class MySQL extends Database {
  var $host;
  var $user;
  var $pass;
  var $opt1;
  var $opt2;
  var $dbname;
  function MySQL() {
    $this->db_open = false;
    $this->models = array();
    $this->recordsets = array();
    $this->max_blob_length = 6144000;  // default max blob file size is 6MB
    $this->max_string_length = 1024000;  // default max string length is 1MB
    $this->datatype_map = array(
      
      'float' => 'float', // precise to 23 digits
      'double' => 'float', // 24-53 digits
      'decimal' => 'float', // double stored as string

      'int' => 'int',
      'tinyint' => 'int',
      'smallint' => 'int',
      'mediumint' => 'int',
      'bigint' => 'int',
      
      'char' => 'char',
      'varchar' => 'char',
      'tinytext' => 'char',
      
      'text' => 'text',
      'mediumtext' => 'text',
      'longtext' => 'text',
      'bigtext' => 'text',
      
      'time' => 'time',
      'timestamp' => 'time',
      'datetime' => 'time',
      
      'date' => 'date',
      
      'boolean' => 'bool',
      'bool' => 'bool',
      
      'blob' => 'blob',
      'mediumblob' => 'blob',
      'longblob' => 'blob'
      
    );
    $args = func_get_args();
    $argnames = array('host','dbname','user','pass','opt1','opt2');
    for ($i = 0; $i < count($args); $i++) {
      $this->$argnames[$i] = $args[$i];
    }
    $this->true_values = array('t','true','1',true);
    $this->alias_array = array();
    $this->connect();
  }
  function connect() { /* function to re/establish the DB connection */
    trigger_before( 'connect', $this, $this );
    $this->conn = mysql_connect($this->host,$this->user,$this->pass,$this->opt1,$this->opt2);
    if (!$this->conn) {
       $this->db_open = false;
       trigger_error("Sorry, the database connection failed. Please check your database connection settings.".@mysql_error($this->conn), E_USER_ERROR );
    } else {
      $this->db_open = mysql_select_db($this->dbname);
      if (!$this->db_open)
        trigger_error(@mysql_error($this->conn), E_USER_ERROR );
    }
    return $this->db_open;
  }
  function escape_string( $string ) { /* watch for bad characters in each SQL query */
    trigger_before( 'escape_string', $this, $this );
    if (!(strlen($string) > 0)) { return $string; }
    $result = @mysql_escape_string($string);
    if (!$result && !(is_numeric($string))) {
      trigger_error("error in escape_string in mysql.php ".@mysql_error($this->conn), E_USER_ERROR );
    } else {
      return $result;
    }
  }
  
  function create_openid_tables() {
    if (in_array('oauth_consumers',$this->tables))
      return;
      
          $result = $this->get_result("CREATE TABLE openid_identities (
    uurl_id bigint(20) NOT NULL auto_increment,
    user_id bigint(20) NOT NULL default '0',
    url text,
    hash char(32),
    PRIMARY KEY  (uurl_id),
    UNIQUE KEY uurl (hash),
    KEY url (url(30)),
    KEY user_id (user_id)
    )");
$result = $this->get_result("CREATE TABLE openid_nonces (\n".
            "  server_url VARCHAR(2047),\n".
            "  timestamp INTEGER,\n".
            "  salt CHAR(40),\n".
            "  UNIQUE (server_url(255), timestamp, salt)\n".
            ")");
      
//CREATE TABLE openid_identities ( uurl_id int NOT NULL, user_id int NOT NULL default '0', url text, hash char(32) )

//CREATE TABLE oauth_consumers (consumer_key CHAR(255) PRIMARY KEY, secret CHAR(40), description CHAR(40));
    $result = $this->get_result("CREATE TABLE IF NOT EXISTS oauth_consumers (consumer_key CHAR(255) PRIMARY KEY, secret CHAR(40), description CHAR(40))");
//CREATE TABLE oauth_tokens (consumer_key CHAR(40), token CHAR(40), secret CHAR(40), token_type CHAR(7), nonce CHAR(40), user_id INT DEFAULT 0, expires INT DEFAULT 0);
    $result = $this->get_result("CREATE TABLE IF NOT EXISTS oauth_tokens (consumer_key CHAR(40), token CHAR(40), secret CHAR(40), token_type CHAR(7), nonce CHAR(40), user_id TINYINT DEFAULT 0, expires INT DEFAULT 0)");
//
    $result = $this->get_result("INSERT INTO oauth_consumers (consumer_key, secret, description) VALUES ('DUMMYKEY', '', 'Unidentified Consumer')");
//CREATE TABLE openid_nonces ( server_url VARCHAR(2047), timestamp INT, salt CHAR(40) );

//CREATE TABLE openid_associations ( server_url oid, handle VARCHAR(255), secret oid, issued INTEGER, lifetime INTEGER, assoc_type VARCHAR(64) );
$result = $this->get_result("CREATE TABLE openid_associations (\n".
            "  server_url BLOB,\n".
            "  handle VARCHAR(255),\n".
            "  secret BLOB,\n".
            "  issued INTEGER,\n".
            "  lifetime INTEGER,\n".
            "  assoc_type VARCHAR(64),\n".
            "  PRIMARY KEY (server_url(255), handle)\n".
            ")");


  }

  
  
  function get_result( $sql, $returnfalse = NULL ) { /* run an SQL query */
    trigger_before( 'get_result', $this, $this );
    global $request;
    if (isset($request->params))
      trigger_before( 'get_result', $request, $this );
    $result = @mysql_query( $sql, $this->conn );
    if (!$result && $returnfalse == NULL) {
      trigger_error("error in get_result in mysql.php ".@mysql_error($this->conn)." ".$sql, E_USER_ERROR );
      exit;
    } elseif (!$result && $returnfalse) {
      return false;
    } else {
      return $result;
    }
  }
  function next_primary_key($table,$pkfield,$sequence_name=NULL) {
    trigger_before( 'next_primary_key', $this, $this );
    return "";
  }
  function last_insert_id( &$result, $pk, $table ) { /* returns the id of the most recently modified record */
    trigger_before( 'last_insert_id', $this, $this );
    $res = @mysql_insert_id($this->conn);
    if (!$res) {
      trigger_error("unable to determine last_insert_id in mysql.php ".@mysql_error($this->conn), E_USER_ERROR );
    } else {
      return $res;
    }
  }
  function result_value( &$result, $resultindex, $field ) { /* get a single value from a result set */
    trigger_before( 'result_value', $this, $this );
    $res = mysql_result( $result, $resultindex, $field );
    if (!$res && $res != 0) {
      trigger_error("error in result_value in mysql.php".@mysql_error($this->conn), E_USER_ERROR );
    } else {
      return $res;
    }
  }
  function close() {
    trigger_before( 'close', $this, $this );
    $args = func_get_args();
    mysql_close( $this->conn );
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
  function fetch_array(&$result,$row=NULL) {
    trigger_before( 'fetch_array', $this, $this );
    if (is_numeric($row)) {
      $this->seek_row( $result, $row );
    }
    return mysql_fetch_array( $result, MYSQL_ASSOC );
  }
  function fetch_row(&$result,$row=NULL) {
    trigger_before( 'fetch_row', $this, $this );
    if (is_numeric($row)) {
      $this->seek_row( $result, $row );
    }
    return mysql_fetch_row( $result );
  }
  function seek_row(&$result,$row) {
    trigger_before( 'seek_row', $this, $this );
    return mysql_data_seek( $result, $row );
  }
  function query_limit($limit,$offset) {
    trigger_before( 'query_limit', $this, $this );
    return " LIMIT " . $offset .  "," . $limit;
  }
  function blob_value( &$rec, $field, &$value ) {
    trigger_before( 'blob_value', $this, $this );
    $ret = array();
    $ret['t'] = $rec->table;
    $ret['f'] = $field;
    $ret['k'] = $rec->primary_key;
    $ret['i'] = $rec->attributes[$rec->primary_key];
    return $ret;
  }
  function sql_insert_for( &$rec ) {
    trigger_before( 'sql_insert_for', $this, $this );
    $sql = "INSERT INTO " . $rec->table . " (";
    $comma = '';
    $fields = '';
    $values = '';
    foreach (array_unique($rec->modified_fields) AS $modified_field) {
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
    $sql .= $rec->table . ' SET ';
    $comma = '';
    foreach (array_unique($rec->modified_fields) AS $modified_field) {
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
    return "SELECT ".$rec->selecttext." FROM ".$rec->table." WHERE ".$rec->primary_key." = '".$id."'";
  }
  function sql_delete_for( &$rec ) {
    trigger_before( 'sql_delete_for', $this, $this );
    $pkfield = $rec->primary_key;
    $sql = 'DELETE FROM ' . $rec->table . ' WHERE ' . $rec->primary_key . ' = ' . $rec->$pkfield;
    return $sql;
  }
  function select_distinct( $field, $table, $orderby ) {
    trigger_before( 'select_distinct', $this, $this );
    return "SELECT DISTINCT $field, " . $this->models[$table]->primary_key . " FROM $table ORDER BY $orderby DESC";
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
    global $request;
    $req =& $request;
    if (isset($this->models[$rec->table]->field_attrs[$modified_field]['required'])) {
      if (!(strlen( $rec->attributes[$modified_field] ) > 0))
        trigger_error( "$modified_field is a required field", E_USER_ERROR );
    }
    if (isset($this->models[$rec->table]->field_attrs[$modified_field]['unique'])) {
      $result = $this->get_result("select ".$modified_field." from ".$rec->table." where ".$modified_field." = '".$rec->attributes[$modified_field]."'");
      if ($result && $this->num_rows($result) > 0)
        trigger_error( "Sorry but that $modified_field has already been taken.", E_USER_ERROR );
    }
    if ($datatype == 'time' && !(strlen($rec->attributes[$modified_field]) > 0))
      $rec->attributes[$modified_field] = date("Y-m-d H:i:s",strtotime("now"));
    if ($datatype == 'blob' && !(empty($req->params[strtolower(classify($rec->table))][$modified_field]))) {
      $coll = environment('collection_cache');
      if (isset($coll[$request->resource]) && $coll[$request->resource]['location'] == 'aws') {
        $this->aws_upload = array($modified_field,$rec->attributes[$modified_field]);
      } else {
        $rec->attributes[$modified_field] =& $this->large_object_create( $rec->table, $rec->attributes[$modified_field] );
      }
    }
    if ($datatype == 'bool') {
      if ( in_array( $rec->attributes[$modified_field], $this->true_values, true ) ) {
        $rec->attributes[$modified_field] = "1";
      } else {
        $rec->attributes[$modified_field] = "false";
      }
    }
  }
  function pre_update( &$rec, $modified_field, $datatype ) {
    trigger_before( 'pre_update', $rec, $this );
    global $request; 
    $req =& $request;  
    if (isset($this->models[$rec->table]->field_attrs[$modified_field]['required'])) {
      if (!(strlen( $rec->attributes[$modified_field] ) > 0))
        trigger_error( "$modified_field is a required field", E_USER_ERROR );
    }
    if (isset($this->models[$rec->table]->field_attrs[$modified_field]['unique'])) {
      $result = $this->get_result("select ".$modified_field." from ".$rec->table." where ".$modified_field." = '".$rec->attributes[$modified_field]."' and ".$rec->primary_key." != '".$rec->attributes[$rec->primary_key]."'");
      if ($this->num_rows($result) > 0)
        trigger_error( "Sorry but that $modified_field has already been taken.", E_USER_ERROR );
    }
    if ($datatype == 'bool') {
      if ( in_array( $rec->attributes[$modified_field], $this->true_values, true ) ) {
        $rec->attributes[$modified_field] = "1";
      } else {
        $rec->attributes[$modified_field] = "false";
      }
    }
    if ($datatype == 'blob' && !(empty($req->params[strtolower(classify($rec->table))][$modified_field]))) {
      if ( strlen( $rec->attributes[$modified_field] ) > 0 ) {
        $coll = environment('collection_cache');
        if (isset($coll[$request->resource]) && $coll[$request->resource]['location'] == 'aws') {
          $this->aws_upload = array($modified_field,$rec->attributes[$modified_field]);
          $this->aws_delfile($rec,$rec->id);
          $this->aws_putfile($rec,$rec->id);
        } else {
          unlink_cachefile($rec->table,$rec->id,$coll);
          $data =& $this->large_object_create($rec->table,$rec->attributes[$modified_field]);
          $rec->attributes[$modified_field] =& $data;
        }
      }
    }
  }
  function post_insert( &$rec, &$result ) {
    trigger_before( 'post_insert', $this, $this );
    if (!$result) { trigger_error("Sorry, the record could not be saved due to a database error.", E_USER_ERROR ); }
    $pkvalue = $this->last_insert_id($result,NULL,NULL);
    if (is_array($this->aws_upload))
      $this->aws_putfile($rec,$pkvalue);
    $pkfield = $rec->primary_key;
    $rec->attributes[$pkfield] = $pkvalue;
    $rec->$pkfield =& $rec->attributes[$pkfield];
  }
  function affected_rows(&$result) {
    trigger_before( 'affected_rows', $this, $this );
    return @mysql_affected_rows($result);
  }
  function fetch_field(&$result,$i) {
    trigger_before( 'fetch_field', $this, $this );
    return @mysql_fetch_field($result,$i);
  }
  function fetch_object(&$result) {
    trigger_before( 'fetch_object', $this, $this );
    return @mysql_fetch_object($result);
  }
  function num_rows(&$result) {
    trigger_before( 'num_rows', $this, $this );
    return @mysql_num_rows($result);
  }
  function num_fields(&$result) {
    trigger_before( 'num_fields', $this, $this );
    return @mysql_num_fields($result);
  }
  function field_name(&$result, $index) {
    trigger_before( 'field_name', $this, $this );
    return @mysql_field_name($result, $index);
  }
  function large_object_create($table,$file) {
    trigger_before( 'large_object_create', $this, $this );
    $return = false;
    if (is_array($file))
      return $return;
    if (!(file_exists($file))) { trigger_error("temporary file $file could not be found", E_USER_ERROR ); }
    $handle = fopen($file,"r");
    if (!$handle) { trigger_error("Error creating large object in fopen", E_USER_ERROR ); }
    $buffer = fread($handle,filesize($file));
    if (!$buffer) { trigger_error("Error creating large object in fread", E_USER_ERROR ); }
    $result = fclose($handle);
    if (!$result) { trigger_error("Error creating large object in fclose", E_USER_ERROR ); }
    else {
      $return =& $buffer;
    }
    return $return;
  }
  function large_object_fetch($table,$blobcol,$pkfield,$pkvalue, $return=false) {
    trigger_before( 'large_object_fetch', $this, $this );
    // t f k i
    $sql = "SELECT $blobcol FROM $table WHERE $pkfield = '$pkvalue'";
    $result = $this->get_result($sql);
    if ($result && $return)
      return $this->result_value($result,0,$blobcol);
    elseif ($result)
      print $this->result_value($result,0,$blobcol);
    return false;
  }
  function large_object_delete($oid) {
    trigger_before( 'large_object_delete', $this, $this );
    return true;
  }
  function add_table( $table, $field_array ) {
    trigger_before( 'add_table', $this, $this );
    if (!(count($field_array)>0)) trigger_error( "Error creating table, no fields are defined. Use \$model->auto_field and \$model->text_field etc.", E_USER_ERROR );
    $sql = "CREATE TABLE $table (";
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
    $sql = "ALTER TABLE $table ADD COLUMN $field $data_type";
    $result = $this->get_result($sql);
  }
  function has_table($t) {
    trigger_before( 'has_table', $this, $this );
    return in_array( $t, $this->get_tables(), true );
  }
  function get_tables() {
    trigger_before( 'get_tables', $this, $this );
    $tables = array();
    $sql =  "SHOW tables FROM ".$this->dbname;
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
    $sql = "SHOW columns FROM $table";
    $result = $this->get_result($sql, true);
    if (!$result) return $datatypes;
    while ($arr = $this->fetch_array($result)) {
      foreach($arr as $key=>$value) {
        if ($key == "Field") {
          $field = $value;
        } elseif ($key == "Type") {
          $type = $value;
        } elseif ($key == "Key") {
          if ($value == "PRI") {
            $datatypes[$table."_primary_key"] = $field; // yuck
          }
        }
      }
      $datatypes[$field] = $type;
    }
    return $datatypes;
  }
  
  function auto_field( $field, &$model ) {
    $model->set_field( $field, "int(11) not null auto_increment primary key" );
    $model->set_primary_key( $field );
  }

  function enum_field( $field, $values, &$model ) {
    $model->set_field( $field, "enum", $values );
  }
  
  function float_field( $field, &$model ) {
    $model->set_field( $field, "double" );
  }
  
  function bool_field( $field, &$model ) {
    $model->set_field( $field, "bool" );
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
    $model->set_field( $field, "longblob" );
  }
  
  function int_field( $field, &$model ) {
    $model->set_field( $field, "int(11)" );
  }
  
  function text_field( $field, &$model ) {
    $model->set_field( $field, "text" );
  }
  
  function time_field( $field, &$model ) {
    $model->set_field( $field, "datetime" );
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
    $table = $model->table;
    $fieldstring = '';
    $sql = "SELECT " . "\n";
    if (!array_key_exists($pkfield,$model->field_array))
      $sql .= "$table.$pkfield as \"$table.$pkfield\", " . "\n";
    foreach ($model->field_array as $fieldname=>$datatypename) {
      // loop to add each field to the sql query
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
        if (($val["type"] != 'child-many'))
          $leftsql .= "(";
      }
      $skippedrel = false;
      foreach ($relfields as $key=>$val) {
        $spl = split("\.",$val["fkey"]);
        if (($val["type"] == 'child-many')) {
          if ($first)
            $skippedrel = true;
          continue;
        }
        foreach ($this->models[$spl[0]]->field_array as $fieldname=>$datatypename) {
          $fieldstring .= $spl[0].".".$fieldname." as \"".$spl[0].".".$fieldname."\", " . "\n";
        }
        if ($first)
          $leftsql .= $table;
        $leftsql .= " left join " . $spl[0] . " on ".$table.".".$val["col"]." = " . $val["fkey"];
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
      foreach( $model->find_by as $col=>$val ) {
        if (is_array($val))
            list($col,$val) = each($val);
        if ($col == 'op') {
          $op = $val;
        } else {
          
          if (strpos($col,".") === false)
            $field = "$table.$col";
          else
            $field = $col;
          
          if ($findfirst) {
            $sql .= " WHERE $field = '$val' ";
          } else {
            $sql .= " $op $field = '$val' ";
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
    
    if (!(isset($model->orderby))) {
      $model->orderby = $table . "." . $pkfield;
    }
    
    if (!(isset($model->order))) {
      $model->order = "DESC";
    }
    
    if (!(isset($model->offset))) {
      $model->offset = 0;
    }
    
    if (!(isset($model->limit))) {
      $model->limit = 20;
    }
    
    if (isset($model->groupby))
      $sql .= " GROUP BY " . $model->groupby . " ";
    
    $sql .= " ORDER BY " . $model->orderby . " ";
    
    $sql .= $model->order . $this->query_limit($model->limit,$model->offset);
    
    trigger_after( 'get_query', $model, $this );
    //if ($model->table == 'people') { echo $sql; exit; }
    return $sql;
    
  }
  
}

?>