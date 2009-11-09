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
   
   dbscript -- restful openid framework
   Copyright (C) 2008 Brian Hendrickson
   
   This library is free software; you can redistribute it and/or
   modify it under the terms of the MIT License.
   
   This library is distributed in the hope that it will be useful,
   but without any warranty; without even the implied warranty of
   merchantability or fitness for a particular purpose.
   
   Author
     Brian Hendrickson - http://brianhendrickson.com
   
   Version 0.1, 16-Nov-2006
     initial release at pdxphp meeting
   
   Version 0.1.1, 15-Feb-2007
     added gary court's content-negotiation library
   
   Version 0.1.2, 19-Feb-2007
     changed object instantiation routine
   
   Version 0.1.3, 23-Feb-2007
     fixed call-time pass-by-reference error in View
   
   Version 0.2.0, 19-Mar-2007
     added openid authentication to security plugin
   
   Version 0.3.0, 10-Jun-2007
     models for Group, Membership, Identity
   
   Version 0.5.0, 12-August-2008
     new templates: vcard, hcard, ics, rdf, json, atom
   
   Version 0.6.0, 22-October-2008
     apps, openappstore
   
   */
   
$version = '0.6.0';

  /**
   * directory paths
   */

global $views,$app,$config,$env,$exec_time,$version,$response;
global $variants,$request,$loader,$db,$logic;

  /**
   * load config
   */

if (file_exists('config/config.php'))
  require('config/config.php');
else
  require('config.php');

  /**
   * memcached
   */

