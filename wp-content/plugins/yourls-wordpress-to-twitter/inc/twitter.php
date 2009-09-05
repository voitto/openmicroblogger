<?php

// Send a message to Twitter. Returns boolean for success or failure.
function wp_ozh_yourls_tweet_it($username, $password, $message){

    $api_url = 'http://bh.rp.ly/api/statuses/update.json';
	
	$body =    array( 'status'=>$message );
	$headers = array( 'Authorization' => 'Basic '.base64_encode("$username:$password") );
	
	$result = wp_ozh_yourls_fetch_url( $api_url, 'POST', $body, $headers );
	
	// Basic check for success or failure: if body contains <error>some string</error>, not good
	return ( preg_match_all('!<error>[^<]+</error>!', $result, $matches) !== 1 );
}
