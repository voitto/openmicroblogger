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
   * Generic Iterator
   * 
   * For looping over arrays, directories, file contents, etc.
   * 
   * More info...
   * {@link http://dbscript.net/genericiterator}
   * 
   * @package dbscript
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @version 0.6.0 -- 22-October-2008
   */

class GenericIterator {
  
  var $EOF;
  var $_currentRow;
  var $collection;
  
  function rewind() {
    $this->MoveFirst();
  }
  
  function valid() {
    return !$this->EOF;
  }
  
  function key() {
    return $this->_currentRow;
  }
  
  function current() {
    $this->Load();
  }
  
  function next() {
    $this->MoveNext();
  }
  
  function call($func, $params) {
    return call_user_func_array(array($this->collection, $func), $params);
  }
  
  function hasMore() {
    return !$this->EOF;
  }
  
}

?>