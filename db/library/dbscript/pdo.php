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
   * pdo
   * 
   * adapter for pdo -- incomplete
   * 
   * Usage:
   * <code>
   * $db = new pdo ( 'hostname', 'database_name', 'username', 'password' );
   * </code>
   * 
   * More info...
   * {@link http://dbscript.net/pdo}
   * 
   * @package dbscript
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @version 0.5.0 -- 8-August-2008
   * @todo support array datatypes
   */

class pdo extends Database {
  var $host;
  var $user;
  var $pass;
  var $opt1;
  var $opt2;
  var $dbname;
  function pdo() {
  }
  function connect() { /* function to re/establish the DB connection */
  }
  function escape_string( $string ) { /* watch for bad characters in each SQL query */
  }
  function get_result( $sql, $returnfalse = NULL ) { /* run an SQL query */
  }
  function next_primary_key($table,$pkfield,$sequence_name=NULL) {
  }
  function last_insert_id( &$result, $pk, $table ) { /* returns the id of the most recently modified record */
  }
  function result_value( &$result, $resultindex, $field ) { /* get a single value from a result set */
  }
  function close() {
  }
  function &get_table($table) {
  }
  function &model($model) {
  }
  function fetch_array(&$result,$row=NULL) {
  }
  function fetch_row(&$result,$row=NULL) {
  }
  function seek_row(&$result,$row) {
  }
  function query_limit($limit,$offset) {
  }
  function blob_value( &$rec, $field, &$value ) {
  }
  function sql_insert_for( &$rec ) {
  }
  function sql_update_for( &$rec ) {
  }
  function sql_select_for( &$rec, $id ) {
  }
  function sql_delete_for( &$rec ) {
  }
  function select_distinct( $field, $table, $orderby ) {
  }
  function quoted_update_value( &$rec, $modified_field ) {
  }
  function quoted_insert_value( &$rec, $modified_field ) {
  }
  function pre_insert( &$rec, $modified_field, $datatype ) {
  }
  function pre_update( &$rec, $modified_field, $datatype ) {
  }
  function post_insert( &$rec, &$result ) {
  }
  function num_rows(&$result) {
  }
  function num_fields(&$result) {
  }
  function field_name(&$result, $index) {
  }
  function large_object_create($table,$file) {
  }
  function large_object_fetch($table,$blobcol,$pkfield,$pkvalue, $return=false) {
  }
  function large_object_delete($oid) {
  }
  function add_table( $table, $field_array ) {
  }
  function add_field( $table, $field, $data_type ) {
  }
  function has_table($t) {
  }
  function get_tables() {
  }
  function get_fields($table) {
  }
  
}


  /**
   * pdo Table
   * 
   * data model for a single pdo table
   * 
   * Usage:
   * <code>
   *   $Person =& $db->model( 'Person' );
   * </code>
   * 
   * More info...
   * {@link http://dbscript.net/pdotable}
   * 
   * @package dbscript
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param string $table
   * @param object $db
   * @version 0.5.0 -- 8-August-2008
   */

class pdoTable extends Model {
  
  function pdoTable( $table, &$db ) {
  }
  
  function auto_field( $field ) {
  }

  function enum_field( $field, $values ) {
  }
  
  function float_field( $field ) {
  }
  
  function bool_field( $field ) {
  }
  
  function char_field( $field ) {
  }
  
  function date_field( $field ) {
  }
  
  function file_field( $field ) {
  }
  
  function int_field( $field ) {
  }
  
  function text_field( $field ) {
  }
  
  function time_field( $field ) {
  }
  
  function get_query( $id=NULL, $find_by=NULL ) {
  }
  
}

?>