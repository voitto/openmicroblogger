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
   * Result Iterator
   * 
   * Attached to a RecordSet to lazy-load its result resource.
   * 
   * More info...
   * {@link http://dbscript.net/resultiterator}
   * 
   * @package dbscript
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @version 0.6.0 -- 22-October-2008
   */
   
class ResultIterator extends GenericIterator {

  var $rs;
  var $result;
  var $rowcount;
  var $table_name;
  var $tablemapper;
  var $pkvals;

  function ResultIterator( &$rs, $table ) {

    $this->rs =& $rs;
    $this->result =& $rs->result;
    $this->table_name = $table;
    $this->_currentRow = 0;
    $this->EOF = false;
    $this->tablemapper = array();
    $this->pkvals = array();
    $this->rowcount = 0;
    foreach ( $rs->rowmap as $table => $pkvals ) {
      if ($table == $this->table_name) {
        foreach ( $pkvals as $pk => $result_row ) {
          if ($pk != 0) {
            $this->tablemapper[] = $result_row;
            $this->pkvals[] = $pk;
            $this->rowcount++;
          }
        }
      }
    }
  }

  function seek( $row ) {
    $return = false;
    if ( !( $this->rowcount > 0 )) {
      $this->EOF = true;
    }
    if ( !( $row < $this->rowcount )) {
      $this->EOF = true;
    }
    if ($this->valid()) {
      $this->_currentRow = $row;
      $return = true;
    }
    return $return;
  }
  
  function MoveFirst() {
    $this->EOF = false;
    $this->_currentRow = 0;
    if ($this->seek( 0 )) return $this->Load();
    return false;
  }
  
  function MoveNext() {
    global $db;
    if ($this->seek( $this->_currentRow )) {
      $rec = $this->Load();
      $this->_currentRow++;
      foreach ($db->models[$this->table_name]->relations as $table=>$vals) {
        if (!(isset($rec->$table))) 
          $rec->$table = $rec->FirstChild( $table );
      }
      return $rec;
    }
    return false;
  }
  
  function FirstChild( $parent_pkval ) {
    $this->_currentRow = array_search( $this->rs->relations[$parent_pkval][$this->table_name][0], $this->pkvals, false );
    if ( $this->seek( $this->_currentRow ) && in_array( $this->pkvals[$this->_currentRow], $this->rs->relations[$parent_pkval][$this->table_name] ) ) {
      return $this->Load();
    }
    return false;
  }
  
  function NextChild( $parent_pkval ) {
    if ( $this->seek( $this->_currentRow ) && in_array( $this->pkvals[$this->_currentRow], $this->rs->relations[$parent_pkval][$this->table_name] ) ) {
      $rec = $this->Load();
      $this->_currentRow++;
      return $rec;
    }
    return false;
  }
  
  function Load() {
    if ( !( $this->valid() ) ) return NULL;
    return $this->rs->Load( $this->table_name, $this->tablemapper[$this->_currentRow] );
  }

}

?>