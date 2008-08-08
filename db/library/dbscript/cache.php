<?php

  /** 
   * dbscript -- restful openid framework
   * @version 0.5.0 -- 8-August-2008
   * @author Brian Hendrickson <brian@dbscript.net>
   * @link http://dbscript.net/
   * @copyright Copyright 2008 Brian Hendrickson
   * @package dbscript
   * @license http://www.opensource.org/licenses/mit-license.php MIT License
   */

  /**
   * Cache
   * 
   * cache recordset rows in a text file
   * 
   * More info...
   * {@link http://dbscript.net/cache}
   * 
   * @package dbscript
   * @author Alejandro Gervasio
   * @access public
   * @version 0.5.0 -- 8-August-2008
   * @todo implement
   */

class Cache {
  
  var $recordset;  // instance of recordset object
  var $expiry;     // cache expire time in seconds
  var $cacheFile;  // cache file
  var $data;       // recordset set array
  
  // constructor
  function Cache( $expiry = 86400, $cacheFile = 'cache.txt' ) {
    ( is_int( $expiry ) && $expiry>0) ? $this->expiry = $expiry: trigger_error( 'Expire time must be a positive integer', E_USER_ERROR );
    $this->cacheFile = $cacheFile;
    $this->data = array();
  }
  
  // if cache is valid, perform query and return a recordset. Otherwise, get recordset from cache file
  function query( $query, $model ) {
    // check if query starts with SELECT
    if ( !preg_match( "/^SELECT/", $query ) ) {
      return false;
    }
    if ( !$this->isValid() ) {
      // read from database
      global $db;
      $this->recordset = $db->get_recordset($query);
      $this->data = $this->write();
    } else {
      // read from cache file
      $this->data = $this->read();
    }
  }
  
  // write cache file
  function write() {
    if ( !$fp = fopen( $this->cacheFile, 'w' ) ) {
      trigger_error( 'Error opening cache file', E_USER_ERROR );
    }
    if ( !flock( $fp, LOCK_EX ) ) {
      trigger_error( 'Unable to lock cache file', E_USER_ERROR );
    }
    $this->recordset->rewind();
    while( $row = $this->recordset->MoveNext() ) {
      $content[] = $row;
    }
    if( !fwrite( $fp, serialize( $content ) ) ) {
      trigger_error( 'Error writing to cache file', E_USER_ERROR );
    }
    flock( $fp, LOCK_UN );
    fclose( $fp );
    unset( $fp, $row );
    return $content;
  }
  
  // read cache file
  function read() {
    if ( !$content = unserialize( file_get_contents( $this->cacheFile ) ) ) {
      trigger_error( 'Error reading from cache file', E_USER_ERROR );
    }
    return $content;
  }
  
  // determine cache validity based on a time expiry trigger
  function isValid() {
    if ( file_exists( $this->cacheFile ) && filemtime( $this->cacheFile ) > ( time() - $this->expiry ) ) {
      return true;
    }
    return false;
  }
  
  // fetch cache row
  function fetchRow(){
    if ( !$row = current( $this->data ) ) {
      return false;
    }
    next( $this->data );
    return $row;
  }
  
  // fetch all cache rows
  function fetchAll(){
    if ( count( $this->data ) < 1 ) {
      trigger_error( 'Error accessing cache data', E_USER_ERROR );
    }
    return $this->data;
  }
  
  // count cache rows
  function countRows() {
    if ( !$rows = count( $this->data ) ) {
      trigger_error( 'Error counting cache rows', E_USER_ERROR );
    }
    return $rows;
  }
  
}

?>