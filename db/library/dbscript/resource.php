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
   * Resource
   * 
   * A Restful HTTP client for accessing remote Models.
   * 
   * Usage:
   * <code>
   *   $jopeeps = $db->get_resource( 'http://joe.net/?people' );
   * </code>
   * 
   * More info...
   * {@link http://dbscript.net/resource}
   * 
   * @package dbscript
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @version 0.6.0 -- 22-October-2008
   * @todo implement
   */

class Resource {

  var $name;
  
  function Resource() {

    $this->defaults = array(
      
      'destination'=>'',
      'timeout'=>60
      
    );
    
  }

}

?>