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
   * URI Mapper
   * 
   * connects the current URI to a Route,
   * establishing the request variable names
   * e.g. my_domain/:resource/:id would map
   * values into $req->resource and $req->id
   * 
   * Usage:
   * <code>
   *   $req = new Mapper();
   * </code>
   * 
   * More info...
   * {@link http://dbscript.net/mapper}
   * 
   * @package dbscript
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @return object
   * @version 0.3.1
   */

class Mapper {
  
  /**
   * current URI
   * @var string
   */
  var $uri;

  /**
   * days til SESSION cookie expires
   * @var string
   */

  var $cookiedays;

  /**
   * domain in URI
   * @var string
   */

  var $domain;
  
  /**
   * path after domain in URI
   * @var string
   */
  var $path;
  /**
   * base URI
   * @var string
   */
  var $base;

  /**
   * unmolested regex parts of the URI
   * @var string[]
   */
  var $values;
  
  /**
   * URI parameter names and values
   * @var string[]
   */
  var $params;

  /**
   * matched Route object
   * @var Route
   */
  var $activeroute;

  /**
   * list of connected Route objects
   * @var Route[]
   */
  var $routes;

  /**
   * list of Groups
   * @var string[]
   */
  var $groups;
  
  /**
   * list of public methods
   * @var string[]
   */
  var $allowed_methods;

  /**
   * parameters to (silently) propagate
   * @var string[]
   */
  var $persisted_vars;

  /**
   * path to views
   * @var string
   */
  var $template_path;
  
  /**
   * path to layouts
   * @var string
   */
  var $layout_path;

  /**
   * true if an error has been raised
   * @var boolean
   */
  var $error;

  /**
   * openid status
   * @var boolean
   */
  var $openid_complete;
  
  /**
   * contents of error message
   * @var string
   */
  var $error_string;
  
  /**
   * database Record object for the current session
   * @var string
   */
  var $DbSession;

  function Mapper() {
    
    $this->params = array('');
    
    $this->cookiedays = 30;

    $this->uri = $this->composite_uri();
    
    preg_match( "/^(https?:\/\/)([^\/]+)\/?[^\?]+?[\??]([-%\w\/\.]+)?/i", $this->uri, $this->values );
    
    if (!($this->values))
      preg_match( "/^(https?:\/\/)([^\/]+)\/?(([^\?]+))?/i", $this->uri, $this->values );
    
    $pos = strpos( $this->uri, "?" );
    
    if ( $pos > 0 )
      $this->base = substr( $this->uri, 0, $pos );
    else
      $this->base = $this->uri;
      
    if ( isset( $this->values[3] ) )
      $this->params = explode( '/', $this->values[3] );
    
    $qp = strpos($this->uri,"?");
    
    $end = 0 - (strlen($this->uri) - $qp);
    
    $lenbase = strlen($this->values[1]) + strlen($this->values[2]);
    
    if ($qp === false)
      $this->path = substr($this->uri, $lenbase);
    else
      $this->path = substr($this->uri, $lenbase, $end);

    if (!(strpos($this->params[(count($this->params)-1)],".") === false)) {
      $actionsplit = split("\.", $this->params[(count($this->params)-1)]);
      $this->client_wants = $actionsplit[1];
    }
      
    session_set_cookie_params( 60*60*24*$this->cookiedays, $this->path );
    
    if (!(substr($this->path, -1) == "/"))
      $this->path .= "/";
    
    $this->domain = $this->values[2];

    if ($qp > $lenbase)
      $this->params = explode( '/', substr($this->uri,$qp+1));
    else
      $this->params = array('');

    $this->routes = array();
    $this->persisted_vars = array();
    $this->allowed_methods = array();
    $this->groups = array();
    $this->template_path = '';
    $this->layout_path = '';
    $this->error = false;
    $this->openid_complete = false;
    
  }
  
  function handle_error( $errstr ) {
    $this->error = true;
    $this->params['error'] .= $errstr . "\n";
    trigger_before( 'handle_error', $this, $errstr );
  }
  
  function route_exists( $routename ) {
    foreach ( $this->routes as $r ) 
      if ( $routename == $r->name )
        return true;
    return false;
  }
  
  function url_for( $params, $altparams = NULL ) {
    $match = false;
    $route_match = NULL;
    
    if ( is_string( $params ) ) {
      // first var is a route name (or a URL)
      
      if (strstr($params,"http")) {
        return $params;
      }
      
      $routename = $params;
      $params = $altparams;
    }

    foreach ( $this->routes as $r ) {
      
      $vars = array();
      
      foreach ( $r->patterns as $pos => $str ) {
        if ( substr( $str, 0, 1 ) == ':' ) {
          $vars[substr( $str, 1 )] = $pos;
        }
      }

      if ( isset( $routename ) ) {
        if ( $routename == $r->name ) {
          // a named route was found
          if ($altparams == NULL)
            $params = $r->defaults;
          return $r->build_url( $params, $this->base );
        }
//      } elseif ( is_array($params) && count( array_intersect( array_keys($vars), array_keys($params) ) ) == count( $vars ) && count($vars) == count($params) && count($r->patterns) == count($params) ) {
      } elseif ( is_array($params) && count( array_intersect( array_keys($vars), array_keys($params) ) ) == count( $vars ) && count($vars) == count($params)  ) {
        // every pattern in the route exists in the requested params

        return $r->build_url( $params, $this->base );
      } else {
        // eh
      }
      
    }

    foreach ( $this->params as $paramkey=>$paramval ) {
      
      if ( is_integer( $paramkey ) )
        continue;
      
      $params[$paramkey] = $paramval;
      
      foreach ( $this->routes as $r ) {
        
        $vars = array();
        
        foreach ( $r->patterns as $pos => $str ) {
          if ( substr( $str, 0, 1 ) == ':' ) {
            $vars[substr( $str, 1 )] = $pos;
          }
        }
        
        if ( count( array_intersect( array_keys($vars), array_keys($params) ) ) == count( $vars ) && count($vars) == count($params) ) {

          return $r->build_url( $params, $this->base );
        }
      
      } // end foreach routes
    
    } // end foreach params

  }
  
  function link_to( $params, $altparams = NULL ) {
    $url = $this->url_for( $params, $altparams );
    return "<a href=\"$url\">$url</a>";
  }
  
  function redirect_to( $params, $altparams = NULL ) {
    header( "Location: " . $this->url_for($params, $altparams) );
    exit;
  }

  function breadcrumbs() {
    $controller = $this->params['resource'];
    $links = array();
    $html = "";

    $links[] = '<a href="'. $this->base .'">Home</a>';
    
    if ( isset( $this->resource ) && ( $this->resource != 'introspection' ))
      $links[] = '<a href="'. $this->base .'?'.$this->resource.'">'.ucwords($this->resource).'</a>';
    
    if ( ($this->id != 0) && isset( $this->resource ) && ( $this->resource != 'introspection' ))
       $links[] = '<a href="'.$this->entry_url($this->id).'">Entry '.ucwords($this->id).'</a>';
    elseif ( isset( $this->resource )  && $this->new_url())
      $links[] = '<a href="'.$this->new_url().'">New '.classify($this->resource).'</a>';
    
    $html = "<span>";
    foreach ($links as $key=>$val) {
      if ($key > 0) {
        $html .= " | ";
      }
      $html .= $val;
    }
    $html .= "</span>";
    return $html;
  }
  
  function set_persisted_vars($arr) {
    if (is_array($arr))
      $this->persisted_vars = $arr;
  }
  
  function set_filter( $name, $func, $when = 'after' ) {
    aspect_join_functions( $func, $name, $when );
  }
  
  function set_action( $method ) {
    $this->allowed_methods[] = $method;
  }
  
  function set_param( $param, $value ) {
    if (is_array($param)) {
      $this->params[$param[0]][$param[1]] = $value;
    } else {
      $this->params[$param] = $value;
      $this->$param =& $this->params[$param];
    }
  }
  
  function set_layout_path( $path ) {
    $this->layout_path = $path;
  }
    
  function set_template_path( $path ) {
    $this->template_path = $path;
  }
  
  function feed_url() {
    $result = false;
    if (isset($this->action)&&in_array($this->action,array('login','email')))
      return $result;
    if (isset($this->resource)&&in_array($this->resource,array('introspection')))
      return $result;
    if (isset($this->resource))
      $result = is_file( $this->template_path . $this->resource . DIRECTORY_SEPARATOR. '_index.atom' );
    if ($result)
      return $this->url_for( array('resource'=>$this->resource, 'action'=>'index.atom'));
    return $result;
  }
  
  function entry_url( $id = NULL ) {
    $result = false;
    if (isset($this->resource))
      $result = is_file( $this->template_path . $this->resource . DIRECTORY_SEPARATOR. '_entry.js' );
    if (!$result)
      $result = is_file( $this->template_path . $this->resource . DIRECTORY_SEPARATOR. '_entry.html' );
    if ($result && ($id != NULL))
      return $this->url_for( array('resource'=>$this->resource, 'action'=>'entry', 'id'=>$id));
    if ($result)
      return $this->url_for( array('resource'=>$this->resource, 'action'=>'entry'));
    return $result;
  }
  
  function new_url() {
    $result = false;
    if (isset($this->resource))
      $result = is_file( $this->template_path . $this->resource . DIRECTORY_SEPARATOR. '_new.js' );
    if (!$result)
      $result = is_file( $this->template_path . $this->resource . DIRECTORY_SEPARATOR. '_new.html' );
    if ($result)
      return $this->url_for( array('resource'=>$this->resource, 'action'=>'new'));
    return $result;
  }
    
  function get_template_path( $ext, $template = null ) {
    
    if (isset($this->params['resource']))
      $resource = $this->params['resource'] . DIRECTORY_SEPARATOR;
    else
      $resource = "";
    
    if ($template == null) {
      $partial = false;
      $template = $this->params['action'];
    } else {
      $partial = true;
      $template = "_" . $template;
    }
    
    if ($template == 'get')
      $template = 'index';
    
    if (isset($this->client_wants))
      $ext = $this->client_wants;
    
    // example: blah.net/?posts/new.html
    
    // searching for a layout to go with the partial _new
    
    // /posts/new.html
    $view = $this->template_path . $resource . $template . "." . $ext;
    
    // /new.html
    if (!(is_file($view)))
      $view = $this->template_path . $template . "." . $ext;
    
    // /posts/index.html
    if (!$partial && !(is_file($view)))
      $view = $this->template_path . $resource . 'index' . "." . $ext;
    
    // /index.html
    if (!$partial && !(is_file($view)))
      $view = $this->template_path . 'index' . "." . $ext;
    
    if (!$partial) {
      
      if ($this->action == 'get')
        $action = 'index';
      else
        $action = $this->action;
      
      // found a potential layout but is there a partial with the same extension?
      
      // /posts/_new.ext  ??
      if ((!(file_exists($this->template_path . $resource . "_" . $action . "." . $ext)))
        // /_new.ext    ??
        && (!(file_exists($this->template_path . "_" . $action . "." . $ext))))
          return false;
      
    }
    
    if  (is_file($view))
      return $view;
    
    return false;
    
  }
  
  function is_allowed( $method ) {
    return in_array( $method, $this->allowed_methods, true );
  }
  
  function connect() {
    // connect a Route to the Mapper
    
    $r = new Route();
    
    $args = func_get_args();
    
    if (count($args) == 1) {
      $args[] = $args[0];
      $args[] = array('action'=>$args[0]); 
    }
    
    foreach ( $args as $idx => $arg ) {
      
      if ( is_string( $arg ) ) {
        
        $r->patterns = explode( '/', $arg );
        
        if ( count( $r->patterns ) == 1 && $idx == 0 )
          $r->name = $r->patterns[0];
          
      } elseif ( is_array( $arg ) ) {
        
        foreach ( $arg as $key => $val ) {
          if ( $key == 'requirements' ) {
            $i = 0;
            foreach ( $r->patterns as $pos => $str ) {
              if ( substr( $str, 0, 1 ) == ':' ) {
                $r->requirements[$pos] = $val[$i];
                $i++;
              }
            }
          } else {
            $r->defaults[$key] = $val;
          }
        }
        
      }
      
    }
    
    $this->routes[] = $r;
    
  }
  
  function generate( $controller='index.php', $action='get' ) {
    // Generate a route from a set of keywords and return the url
  }
  
  function set( $param, $val ) {
    $this->$param = $val;
  }
  
  function routematch( $url = NULL ) {
    // Match a URL against against one of the routes contained.
    
    if ($this->activeroute)
      return;
    
    $return = false;
    trigger_before( 'routematch', $this, $this->activeroute );
    
    if ($url === NULL) $url = $this->uri;
    
    foreach ( $this->routes as $route ) {
      if ($this->match( $url, $route )) {
        break;
        $return = true;
      }
    }
    
    if ( isset( $this->params['method'] ) ) $this->action = $this->method;
    
    if ( isset( $this->params['forward_to'] ) ) $this->controller = $this->forward_to;
    
    if ( isset( $this->action )) {
      if (!(strpos($this->action,".") === false)) { // check for period
        $actionsplit = split("\.", $this->action);
        $this->set_param( 'action', $actionsplit[0]);
        $this->set( 'client_wants', $actionsplit[1] );
      }
    }
    if (isset($this->resource)) {
      if (!(strpos($this->resource,".") === false)) { // check for period
        $actionsplit = split("\.", $this->resource);
        $this->set_param( 'resource', $actionsplit[0]);
        $this->set( 'client_wants', $actionsplit[1] );
      }
    }
    
    trigger_after( 'routematch', $this, $this->activeroute );
    
    return $return;
    
  }
  
  function match( $url, $r ) {
    
    foreach ( $r->patterns as $idx => $value ) {
      if ( !( isset( $this->params[$idx] ) ) ) {
        return false;
      }
    }
    
    $i = 0;
    $regx = array();
    foreach ( $r->patterns as $pos => $str ) {
      if ( substr( $str, 0, 1 ) == ':' ) {
        if ( isset( $r->requirements[$pos] ) ) {
          $regx[] = $r->requirements[$pos];
        } else {
          $regx[] = '(.+)';
        }
        $i++;
      } else {
        $regx[] = $str;
      }
    }
    
    $params = $this->params;
    $paramcount = count($params);

    while ( count( $params ) > count( $regx ) ) {
      array_shift( $params );
    }
    
    if ( count( $r->patterns ) == 0 ) {
      $r->match = true;
      $pmatches = array();
//    } elseif ( preg_match( "/\/" . implode( "\/", $regx ) . "/i", "/" .implode( "/", $params ), $pmatches )  ) {
    } elseif ( preg_match( "/\/" . implode( "\/", $regx ) . "/i", "/" .implode( "/", $params ), $pmatches ) && count($r->patterns) == $paramcount ) {
      $r->match = true;
    }
    
    if ($r->match) {
      $this->activeroute =& $r;
      $this->params = array_merge( $_GET, $_POST, $r->defaults, $this->params );
      foreach ( $this->params as $p=>$v ) {
        if ( !( isset( $this->$p ) ) )
          $this->$p =& $this->params[$p];
      }
      foreach ( $r->patterns as $idx => $val ) {
        if ( substr( $val, 0, 1 ) == ':' ) {
          $val = substr( $val, 1);
          if ( isset( $params[$idx] ) ) $this->params[$val] = $params[$idx];
        }
      }
      
    }
    return $r->match;
  }
  
  function composite_uri() {
    // cross platform URI code by Angsuman Chakraborty
    $port = "";
    if ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS']=='on' ) {
      $_SERVER['FULL_URL'] = 'https://';
      if ( $_SERVER['SERVER_PORT']!='443' ) {
        $port = ':' . $_SERVER['SERVER_PORT'];
      }
    } else {
      $_SERVER['FULL_URL'] = 'http://';
      if ( $_SERVER['SERVER_PORT']!='80' ) {
        $port = ':' . $_SERVER['SERVER_PORT'];
      }
    }
    if ( isset( $_SERVER['REQUEST_URI'] ) ) {
      $script = $_SERVER['REQUEST_URI'];
    } else {
      $script = $_SERVER['PHP_SELF'];
      if ( $_SERVER['QUERY_STRING']>' ' ) {
        $script .= '?'.$_SERVER['QUERY_STRING'];
      }
    }
    if ( isset( $_SERVER['HTTP_HOST'] ) ) {
      $_SERVER['FULL_URL'] .= $_SERVER['HTTP_HOST'] . $port . $script;
    } else {
      $_SERVER['FULL_URL'] .= $_SERVER['SERVER_NAME'] . $port . $script;
    }
    return $_SERVER['FULL_URL'];
  }
  
  function hasErrors() {
    if ( $this->error === true )
      return true;
    return false;
  }
  
  function propagate() {
    $allowed = $this->persisted_vars;
    $_SESSION['params'] = array();
    foreach( $this->params as $param=>$val ) {
      if (in_array($param, $allowed)) {
        $_SESSION['params'][$param] = $val;
      }
    }
  }
  
  function restore() {
    if (!(isset($_SESSION['params']))) return false;
    foreach( $_SESSION['params'] as $param=>$val ) {
      $this->params[$param] = $val;
      $this->$param =& $this->params[$param];
    }
  }
  
}

?>