if (MEMCACHED) {
  $perma = parse_url( $_SERVER['REQUEST_URI'] );
  $_PERMA = explode( "/", $perma['path'] );
  @array_shift( $_PERMA );
  if ( isset($_PERMA[0]) && $_PERMA[0] != basename($_SERVER['PHP_SELF']) ){
    require_once 'db/library/pca/pca.class.php';
    $cache = PCA::get_best_backend();
    if ( $cache->exists( $_SERVER['REQUEST_URI'] )) {
  		header( 'Location: '.$cache->get( $_SERVER['REQUEST_URI'] ), TRUE, 301 );
  		exit;
  	}
  }
  require_once 'db/library/pca/pca.class.php';
  $cache = PCA::get_best_backend();
  $_SERVER['FULL_URL'] = 'http://';
  if ( $_SERVER['SERVER_PORT']!='80' ) {
    $port = ':' . $_SERVER['SERVER_PORT'];
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
  global $pretty_url_base;
  if (isset($pretty_url_base) && !empty($pretty_url_base)) {
    if (!empty($_SERVER['QUERY_STRING']))
      $url = $pretty_url_base.'/?'.$_SERVER['QUERY_STRING'];
    else
      $url = $pretty_url_base.'/';
  } else {
    $url = $_SERVER['FULL_URL'];
  }
  if($cache->exists($url) && $cache->exists($url.'type')){
    header( 'Content-Type: '.$cache->get($url.'type') );
    header( "Content-Disposition: inline" );
    echo $cache->get($url);
    exit;
  }
}

  // set path to db directory
if (is_dir('db'))
  $app = 'db' . DIRECTORY_SEPARATOR;
elseif (is_dir('site' . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR))
  $app = 'site' . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR;
else
  trigger_error( 'path to dbscript not found', E_USER_ERROR );

$GLOBALS['PATH'] = array();
$GLOBALS['PATH']['app'] = $app;
$GLOBALS['PATH']['library'] = $app . 'library' . DIRECTORY_SEPARATOR;
$GLOBALS['PATH']['controllers'] = $app . 'controllers' . DIRECTORY_SEPARATOR;
$GLOBALS['PATH']['models'] = $app . 'models' . DIRECTORY_SEPARATOR;
$GLOBALS['PATH']['plugins'] = $app . 'plugins' . DIRECTORY_SEPARATOR;
$GLOBALS['PATH']['dbscript'] = $GLOBALS['PATH']['library'] . 'dbscript' . DIRECTORY_SEPARATOR;

  /**
   * load dbscript minimal functions & classes
   */

foreach( array(
    '_functions',
    'bootloader',
    'mapper',
    'route',
    'genericiterator',
    'collection',
    'view',
    'cookie'
  ) as $module ) {

  include $GLOBALS['PATH']['dbscript'] . $module . '.php';
  
}

  // load HTTP_Negotiate by Gary Court
lib_include( 'http_negotiate' );

  // load Cake's inflector
lib_include( 'inflector' );


error_reporting( E_ALL & ~E_NOTICE & ~E_WARNING );
$dbscript_error_handler = set_error_handler( 'dbscript_error' );


  /**
   * cross-platform magic-quotes init
   */

  // turn off magic quotes
@set_magic_quotes_runtime(0);

  // if get_magic_quotes_gpc, strip quotes or slashes
if ( get_magic_quotes_gpc() ) {
  if ( @ini_get( 'magic_quotes_sybase' )=='1' ) {
    $_GET = magic_quotes_stripquotes($_GET);
    $_POST = magic_quotes_stripquotes($_POST);
    $_COOKIE = magic_quotes_stripquotes($_COOKIE);
    $_REQUEST = magic_quotes_stripquotes($_REQUEST);
  } else {
    $_GET = magic_quotes_stripslashes($_GET);
    $_POST = magic_quotes_stripslashes($_POST);
    $_COOKIE = magic_quotes_stripslashes($_COOKIE);
    $_REQUEST = magic_quotes_stripslashes($_REQUEST);
  }
}


  /**
   * routes
   */

  // create a request mapper object to regex-match the URI to a Route
$request = new Mapper();

  // add a new Route
$request->connect(
  
  // route pattern
  'static/:staticresource',
  
  array(
    'requirements' => array ( '[A-Za-z0-9_.]+' )
  )

);
// load static-file-cache and debug aspects
//include $GLOBALS['PATH']['plugins'] . 'renderer.php';

// this doesn't do anything because the aspect-filter was deleted XXX
//$request->routematch();


$request->connect(
  'admin',
  array(
    'action'=>'index',
    'resource'=>'admin'
  )
);


  /**
   * read the configuration file
   */

include $GLOBALS['PATH']['library'] . 'yaml.php';

$loader = new Horde_Yaml();

if ( file_exists( $app . 'config.yml' ) ) {
  extract($loader->load(file_get_contents($app.'config.yml')));
  extract( $$env['enable_db'] );
} else {
  $env = array('app_folder'=>'app');
}


// if app folder exists, re-config for the app

if (is_dir( $env['app_folder'] )) {
  $app = $env['app_folder'] . DIRECTORY_SEPARATOR;
  $appdir = $app;
  if ( file_exists( $app . 'config' . DIRECTORY_SEPARATOR . 'config.yml' ) ) {
    extract($loader->load(file_get_contents($app . 'config' . DIRECTORY_SEPARATOR .'config.yml')));
    extract( $$env['enable_db'] );
    if (isset($env['boot']))
      $appdir = $app.$env['boot'].DIRECTORY_SEPARATOR;
    else
      $appdir = $app.'omb'.DIRECTORY_SEPARATOR;
    $GLOBALS['PATH']['app'] = $app;
    $app = $appdir;
    $GLOBALS['PATH']['controllers'] = $appdir . 'controllers' . DIRECTORY_SEPARATOR;
    $GLOBALS['PATH']['models'] = $appdir . 'models' . DIRECTORY_SEPARATOR;
  }
  if (is_dir( $appdir . 'plugins' . DIRECTORY_SEPARATOR ))
    $GLOBALS['PATH']['plugins'] = $appdir . 'plugins' . DIRECTORY_SEPARATOR;
}




// debug mode

if ($env['debug_enabled']) {
  ini_set('display_errors','1');
  ini_set('display_startup_errors','1');
  error_reporting (E_ALL & ~E_NOTICE );
  global $exec_time;
  $exec_time = microtime_float();
}


// set up wp-config folder

$content_config = 'wp-content'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.yml';

if (file_exists( $content_config )) {
  
  // if    exists /wp-content/config/config.yml
  // then  use /wp-content
  
  // theme MUST be present in /wp-content/themes
  
  // plugins from /wp-content/plugins will take precedent
  // todo wp-plugins not supported yet in this plugins folder
  
  extract( $loader->load( file_get_contents( $content_config )));
  extract( $$env['enable_db'] );
  
}


$wp_theme = "wp-content".DIRECTORY_SEPARATOR."themes".DIRECTORY_SEPARATOR.$env['theme'];

if ((file_exists($wp_theme))) {
  $GLOBALS['PATH']['content_plugins'] = 'wp-content'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR;
  $GLOBALS['PATH']['themes'] = "wp-content".DIRECTORY_SEPARATOR."themes".DIRECTORY_SEPARATOR;
} else {
  $GLOBALS['PATH']['themes'] = $request->template_path . 'wp-themes' . DIRECTORY_SEPARATOR;
}

// set up the content-negotiation template paths

if ( is_dir( $app . $env['view_folder'] ) )
  $request->set_template_path( $app . $env['view_folder'].DIRECTORY_SEPARATOR );
else
  $request->set_template_path( $env['view_folder'].DIRECTORY_SEPARATOR );
  
if ( is_dir( $app . $env['layout_folder'] ) )
  $request->set_layout_path( $app . $env['layout_folder'].DIRECTORY_SEPARATOR );
else
  $request->set_layout_path( $env['layout_folder'].DIRECTORY_SEPARATOR );


  /**
   * connect to the database with settings from config.yml
   */

  // load dbscript database support classes
db_include( array(
  'database',
  'model',
  'record',
  'recordset',
  'resultiterator',
  $adapter
));

if (DB_NAME)
  $database = DB_NAME;

if (DB_USER)
  $username = DB_USER;

if (DB_PASSWORD)
  $password = DB_PASSWORD;

if (DB_HOST)
  $host = DB_HOST;


  // init the Database ($db) object and connect to the database
$db = new $adapter(
  $host,
  $database,
  $username,
  $password
);


/**
 * boot the loads
 */

$loader = new BootLoader();

$loader->start();

if ( $db->just_get_objects() )
  return;


/**
 * connect pre-plugin routes
 */

// doesn't work XXX
//$request->connect( 'migrate' );


/**
 * set up wp theme and plugin paths
 */

$wp_theme = "wp-content".DIRECTORY_SEPARATOR."themes".DIRECTORY_SEPARATOR.$env['theme'];

if ((file_exists($wp_theme))) {
  $GLOBALS['PATH']['content_plugins'] = 'wp-content'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR;
  $GLOBALS['PATH']['themes'] = "wp-content".DIRECTORY_SEPARATOR."themes".DIRECTORY_SEPARATOR;
} else {
  $GLOBALS['PATH']['themes'] = $env['themepath'.$env['theme']].DIRECTORY_SEPARATOR;
}


/**
 * OMB MU setup
 */

$params = array_merge($_GET,$_POST);
$stream = false;
list($subdomain, $rest) = explode('.', $_SERVER['SERVER_NAME'], 2);
// XXX subdomain upgrade
if ($pretty_url_base && !mu_url() && !('http://'.$subdomain.".".$rest == $pretty_url_base)) {
  $request->base = 'http://'.$subdomain.".".$rest;
  $request->domain = $subdomain.".".$rest;
  $pretty_url_base = $request->base;
  $stream = $subdomain;
// XXX subdomain upgrade
} elseif (mu_url()) {
  $pattern='/(\?)?twitter\/([a-z]+)(\/?)/';
  if ( 1 <= preg_match_all( $pattern, $request->uri, $found )) {
	  if ($pretty_url_base && environment('subdomains')){
		  $trail = "/";
		  $pattern2='/(\?)?twitter\/([a-z]+)(\/?)(\/.+)/';
		  if ( 1 <= preg_match_all( $pattern2, $request->uri, $found2 ))
        $trail = $found2[4][0];
      redirect_to('http://'.$found[2][0].".".$request->domain.$trail);
	  }
    $uri = $request->uri;
    $tags[] = $found;
    // XXX subdomain upgrade
    $repl = 'twitter/'.$tags[0][2][0].$tags[0][3][0];
    $request->uri = str_replace($repl,'',$uri);
    $request->prefix = $repl;
    $request->setup();
    $trail = '';
    if (empty($tags[0][3][0]))
      $trail = "/";
    $request->base = substr($uri,0,strpos($uri,$tags[0][0][0])+(strlen($repl)+1)).$trail;
  }
  $stream = $tags[0][2][0];
} elseif (isset($params['username']) && isset($params['password']) && !isset($_FILES['media'])) {
  $sql = "SELECT nickname,profile_id FROM shorteners WHERE nickname LIKE '".$db->escape_string($params['username'])."'";
  $sql .= " AND password LIKE '".$db->escape_string($params['password'])."'";
  $result = $db->get_result( $sql );
  if ( $db->num_rows($result) == 1 ) {
    if (!($pretty_url_base && !mu_url() && !('http://'.$subdomain.".".$rest == $pretty_url_base))) {
      $request->base = 'http://'.$subdomain.".".$request->domain;
      $request->domain = $subdomain.".".$request->domain;
      $pretty_url_base = $request->base;
    }
    $stream = $db->result_value( $result, 0, "nickname" );
    global $db,$request;
    global $person_id;
    global $api_methods,$api_method_perms;
    if (array_key_exists($request->action,$api_method_perms)) {
      $arr = $api_method_perms[$request->action];
      if ($db->models[$arr['table']]->can($arr['perm']))
        return;
    }
    $Identity =& $db->get_table( 'identities' );
    $Person =& $db->get_table( 'people' );
    $i = $Identity->find($db->result_value( $result, 0, "profile_id" ));
    $p = $Person->find( $i->person_id );
    if (!(isset( $p->id ) && $p->id > 0)) {
      header('HTTP/1.1 401 Unauthorized');
      echo 'BAD LOGIN';
      exit;
    }
    $person_id = $p->id;
  }
}
if ($stream) {
  if (!$db->table_exists('blogs')) {
    $Blog =& $db->model('Blog');
    $Blog->save();
  }
  $sql = "SELECT prefix FROM blogs WHERE nickname LIKE '".$db->escape_string($stream)."'";
  $result = $db->get_result( $sql );
  if ( $db->num_rows($result) == 1 ) {
    global $prefix;
    $prefix = $db->result_value( $result, 0, "prefix" )."_";
    $db->prefix = $prefix;
  }
}


/**
 * load saved config
 */

$Setting =& $db->model('Setting');
$Setting->find_by(array(
  'eq'    => 'like',
  'name'  => 'config%'
));
while ($s = $Setting->MoveNext()) {
  $set = split('\.',$s->name);
  if (is_array($set) && $set[0] == 'config') {
    if ($set[1] == 'env') {
      $env[$set[2]] = $s->value;
    } elseif ($set[1] == 'perms') {
      $tab =& $db->models[$set[2]];
      if ($tab)
        $tab->permission_mask( $set[3],$s->value,$set[4] );
    }
  }
}


/**
 * overrides from config.php
 */

if (isset($env['max_upload_mb']))
  $db->max_upload_megabytes($env['max_upload_mb']);

if (INTRANET)
  $env['authentication'] = 'password';

//if (UPLOADS)
//  $env['collection_cache']['posts']['location'] = UPLOADS;

//if (UPLOADS)
//  $env['collection_cache']['identities']['location'] = UPLOADS;

// PHP5 only set server timezone
if (function_exists(timezone_abbreviations_list) && environment('timezone'))
  if (setting('timezone'))
    set_tz_by_offset(setting('timezone'));
  else
    set_tz_by_offset(environment('timezone'));


/**
 * load virtual API methods
 */

global $api_methods,$api_method_perms;
$api_methods = array();
$api_method_perms = array();
$Method =& $db->model('Method');
$Method->set_order('asc');
$Method->find_by(array(
  'eq'        => 'like',
  'function'  => 'api_%'
));
while ($m = $Method->MoveNext()) {
  $api_method_perms[$m->function] = array('table'=>$m->resource,'perm'=>$m->permission);
  $api_methods[$m->function] = $m->code;
  $patterns = explode( '/', $m->route );
  $requirements = array();
  foreach ( $patterns as $pos => $str ) {
    if ( substr( $str, 0, 1 ) == ':' ) {
		  $requirements[] = '[A-Za-z0-9_.]+';
    }
  }
	$routesetup = array(
	  'action'=>$m->function,
	  'resource'=>$m->resource
	);
	if (count($requirements) > 0)
		$routesetup['requirements'] = $requirements;
  $request->connect(
    $m->route,
    $routesetup
  );
  if ($m->omb)
    before_filter( 'authenticate_with_omb', $m->function );
  if ($m->http)
    before_filter( 'authenticate_with_http', $m->function );
  if ($m->oauth)
    before_filter( 'authenticate_with_oauth', $m->function );
}


/**
 * load plugins
 */

if ( isset( $env ))
  while ( list( $key, $plugin ) = each( $env['plugins'] ) )
    load_plugin( $plugin );


/**
 * connect more Routes to the Mapper
 */

$request->connect(
  ':resource/:id/email/:ident',
  array(
    'requirements' => array ( '[A-Za-z0-9_.]+', '[0-9]+', '[A-Za-z0-9]+' )
  )
);

$request->connect(
  ':resource/page/:page/:action',
  array(
    'requirements' => array ( '[A-Za-z0-9_.]+', '[0-9]+','[A-Za-z0-9_.]+' )
  )
);

$request->connect(
  ':resource/page/:page',
  array(
    'requirements' => array ( '[A-Za-z0-9_.]+', '[0-9]+' )
  )
);

$request->connect(
  ':resource/:id/:action',
  array(
    'requirements' => array ( '[A-Za-z0-9_.]+', '[0-9]+', '[A-Za-z0-9_.]+' )
  )
);

$request->connect(
  ':resource/:id',
  array(
    'requirements' => array ( '[A-Za-z0-9_.]+', '[0-9]+' )
  )
);

$request->connect(
  ':resource/:action',
  array(
    'requirements' => array ( '[A-Za-z0-9_.]+', '[A-Za-z0-9_.]+' )
  )
);

$request->connect(
  ':resource/:id/:action/partial',
  array(
    'requirements' => array ( '[A-Za-z0-9_.]+', '[0-9]+', '[A-Za-z0-9_.]+' )
  )
);

$request->connect(
  ':resource/:action/partial',
  array(
    'requirements' => array ( '[A-Za-z0-9_.]+', '[A-Za-z0-9_.]+' )
  )
);

$request->connect(
  ':resource',
  array(
    'requirements' => array ( '[A-Za-z0-9_.]+' )
  )
);

$request->connect( '', array( 'resource'=>$env['goes'], 'action'=>'get' ) );

  // (for debugging) print the current request variables and die
#aspect_join_functions( 'routematch', 'catch_params' );

$request->routematch();

//print_r($request->activeroute); echo '<br /><br />'; print_r($request->params); exit;

/**
 * attach functions to aspect crosscuts
 */

  // load data model if these model methods are triggered
before_filter( 'load_model', 'delete_from_post' );
before_filter( 'load_model', 'insert_from_post' );
before_filter( 'load_model', 'update_from_post' );
before_filter( 'load_model', 'fields_from_request' );
before_filter( 'load_model', 'MoveFirst' );
before_filter( 'load_model', 'MoveNext' );
before_filter( 'load_model', 'base' );
before_filter( 'load_model', 'find' );

  // add a filter to persist submitted data on error
before_filter( 'session_error', 'handle_error' );

  // activate Taint Mode to validate each input
before_filter( 'regex_validate', 'save_record' );

  // read the Access List and verify action permissions
before_filter( 'model_security', $request->action );

  // if public resource, ping the search index server
after_filter( 'send_ping', 'insert_from_post' );
after_filter( 'send_ping', 'update_from_post' );

  // echo value after single-field Ajax PUT call
after_filter( 'ajax_put_field', 'update_from_post' );
after_filter( 'ajax_put_field', 'insert_from_post' );


// authenticate yourself without OpenID

//test_log_in();

function test_log_in() {
  $person_id = 1;
  set_cookie($person_id);
  $_SESSION['openid_complete'] = true;
}


/**
 * negotiate the best content-type for the client
 */

$response = new View();

render( 'action', $request->action );

