<?php

// Manual tweet from the Edit interface
function wp_ozh_yourls_promote() {
	check_ajax_referer( 'yourls' );
	$account = $_POST['yourls_twitter_account'];
	$post_id = (int) $_POST['yourls_post_id'];

	if ( wp_ozh_yourls_send_tweet( stripslashes($_POST['yourls_tweet']) ) ) {
		$result = "Success! Post was promoted on <a href='http://twitter.com/$account'>@$account</a>!";
		update_post_meta($post_id, 'yourls_tweeted', 1);
	} else {
		$result = "Bleh. Could not promote this post on <a href='http://twitter.com/$account'>@$account</a>. Maybe Twitter is down? Please try again later!";
	}
	$x = new WP_AJAX_Response( array(
		'data' => $result
	) );
	$x->send();
	die('1');	
}

// Manual reset of the short URL from the Edit interface
function wp_ozh_yourls_reset_url() {
	check_ajax_referer( 'yourls' );
	$post_id = (int) $_POST['yourls_post_id'];

	$old_shorturl = $_POST['yourls_shorturl'];
	delete_post_meta($post_id, 'yourls_shorturl');
	$shorturl = wp_ozh_yourls_geturl( $post_id );

	if ( $shorturl ) {
		$result = "New short URL generated: <a href='$shorturl'>$shorturl</a>";
		update_post_meta($post_id, 'yourls_shorturl', $shorturl);
	} else {
		$result = "Bleh. Could not generate short URL. Maybe the URL shortening service is down? Please try again later!";
	}
	$x = new WP_AJAX_Response( array(
		'data' => $result,
		'supplemental' => array(
			'old_shorturl' => $old_shorturl,
			'shorturl' => $shorturl
		)
	) );
	$x->send();
	die('1');	
}

// Function called when new post. Expecting post object.
function wp_ozh_yourls_newpost( $post ) {
	global $wp_ozh_yourls;
	$post_id = $post->ID;
	$url = get_permalink( $post_id );
	
	if ( $post->post_type != 'post' && $post->post_type != 'page' )
		return;
		
	// Generate short URL ?
	if ( !wp_ozh_yourls_generate_on( $post->post_type ) )
		return;
	
	$title = get_the_title($post_id);
	$url = get_permalink ($post_id);
	$short = wp_ozh_yourls_get_new_short_url( $url );
	
	// Tweet short URL ?
	if ( !wp_ozh_yourls_tweet_on( $post->post_type ) )
		return;

	if ( !get_post_custom_values( 'yourls_tweeted', $post_id ) ) {
		// Not tweeted yet
		$tweet = wp_ozh_yourls_maketweet( $short, $title );
		if ( wp_ozh_yourls_send_tweet( $tweet ) )
			update_post_meta($post_id, 'yourls_tweeted', 1);
	}
	
}

// Tweet something. Returns boolean for success or failure.
function wp_ozh_yourls_send_tweet( $tweet ) {
	global $wp_ozh_yourls;
	require_once( dirname(__FILE__) . '/twitter.php' );
	return ( wp_ozh_yourls_tweet_it($wp_ozh_yourls['twitter_login'], $wp_ozh_yourls['twitter_password'], $tweet) );
}

// The WP <-> YOURLS bridge function: get short URL of a WP post. Returns string(url)
function wp_ozh_yourls_get_new_short_url( $url, $post_id = 0 ) {
	// Init plugin (redundant when in admin, needed when plugin called from public part, for instance triggered by a template tag)
	global $wp_ozh_yourls;
	if (!$wp_ozh_yourls)
		wp_ozh_yourls_admin_init();
	// Get short URL
	$shorturl = wp_ozh_yourls_api_call( wp_ozh_yourls_service(), $url);

	// Store short URL in a custom field
	if ($post_id && $shorturl)
		update_post_meta($post_id, 'yourls_shorturl', $shorturl);

	return $shorturl;
}

