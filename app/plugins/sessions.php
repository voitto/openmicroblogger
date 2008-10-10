<?php

  /** 
   * dbscript -- restful openid framework
   * @version 0.6.0 -- 10-October-2008
   * @author Brian Hendrickson <brian@dbscript.net>
   * @link http://dbscript.net/
   * @copyright Copyright 2008 Brian Hendrickson
   * @license http://www.opensource.org/licenses/mit-license.php MIT License
   * @package dbscript
   */


  /**
   * Session Error
   * 
   * filter to catch a submitted resource in case of error
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param object $req
   * @param string $errstr
   * @todo re-implement
   */

function session_error( &$req, $errstr ) {
  
  global $db;
  
  if ( array_key_exists( $req->resource, $db->models )) {
    $model =& $db->models[$req->resource];
    if ( isset( $req->action ) && in_array( $req->action, array( 'put','post' )))
      session_save( $req, $model );
  }
  
}


  /**
   * Session Save
   * 
   * save submitted data into the database on error
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param object $req
   * @param object $model
   * @todo revise
   */

function session_save( &$req, &$model ) {
  global $db;
  $pkfield = $model->primary_key;
  $rec = $model->base();
  foreach ($model->field_array as $fieldname=>$datatypename) {
    $formfield = $fieldname;
    if (isset($req->$formfield)) {
      $rec->set_value( $fieldname, $req->$formfield );
    }
  }
  $_SESSION[$model->table."_submission"] = $rec;
  foreach ($model->relations as $table=>$vals) {
    $pkfield = $table . "_" . $db->models[$table]->primary_key;
    if (isset($req->$pkfield) && $req->$pkfield > 0) {
      $rec = $db->models[$table]->base();
      foreach ( $db->models[$table]->field_array as $fieldname=>$datatypename ) {
        $formfield = $table . "_" . $fieldname;
        if (isset($req->$formfield)) {
          $rec->set_value( $fieldname, $req->$formfield );
        }
      }
      $_SESSION[$table."_submission"] = $rec;
    }
  }
}


  /**
   * Session Restore
   * 
   * load submitted data from db_sessions table
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param object $model
   * @return object
   * @todo revise
   */

function session_restore( &$model ) {
  if (!(isset($_SESSION[$model->table."_submission"])))
    return false;
  $rec = $_SESSION[$model->table."_submission"];
  foreach ($model->relations as $table=>$vals) {
    if (isset($_SESSION[$table."_submission"])) {
      if (!(isset($rec->$table)))
        $rec->$table = $_SESSION[$table."_submission"];
    }
  }
  return $rec;
}


/**
* enable custom PHP session handler functions
*/

session_set_save_handler(
  'sess_open',
  'sess_close',
  'sess_read',
  'sess_write',
  'sess_destroy',
  'sess_clean'
);


  /**
   * sess_open
   * 
   * read session data from db_sessions table
   * 
   * @access public
   */

function sess_open() {
  global $db;
  global $request;
  
  $req =& $request;
  
  $DbSession =& $db->get_table( 'db_sessions' );
  $DbSession->char_field( 'id' );
  $DbSession->int_field( 'access' );
  $DbSession->text_field( 'data' );
  $DbSession->int_field( 'person_id' );
  $DbSession->set_primary_key( 'id' );
  if (!($DbSession->exists))
    $DbSession->save();
  $req->DbSession = $DbSession->base();
  return true;
}


  /**
   * sess_close
   * 
   * returns true
   * 
   * @access public
   */

function sess_close() {
  return true;
}


  /**
   * sess_read
   * 
   * handler to read session data from db_sessions table
   * 
   * @access public
   */

function sess_read( $id ) {
  global $db;
  if (!(isset($db)))
    return false;
  $result = $db->get_result( "SELECT data
                              FROM db_sessions
                              WHERE id = '$id'" );
  if ($result && $db->num_rows($result) > 0) {
    return $db->result_value( $result, 0, 'data' );
  }
  return '';
}


  /**
   * sess_write
   * 
   * handler to write session data to db_sessions table
   * 
   * @access public
   */

function sess_write( $id, $data ) {
  global $db;
  if (!(isset($db)))
    return false;
  $access = time();
  $result = $db->get_result( "SELECT id
                              FROM db_sessions
                              WHERE id = '$id'" );
                              
  if ($result && $db->num_rows($result) > 0) {
    return $db->get_result( "UPDATE db_sessions
                             SET access = '$access', data = '$data'
                             WHERE id = '$id'" );
  }
  return $db->get_result( "INSERT
                           INTO db_sessions ( id, access, data )
                           VALUES ( '$id', '$access', '$data' )" );
}


  /**
   * sess_destroy
   * 
   * handler to delete a session from db_sessions table
   * 
   * @access public
   */

function sess_destroy( $id ) {
  global $db;
  if (!(isset($db)))
    return false;
  return $db->get_result( "DELETE
                           FROM db_sessions
                           WHERE id = '$id'" );
}


  /**
   * sess_clean
   * 
   * handler to delete expired sessions from db_sessions table
   * 
   * @access public
   */

function sess_clean( $max ) {
  global $db;
  if (!(isset($db)))
    return false;
  $old = time() - $max;
  return $db->get_result( "DELETE
                           FROM db_sessions
                           WHERE access < '$old'" );
}

function sessions_init() {
  if (!(session_started()))
    session_start();

}

?>