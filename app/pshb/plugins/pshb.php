<?php

// attach a "hook" to the insert_from_post Model method
after_filter( 'send_to_pshb', 'insert_from_post' );

// the "hook" function itself
function send_to_pshb( &$model, &$rec ) {

global $request;
if ($request->resource == 'posts'){

	$pid = get_profile_id();
	global $blogdata;
	$blogdata['rss2_url'] = $request->url_for(array('resource'=>'api/statuses/user_timeline/')).$pid.'.rss';
  $feed_type = 'rss2';
	if (!defined(PUSHPRESS_CLASS))
		require_once 'wp-content/plugins/pushpress/class-pushpress.php';

	if (!function_exists('pushpress_send_ping'))
		require_once 'wp-content/plugins/pushpress/send-ping.php';

	if ( !defined( 'PUSHPRESS_VERSION' ) )
		define( 'PUSHPRESS_VERSION', '0.1.6' );

	if ( !defined( 'PUSHPRESS_CLASS' ) )
		define( 'PUSHPRESS_CLASS', 'PuSHPress' );

	$pushpress_class = PUSHPRESS_CLASS;
	
	$pushpress = new $pushpress_class( );

	$subs = $pushpress->get_subscribers( get_bloginfo( 'rss2_url' ) );

	foreach ( (array) $subs as $callback => $data ) {
		if ( $data['is_active'] == FALSE )
			continue;

		$pushpress->init( );

		$remote_opt = array(
			'headers'		=> array(
				'format'	=> $feed_type
			),
			'sslverify'		=> FALSE,
			'timeout'		=> $pushpress->http_timeout,
			'user-agent'	=> $pushpress->http_user_agent
		);

	ob_start( );

	$feed_url = FALSE;
		$feed_url = FALSE;


		if ( $feed_type == 'rss2' ) {

			$feed_url = get_bloginfo( 'rss2_url' );

			$remote_opt['headers']['Content-Type'] = 'application/rss+xml';
			$remote_opt['headers']['Content-Type'] .= '; charset=' . get_option( 'blog_charset' );

      global $request;
			$request->set_param('byid',get_profile_id());
			$request->set_param('order','desc');
	    $where = array(
	      'profile_id'=>get_profile_id(),
	      'parent_id'=>0
	    );
			$tweets = new Collection( 'posts', $where );
			$pro = get_profile(get_profile_id());

      render_rss_feed($pro,$tweets,$headers,false,true);
//			@load_template( ABSPATH . WPINC . '/feed-rss2.php' );
		} elseif ( $feed_type == 'atom' ) {

			$feed_url = get_bloginfo( 'atom_url' );
			$remote_opt['headers']['Content-Type'] = 'application/atom+xml';
			$remote_opt['headers']['Content-Type'] .= '; charset=' . get_option( 'blog_charset' );

//			@load_template( ABSPATH . WPINC . '/feed-atom.php' );
		}

		$remote_opt['body'] = ob_get_contents( );
		ob_end_clean( );
		
		$secret = $data['secret'];
		
		if ( !empty( $secret ) ) {
			$remote_opt['headers']['X-Hub-Signature'] = 'sha1=' . hash_hmac(
				'sha1', trim($remote_opt['body']), $secret
			);
		}

//		$response = wp_remote_post( $callback, $remote_opt );
		
		$response = pshbhttp($callback,array('body'=>$remote_opt['body']),$remote_opt['headers']);
		

    print_r($response); exit;
		// look for failures
		if ( is_wp_error( $result ) ) {
			do_action( 'pushpress_ping_wp_error' );
			return FALSE;
		}

		if ( isset( $response->errors['http_request_failed'][0] ) ) {
			do_action( 'pushpress_ping_http_failure' );
			return FALSE;
		}

		$status_code = (int) $response['response']['code'];

		if ( $status_code < 200 || $status_code > 299 ) {
			do_action( 'pushpress_ping_not_2xx_failure' );
			$pushpress->unsubscribe_callback( $feed_url, $callback );
			return FALSE;
		}

	}

}

}
	function pshbhttp( $url, $post_data = null, $headers = null ){
		$ch = curl_init();
	  if (defined("CURL_CA_BUNDLE_PATH"))
	    curl_setopt($ch, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);

	  if ($headers && is_array($headers)) {
  		$hdrs = array();
			foreach ($headers as $h=>$v){
		    $hdrs[] = $h.': '.$v;
			}
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $hdrs);
	  }
	
    $params = array();
    foreach ($post_data as $k => $v)
      $params[] = urlencode($k) . "=" . urlencode(trim($v));





	  curl_setopt($ch, CURLOPT_URL, $url);
	  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
	  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	  curl_setopt($ch, CURLOPT_HEADER, false);
	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	  if (isset($post_data)) {
	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, implode("&", $params));
	  }
	  $response = curl_exec($ch);
	  curl_close ($ch);
	  return $response;
	}
