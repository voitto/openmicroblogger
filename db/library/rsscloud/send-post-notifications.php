<?php

add_action( 'publish_post', 'rsscloud_schedule_post_notifications' );
function rsscloud_schedule_post_notifications( ) {
	if ( !defined( 'RSSCLOUD_NOTIFICATIONS_INSTANT' ) || !RSSCLOUD_NOTIFICATIONS_INSTANT )
		wp_schedule_single_event( time( ), 'rsscloud_send_post_notifications_action' );
	else
		rsscloud_send_post_notifications( );

}

add_action( 'rsscloud_send_post_notifications_action', 'rsscloud_send_post_notifications' );
function rsscloud_send_post_notifications( ) {
	$rss2_url = get_bloginfo( 'rss2_url' );
	$notify = rsscloud_get_hub_notifications( );
	if ( !is_array( $notify ) )
		$notify = array( );

	foreach ( $notify[$rss2_url] as $notify_url => $n ) {
		if ( $n['status'] == 'active' ) {
			if ( $n['protocol'] == 'http-post' ) {
				$url = parse_url( $notify_url );
				$port = 80;
				if ( !empty( $url['port'] ) )
					$port = $url['port'];

				$result = wp_remote_post( $notify_url, array( 'method' => 'POST', 'timeout' => RSSCLOUD_HTTP_TIMEOUT, 'user-agent' => RSSCLOUD_USER_AGENT, 'port' => $port, 'body' => array( 'url' => $rss2_url ) ) );

				$need_update = false;
				if ( $result['response']['code'] != 200 ) {
					$notify[$rss2_url][$notify_url]['failure_count']++;
					$need_update = true;
				} elseif ( $notify[$rss2_url][$notify_url]['failure_count'] > RSSCLOUD_MAX_FAILURES ) {
					$notify[$rss2_url][$notify_url]['status'] = 'suspended';
					$need_update = true;
				}
			}
		}
	} // foreach

	if ( $need_update )
		rsscloud_update_hub_notifications( $notify );

}
