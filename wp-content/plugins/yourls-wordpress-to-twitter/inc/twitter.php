<?php

// Send a message to Twitter. Returns boolean for success or failure.
function wp_ozh_yourls_tweet_it($username, $password, $message){

	$ozh_yourls = get_option('ozh_yourls');
	
	if ($ozh_yourls['other'] == 'rply' && isset($ozh_yourls['twitter_api']) && !empty($ozh_yourls['twitter_api'])) {
	  $api_url = $ozh_yourls['twitter_api'];
		if (strpos($api_url, 'rp.ly')) {
		  $body =    array( 'status'=>$message, 'username' => $ozh_yourls['rply_login'],'password'=>$ozh_yourls['rply_password'] );
			$headers = array();
		} else {
		  $body =    array( 'status'=>$message );
		  $headers = array( 'Authorization' => 'Basic '.base64_encode("$username:$password") );
		}
  } else {
    $api_url = 'http://twitter.com/statuses/update.json';
	  $body =    array( 'status'=>$message );
	  $headers = array( 'Authorization' => 'Basic '.base64_encode("$username:$password") );
  }

	$result = wp_ozh_yourls_fetch_url( $api_url, 'POST', $body, $headers );
	
	// Basic check for success or failure: if body contains <error>some string</error>, not good
	return ( preg_match_all('!<error>[^<]+</error>!', $result, $matches) !== 1 );
}
