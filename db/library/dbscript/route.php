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
   * URI Route
   * 
   * connects the current URI to a Route,
   * establishing the request variable names
   * e.g. my_domain/:resource/:id
   * maps values into $req->resource and $req->id
   * 
   * Usage:
   * <code>
   *   $req->connect( 'virtualdir/:var1/:var2' );
   * </code>
   * 
   * More info...
   * {@link http://dbscript.net/route}
   * 
   * @package dbscript
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @version 0.5.0 -- 8-August-2008
   */

class Route {

  var $patterns;
  var $defaults;
  var $requirements;
  var $match;
  var $name;
  
  function Route() {

    $this->patterns = array();
    $this->requirements = array();
    $this->match = false;
    
    $this->defaults = array(
      
      'controller'=>'index.php',
      'resource'=>NULL,
      'id'=>0,
      'action'=>'get',
      'child'=>0
      
    );
    
  }
  
  function build_url( $params, $base ) {
    $url = array();
    foreach ( $this->patterns as $pos => $str ) {
      if ( substr( $str, 0, 1 ) == ':' ) {
        $url[] = $params[substr( $str, 1 )];
      } else {
        $url[] = $str;
      }
    }
    if ( !( substr( $base, -1 ) == '/' ))
      $base = $base . "/";
    return $base . "?". implode ( '/', $url );
  }

}

?>