<?php
/*
Plugin Name: PuSHPress
Plugin URI:
Description: PubSubHubbub plugin for WordPress that includes the hub
Version: 0.1.6
Author: Joseph Scott
Author URI:
License: GPLv2
Site Wide Only: true
 */
require_once dirname( __FILE__ ) . '/class-pushpress.php';
require_once dirname( __FILE__ ) . '/send-ping.php';

define( 'PUSHPRESS_VERSION', '0.1.6' );

if ( !defined( 'PUSHPRESS_CLASS' ) )
	define( 'PUSHPRESS_CLASS', 'PuSHPress' );

$pushpress_class = PUSHPRESS_CLASS;
$pushpress = new $pushpress_class( );
$pushpress->init( );
