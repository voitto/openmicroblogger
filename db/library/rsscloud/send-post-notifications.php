<?php
function rsscloud_send_post_notifications( $rss2_url = false ) {
	if ( $rss2_url === false ) {
		$rss2_url = get_bloginfo( 'rss2_url' );
		if ( defined( 'RSSCLOUD_FEED_URL' ) )
			$rss2_url = RSSCLOUD_FEED_URL;

	}

	do_action( 'rsscloud_feed_notifications', $rss2_url );

	$notify = rsscloud_get_hub_notifications( );
	if ( !is_array( $notify ) )
		$notify = array( );

	$need_update = false;
	foreach ( $notify[$rss2_url] as $notify_url => $n ) {
		if ( $n['status'] != 'active' )
			continue;

		if ( $n['protocol'] == 'http-post' ) {
			$url = parse_url( $notify_url );
			$port = 80;
			if ( !empty( $url['port'] ) )
				$port = $url['port'];

			$result = wp_remote_post( $notify_url, array( 'method' => 'POST', 'timeout' => RSSCLOUD_HTTP_TIMEOUT, 'user-agent' => RSSCLOUD_USER_AGENT, 'port' => $port, 'body' => array( 'url' => $rss2_url ) ) );

			do_action( 'rsscloud_send_notification' );

			if ( !is_wp_error( $result ) )
				$status_code = (int) $result['response']['code'];

			if ( is_wp_error( $result ) || ( $status_code < 200 || $status_code > 299 ) ) {
				do_action( 'rsscloud_notify_failure' );
				$notify[$rss2_url][$notify_url]['failure_count']++;

				if ( $notify[$rss2_url][$notify_url]['failure_count'] > RSSCLOUD_MAX_FAILURES ) {
					do_action( 'rsscloud_suspend_notification_url' );
					$notify[$rss2_url][$notify_url]['status'] = 'suspended';
				}

				$need_update = true;
			} elseif ( $notify[$rss2_url][$notify_url]['failure_count'] > 0 ) {
				do_action( 'rsscloud_reset_failure_count' );
				$notify[$rss2_url][$notify_url]['failure_count'] = 0;
				$need_update = true;
			}
		}
	} // foreach

	if ( $need_update )
		rsscloud_update_hub_notifications( $notify );

}
