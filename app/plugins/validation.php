<?php

  /** 
   * dbscript -- restful openid framework
   * @version 0.5.0 -- 12-August-2008
   * @author Brian Hendrickson <brian@dbscript.net>
   * @link http://dbscript.net/
   * @copyright Copyright 2008 Brian Hendrickson
   * @license http://www.opensource.org/licenses/mit-license.php MIT License
   * @package dbscript
   */


  /**
   * Regex Validate
   * 
   * filter to regex validate input, per-datatype
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param object $rec
   * @param object $model
   * @return boolean
   */

function regex_validate( &$rec, &$model ) {
  global $db;
  foreach (array_unique($rec->modified_fields) as $modified_field) {
  
    $value =& $rec->attributes[$modified_field];
    $type = $db->get_mapped_datatype( $model->field_array[$modified_field] );
  
    switch($type) {
      case "text":
        return ( isset($value) && $value !== NULL && strlen($value) < $db->max_string_length );
      case "char":
        return ( isset($value) && $value !== NULL && strlen($value) < $db->max_string_length );
      case "int":
        return (is_numeric($value) || $value === 0);
      case "float":
        return (is_numeric($value));
      case "time":
        return true;
      case "date":
        return true;
      case "blob":
        return ( file_exists( $value ) && filesize( $value ) < $db->max_blob_length );
      case "bool":
        return (is_bool($value));
      default:
        trigger_error( "The $modified_field field does not have a valid $datatype value.", E_USER_ERROR );
    }
    
  }

}


  /**
   * Format Output
   * 
   * Incomplete - filter to format output, per-datatype
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param object $rec
   * @param object $model
   * @return string
   * @todo implement
   */

function format_output( &$rec, &$model ) {
  global $db;
  foreach (array_unique($rec->modified_fields) as $modified_field) {
  
    $value =& $rec->attributes[$modified_field];
    $type = $db->get_mapped_datatype( $model->field_array[$modified_field] );
  
    switch($type) {
      case "text":
        return true;
      case "char":
        return true;
      case "int":
        return true;
      case "float":
        return true;
      case "time":
        return true;
      case "date":
        return true;
      case "blob":
        return true;
      case "bool":
        return true;
      default:
        return true;
    }
    
  }

}


?>