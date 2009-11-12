<?php
/*
Plugin Name: RSS Cloud
Plugin URI:
Description: Ping RSS Cloud servers
Version: 0.4.1
Author: Joseph Scott
Author URI: http://josephscott.org/
 */

// Uncomment this to not use cron to send out notifications
# define( 'RSSCLOUD_NOTIFICATIONS_INSTANT', true );

if ( !defined( 'RSSCLOUD_USER_AGENT' ) )
	define( 'RSSCLOUD_USER_AGENT', 'WordPress/RSSCloud 0.4.0' );

if ( !defined( 'RSSCLOUD_MAX_FAILURES' ) )
	define( 'RSSCLOUD_MAX_FAILURES', 5 );

if ( !defined( 'RSSCLOUD_HTTP_TIMEOUT' ) )
	define( 'RSSCLOUD_HTTP_TIMEOUT', 3 );

require dirname( __FILE__ ) . '/data-storage.php';

if ( !function_exists( 'rsscloud_hub_process_notification_request' ) )
	require dirname( __FILE__ ) . '/notification-request.php';

if ( !function_exists( 'rsscloud_schedule_post_notifications' ) )
	require dirname( __FILE__ ) . '/schedule-post-notifications.php';

if ( !function_exists( 'rsscloud_send_post_notifications' ) )
	require dirname( __FILE__ ) . '/send-post-notifications.php';

add_filter( 'query_vars', 'rsscloud_query_vars' );
function rsscloud_query_vars( $vars ) {
	$vars[] = 'rsscloud';
	return $vars;
}

add_action( 'parse_request', 'rsscloud_parse_request' );
function rsscloud_parse_request( $wp ) {
	if ( array_key_exists( 'rsscloud', $wp->query_vars ) ) {
		if ( $wp->query_vars['rsscloud'] == 'notify' )
			rsscloud_hub_process_notification_request( );

		exit;
	}
}

function rsscloud_notify_result( $success, $msg ) {
	$success = strip_tags( $success );
	$success = ent2ncr( $success );
	$success = esc_html( $success );

	$msg = strip_tags( $msg );
	$msg = ent2ncr( $msg );
	$msg = esc_html( $msg );

	header( 'Content-Type: text/xml' );
	echo "<?xml version='1.0'?>\n";
	echo "<notifyResult success='{$success}' msg='{$msg}' />\n";
	exit;
}

add_action( 'rss2_head', 'rsscloud_add_rss_cloud_element' );
function rsscloud_add_rss_cloud_element( ) {
	$cloud = parse_url( get_option( 'home' ) . '/?rsscloud=notify' );

	$cloud['port']		= (int) $cloud['port'];
	if ( empty( $cloud['port'] ) )
		$cloud['port'] = 80;

	$cloud['path']	.= "?{$cloud['query']}";

	$cloud['host']	= strtolower( $cloud['host'] );

	echo "<cloud domain='{$cloud['host']}' port='{$cloud['port']}'";
	echo " path='{$cloud['path']}' registerProcedure=''";
	echo " protocol='http-post' />";
	echo "\n";
}

function rsscloud_generate_challenge( $length = 30 ) {
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $chars_length = strlen( $chars );

    $string = '';
    for ( $i = 0; $i < $length; $i++ ) {
        $string .= $chars{mt_rand( 0, $chars_length )};
    }

    return $string;
}
