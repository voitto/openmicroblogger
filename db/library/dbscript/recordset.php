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
   * Record Set
   * 
   * RecordSets are objects comprised of a join query result resource
   * and a lazy-loading iterator for each table in the result.
   * 
   * Usage:
   * <code>
   * $rs = $db->get_recordset( $people->get_query );
   *
   * while ( $Person = $rs->MoveNext() )
   *   print $Person->name;
   * </code>
   * 
   * More info...
   * {@link http://dbscript.net/recordset}
   * 
   * @package dbscript
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @version 0.5.0 -- 8-August-2008
   */

class RecordSet {
  
  var $query;
  var $table;
  var $result;
  var $rowcount;
  var $fieldlist;
  var $tablelist;
  var $rowmap;
  var $iterator;
  var $activerow;
  var $relations;
  
  function RecordSet( $sql ) {

    global $db;

    $this->query = $sql;
    $this->result = $db->get_result($sql, true);
    $this->rowcount = $db->num_rows($this->result);
    $this->fieldlist = array();
    $this->tablelist = array();

    // get table and field names from result column headers
$num_fields = $db->num_fields( $this->result );
    for ( $i = 0; $i < $num_fields; $i++ ) {
      $col = split( "\.", $db->field_name( $this->result, $i ) );
      if ( count( $col ) == 2 && $col[0] && $col[1] ) {
        $this->fieldlist[$col[0]][$col[1]] = $i;
        if ($col[1] == $db->models[$col[0]]->primary_key) {
          $this->tablelist[$col[0]] = $i; // pk offset
        }
        if ($i == 0) $this->table = $col[0];
      } else {
        trigger_error( 'Malformed SQORP query "'.$db->field_name( $this->result, $i ).'". Example: select people.id as "people.id".', E_USER_ERROR );
      }
    }

    $this->rowmap = array();
    $this->relations = array();
    
    // read the primary key value(s) in each row and map them to the result row number

    for ( $i = 0; $i < $db->num_rows( $this->result ); $i++ ) {
      foreach ( $this->tablelist as $table => $pkoffset ) {
        $pkvalue = $db->result_value( $this->result, $i, $pkoffset );
        if ( $pkvalue ) {
          $this->rowmap[$table][$pkvalue] = $i;
          if ( !( $table == $this->table ) ) {
            $this->relations[$db->result_value( $this->result, $i, $this->tablelist[$this->table] )][$table][] = $pkvalue;
          }
        }
      }
    }
    
    $this->iterator = array();
    $this->activerow = array();

  }
  
  function MoveFirst( $table ) {
    if ( array_key_exists( $table, $this->fieldlist )) {
      if ( !( isset( $this->iterator[$table] ))) {
        $this->iterator[$table] = new ResultIterator( $this, $table );
      }
      return $this->iterator[$table]->MoveFirst();
    } else {
      return false;
    }
  }
  
  function MoveNext( $table = NULL ) {
    if ($table === NULL) {
      $keys = array_keys( $this->fieldlist );
      $table = $keys[0];
    }
    if ( array_key_exists( $table, $this->fieldlist )) {
      if ( !( isset( $this->iterator[$table] ))) {
        $this->iterator[$table] = new ResultIterator( $this, $table );
      }
      return $this->iterator[$table]->MoveNext();
    } else {
      return false;
    }
  }
  
  function FirstChild( $parent_pkval, $table ) {
    if ( array_key_exists( $table, $this->fieldlist )) {
      if ( !( isset( $this->iterator[$table] ))) {
        $this->iterator[$table] = new ResultIterator( $this, $table );
      }
      return $this->iterator[$table]->FirstChild( $parent_pkval );
    } else {
      return false;
    }
  }

  function NextChild( $parent_pkval, $table ) {
    if ( array_key_exists( $table, $this->fieldlist )) {
      if ( !( isset( $this->iterator[$table] ))) {
        $this->iterator[$table] = new ResultIterator( $this, $table );
      }
      return $this->iterator[$table]->NextChild( $parent_pkval );
    } else {
      return false;
    }
  }
  
  function Load( $table, $row ) {
    global $db;
    trigger_before( 'Load', $db, $this ); 
    if ( !( $row < $this->rowcount )) return false;
    if ( array_key_exists( $table, $this->fieldlist )) {
      $this->activerow[$table] = $db->fetch_array( $this->result, $row );
      foreach ( $this->fieldlist[$table] as $field => $idx ) {
        $this->fieldlist[$table][$field] =& $this->activerow[$table][$table.".".$field];
      }
      trigger_after( 'Load', $db, $this ); 
      return $db->iterator_load_record( $table, $this->fieldlist[$table], $this );
    } else {
      return false;
    }
  }
  
  function rewind() {
    $table = $this->table;
    $row = 0;
    if ( array_key_exists( $table, $this->fieldlist )) {
      if ( !( isset( $this->iterator[$table] ))) {
        $this->iterator[$table] = new ResultIterator( $this, $table );
      }
      return $this->iterator[$table]->seek( $row );
    } else {
      return false;
    }
  }
  
  function num_rows( $table ) {
    if (isset($this->rowmap[$table]))
      return count($this->rowmap[$table]);
    return 0;
  }

}

?>