// Tap into one of the available APIs. Return a short URL or false if error
function wp_ozh_yourls_api_call( $api, $url) {
	global $wp_ozh_yourls;

	$shorturl = '';

	switch( $api ) {

		case 'yourls-local':
			global $yourls_reserved_URL;
			require_once($wp_ozh_yourls['yourls_path']);
			$yourls_db = new wpdb(YOURLS_DB_USER, YOURLS_DB_PASS, YOURLS_DB_NAME, YOURLS_DB_HOST);
			$yourls_result = yourls_add_new_link($url, '', $yourls_db);
			if ($yourls_result)
				$shorturl = $yourls_result['shorturl'];
			break;
			
		case 'yourls-remote':
			$api_url = sprintf( $wp_ozh_yourls['yourls_url'] . '?username=%s&password=%s&url=%s&format=json&action=shorturl',
				$wp_ozh_yourls['yourls_login'], $wp_ozh_yourls['yourls_password'], urlencode($url) );
			$json = wp_ozh_yourls_remote_json( $api_url );
			if ($json)
				$shorturl = $json->shorturl;
			break;
		
		case 'bitly':
			$api_url = sprintf( 'http://api.bit.ly/shorten?version=2.0.1&longUrl=%s&login=%s&apiKey=%s',
				urlencode($url), $wp_ozh_yourls['bitly_login'], $wp_ozh_yourls['bitly_password'] );
			$json = wp_ozh_yourls_remote_json( $api_url );
			if ($json)
				$shorturl = $json->results->$url->shortUrl; // bit.ly's API makes ugly JSON, seriously, tbh
			break;

		case 'rply':
			$api_url = sprintf( 'http://rp.ly/api/trim_url.json?url=%s&username=%s&password=%s',
				urlencode($url), $wp_ozh_yourls['rply_login'], $wp_ozh_yourls['rply_password'] );
			$json = wp_ozh_yourls_remote_json( $api_url );
			if ($json)
				$shorturl = $json->url;
			break;
			
		case 'trim':
			$api_url = sprintf( 'http://api.tr.im/api/trim_url.json?url=%s&username=%s&password=%s',
				urlencode($url), $wp_ozh_yourls['trim_login'], $wp_ozh_yourls['trim_password'] );
			$json = wp_ozh_yourls_remote_json( $api_url );
			if ($json)
				$shorturl = $json->url;
			break;
		
		case 'pingfm':
			$api_url = 'http://api.ping.fm/v1/url.create';
			$body = array(
				'api_key' => 'd0e1aad9057142126728c3dcc03d7edb',
				'user_app_key' => $wp_ozh_yourls['pingfm_user_app_key'],
				'long_url' => $url
			);
			$xml = wp_ozh_yourls_fetch_url( $api_url, 'POST', $body );
			if ($xml) {
				preg_match_all('!<short_url>[^<]+</short_url>!', $xml, $matches);
				$shorturl = $matches[0][0];				
			}
			break;
		
		case 'tinyurl':
			$api_url = sprintf( 'http://tinyurl.com/api-create.php?url=%s', urlencode($url) );
			$shorturl = wp_ozh_yourls_remote_simple( $api_url );
			break;
		
		case 'isgd':
			$api_url = sprintf( 'http://is.gd/api.php?longurl=%s', urlencode($url) );
			$shorturl = wp_ozh_yourls_remote_simple( $api_url );
			break;
			
		default:
			die('Error, unknown service');
	
	}
	
	// at this point, if ($shorturl), it should contain expected short URL. Potential TODO: deal with edge cases?
	
	return $shorturl;
}


// Poke a remote API that returns a simple string
function wp_ozh_yourls_remote_simple( $url ) {
	return wp_ozh_yourls_fetch_url( $url );
}

// Poke a remote API with JSON and return a object (decoded JSON) or NULL if error
function wp_ozh_yourls_remote_json( $url ) {
	$input = wp_ozh_yourls_fetch_url( $url );
	if ( !class_exists( 'Services_JSON' ) )
		require_once(dirname(__FILE__).'/pear_json.php');
	$json = new Services_JSON();
	$obj = $json->decode($input);
	return $obj;
	// TODO: some error handling ?
}


// Fetch a remote page. Input url, return content
function wp_ozh_yourls_fetch_url( $url, $method='GET', $body=array(), $headers=array() ) {
	$request = new WP_Http;
	$result = $request->request( $url , array( 'method'=>$method, 'body'=>$body, 'headers'=>$headers ) );

	// Success?
	if ( !is_wp_error($result) && isset($result['body']) ) {
		return $result['body'];

	// Failure (server problem...)
	} else {
		// TODO: something more useful ?
		return false;
	}
}


// Parse the tweet template and make a 140 char string
function wp_ozh_yourls_maketweet( $url, $title ) {
	global $wp_ozh_yourls;
	// Replace %U with short url
	$tweet = str_replace('%U', $url, $wp_ozh_yourls['twitter_message']);
	// Now replace %T with as many chars as possible to keep under 140
	$maxlen = 140 - ( strlen( $tweet ) - 2); // 2 = "%T"
	if (strlen($title) > $maxlen) {
		$title = substr($title, 0, ($maxlen - 3)) . '...';
	}
	
	$tweet = str_replace('%T', $title, $tweet);
	return $tweet;
}

// Init plugin options
function wp_ozh_yourls_admin_init(){
	global $wp_ozh_yourls;
	if (function_exists('register_setting')) // testing for the function existence in case we're initting out of of the admin scope
		register_setting( 'wp_ozh_yourls_options', 'ozh_yourls', 'wp_ozh_yourls_sanitize' );
	$wp_ozh_yourls = get_option('ozh_yourls');
}


// Generate on... $type = 'post' or 'page', returns boolean
function wp_ozh_yourls_generate_on( $type ) {
	global $wp_ozh_yourls;
	return ( $wp_ozh_yourls['generate_on_'.$type] == 1 );
}

// Send tweet on... $type = 'post' or 'page', returns boolean
function wp_ozh_yourls_tweet_on( $type ) {
	global $wp_ozh_yourls;
	return ( $wp_ozh_yourls['tweet_on_'.$type] == 1 );
}

// Determine which service to use. Return string
function wp_ozh_yourls_service() {
	global $wp_ozh_yourls;
	if ( $wp_ozh_yourls['service'] == 'yourls' && $wp_ozh_yourls['location'] == 'local' )
		return 'yourls-local';
	
	if ( $wp_ozh_yourls['service'] == 'yourls' && $wp_ozh_yourls['location'] == 'remote' )
		return 'yourls-remote';
		
	if ( $wp_ozh_yourls['service'] == 'other')
		return $wp_ozh_yourls['other'];
}

// Hooked into 'ozh_adminmenu_icon', this function give this plugin its own icon
function wp_ozh_yourls_customicon($in) {
	return WP_PLUGIN_URL.'/'.plugin_basename(dirname(dirname(__FILE__))).'/res/icon.gif';
}

// Add the 'Settings' link to the plugin page
function wp_ozh_yourls_plugin_actions($links) {
	$links[] = "<a href='options-general.php?page=ozh_yourls'><b>Settings</b></a>";
	return $links;
}



