<?php


function pshb_init(){


	// Let other plugins modify various options
//	$pushpress->http_timeout = apply_filters( 'pushpress_http_timeout', 5 );
//	$pushpress->http_user_agent = apply_filters( 'pushpress_http_timeout', 'WordPress/PuSHPress ' . PUSHPRESS_VERSION );

	// Make sure the hubs get listed in the RSS2 and Atom feeds
	add_action( 'rss2_head', 'pubsubhubhub_link_rss2' );
//	add_action( 'atom_head', array( &$pushpress, 'hub_link_atom' ) );
	// Make the built in hub URL work
//	add_action( 'parse_request', array( &$pushpress, 'parse_wp_request' ) );

	// Send out fat pings when a new post is published
//	add_action( 'publish_post', array( &$pushpress, 'publish_post' ) );

  //app_register_init( 'posts', 'index.html', 'Hub', 'pshb', 2 );

}

function pubsubhubhub_link_rss2() {	
	
	require_once 'wp-content/plugins/pushpress/class-pushpress.php';

	define( 'PUSHPRESS_VERSION', '0.1.6' );

	if ( !defined( 'PUSHPRESS_CLASS' ) )
		define( 'PUSHPRESS_CLASS', 'PuSHPress' );

	$pushpress_class = PUSHPRESS_CLASS;
	$pushpress = new $pushpress_class( );
	$pushpress->hub_link_rss2();

}