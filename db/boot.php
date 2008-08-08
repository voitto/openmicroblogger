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
   
   Version 0.5.0, 8-August-2008
     new templates: vcard, hcard, ics, rdf, json, atom
   
   */
   
$version = '0.5.0';

  /**
   * directory paths
   */

global $views,$app,$config,$env,$exec_time,$version,$response;
global $variants,$request,$loader,$db,$logic;



  // set path to db directory
if (is_dir('db'))
  $app = 'db' . DIRECTORY_SEPARATOR;
elseif (is_dir('site'))
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
include $GLOBALS['PATH']['library'] . 'http_negotiate.php';


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
include $GLOBALS['PATH']['plugins'] . 'renderer.php';


$request->routematch();


  /**
   * read the configuration file
   */

include $GLOBALS['PATH']['library'] . 'yaml.php';

$loader = new Horde_Yaml();

if ( file_exists( $app . 'config.yml' ) )
  extract($loader->load(file_get_contents($app.'config.yml')));
else
  trigger_error( 'unable to read dbscript configuration, sorry', E_USER_ERROR );

extract( $$env['enable_db'] );

  // if app folder exists, re-config
if (is_dir( $env['app_folder'] )) {
  $app = $env['app_folder'] . DIRECTORY_SEPARATOR;
  $GLOBALS['PATH']['app'] = $app;
  $GLOBALS['PATH']['controllers'] = $app . 'controllers' . DIRECTORY_SEPARATOR;
  $GLOBALS['PATH']['models'] = $app . 'models' . DIRECTORY_SEPARATOR;
  if ( file_exists( $app . 'config' . DIRECTORY_SEPARATOR . 'config.yml' ) ) {
    extract($loader->load(file_get_contents($app . 'config' . DIRECTORY_SEPARATOR .'config.yml')));
    extract( $$env['enable_db'] );
  }
  if (is_dir( $app . 'plugins' . DIRECTORY_SEPARATOR ))
    $GLOBALS['PATH']['plugins'] = $app . 'plugins' . DIRECTORY_SEPARATOR;
}


if ($env['debug_enabled']) {
  ini_set('display_errors','1');
  ini_set('display_startup_errors','1');
  error_reporting (E_ALL & ~E_NOTICE );
  global $exec_time;
  $exec_time = microtime_float();
}


if ( is_dir( $app . $env['view_folder'] ) )
  $request->set_template_path( $app . $env['view_folder'].DIRECTORY_SEPARATOR );
else
  $request->set_template_path( $env['view_folder'].DIRECTORY_SEPARATOR );
  
if ( is_dir( $app . $env['layout_folder'] ) )
  $request->set_layout_path( $app . $env['layout_folder'].DIRECTORY_SEPARATOR );
else
  $request->set_layout_path( $env['layout_folder'].DIRECTORY_SEPARATOR );

$GLOBALS['PATH']['themes'] = $request->template_path . 'wp-themes' . DIRECTORY_SEPARATOR;

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

//print_r($request->activeroute); exit;


/**
 * attach functions to aspect crosscuts
 */

  // load data model if these model methods are triggered
before_filter( 'load_model', 'delete_from_request' );
before_filter( 'load_model', 'insert_from_request' );
before_filter( 'load_model', 'update_from_request' );
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

?>