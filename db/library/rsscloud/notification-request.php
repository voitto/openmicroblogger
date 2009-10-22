<?php
function rsscloud_hub_process_notification_request( ) {
	// Get the current set of notifications
	$notify = rsscloud_get_hub_notifications( );
	if ( empty( $notify ) )
		$notify = array( );

	// Must provide at least one URL to get notifications about
	if ( empty( $_POST['url1'] ) )
		rsscloud_notify_result( 'false', 'No feed for url1.' );

	// Only support http-post
	$protocol = 'http-post';
	if ( !empty( $_POST['protocol'] ) && strtolower( $_POST['protocol'] ) !== 'http-post' )
		rsscloud_notify_result( 'false', 'Only http-post notifications are supported at this time.' );

	// Assume port 80
	$port = 80;
	if ( !empty( $_POST['port'] ) )
		$port = (int) $_POST['port'];

	// Path is required
	if ( empty( $_POST['path'] ) )
		rsscloud_notify_result( 'false', 'No path provided.' );

	$path = str_replace( '@', '', $_POST['path'] );
	if ( $path{0} != '/' )
		$path = '/' . $path;

	// Process each URL request: url1, url2, url3 ... urlN
	$rss2_url = get_bloginfo( 'rss2_url' );
	$notify_url = $_SERVER['REMOTE_ADDR'] . ':' . $port . $path;
	if (isset($_POST['domain']) && !empty($_POST['domain']))
		$notify_url = $_POST['domain'] . ':' . $port . $path;

	// Attempt a notification to see if it will work
	$result = wp_remote_post( $notify_url, array( 'method' => 'POST', 'timeout' => RSSCLOUD_HTTP_TIMEOUT, 'user-agent' => RSSCLOUD_USER_AGENT, 'port' => $port, 'body' => array( 'url' => $_POST['url1'] ) ) );
	if ( isset( $result->errors['http_request_failed'][0] ) )
		rsscloud_notify_result( 'false', 'Error testing notification URL : ' . $result->errors['http_request_failed'][0] );
	if ( $result['response']['code'] != 200 )
		rsscloud_notify_result( 'false', 'Error testing notification URL.' );

	// Passed all the tests, add this to the list of notifications for
	foreach ( $_POST as $key => $feed_url ) {
		if ( !preg_match( '|url\d+|', $key ) )
			continue;

		// Only allow requests for the RSS2 posts feed
		if ( $feed_url != $rss2_url )
			continue;

		$notify[$feed_url][$notify_url]['protocol'] = $protocol;
		$notify[$feed_url][$notify_url]['status'] = 'active';
		$notify[$feed_url][$notify_url]['failure_count'] = 0;
	}

	rsscloud_update_hub_notifications( $notify );
	rsscloud_notify_result( 'true', 'Registration successful.' );
} // function rsscloud_hub_notify
