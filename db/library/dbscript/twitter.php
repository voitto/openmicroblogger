<?php

/**
 * Structal: a Ruby-like language in PHP
 *
 * PHP version 4.3.0+
 *
 * Copyright (c) 2010, Brian Hendrickson
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * 
 *
 * @package   Structal
 * @author    Brian Hendrickson <brian@megapump.com>
 * @copyright 2003-2010 Brian Hendrickson <brian@megapump.com>
 * @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 * @version   Release: @package_version@
 * @link      http://structal.org
 */

/**
 * TwitterToken
 *
 * @package   Structal
 * @author    Brian Hendrickson <brian@megapump.com>
 * @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link      http://structal.org/twittertoken
 */

class TwitterToken extends AuthToken {

  var $api_root = 'https://twitter.com';
  var $method;
  var $consumer;

  function authorize_url() {
    return $this->api_root.'/oauth/authorize?oauth_token='.$this->token;
  }

  function request_token_url() {
    return $this->api_root.'/oauth/request_token';
  }

  function access_token_url() {
    return $this->api_root.'/oauth/access_token';
  }
	
}

/**
 * Twitter
 *
 * @package   Structal
 * @author    Brian Hendrickson <brian@megapump.com>
 * @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link      http://structal.org/twitter
 */

class Twitter {

  var $friends_timeline;
  var $user_timeline;
  var $replies;
  var $token = NULL;

  function Twitter( $key, $secret ){
    $this->method = new OAuthSignatureMethod_HMAC_SHA1();
    $this->consumer = new OAuthConsumer( $key, $secret );
  }

  function request_token() {
	  $tk = new TwitterToken();
	  $req = OAuthRequest::from_consumer_and_token(
		  $this->consumer,
		  $this->token,
		  'GET',
		  $tk->request_token_url(),
		  array()
		);
	  $req->sign_request(
		  $this->method,
		  $this->consumer,
		  $this->token
		);
	  $response = $this->http($req->to_url());
    foreach (explode('&', $response) as $param) {
      $pair = explode('=', $param, 2);
      if (count($pair) != 2) continue;
      $var = urldecode($pair[0]);
      if ($var == 'oauth_token')
        $var = 'token';
      if ($var == 'oauth_token_secret')
        $var = 'secret';
      $tk->$var = urldecode($pair[1]);
    }
    return $tk;
  }

  function authorize_from_request( $token, $secret ){
    $this->token = new OAuthConsumer( $token, $secret );
	  $tk = new TwitterToken();
    $req = OAuthRequest::from_consumer_and_token(
	    $this->consumer,
	    $this->token,
	    'GET',
	    $tk->access_token_url(),
	    array()
	  );
    $req->sign_request(
	    $this->method,
	    $this->consumer,
	    $this->token
	  );
	  $response = $this->http($req->to_url());
    foreach (explode('&', $response) as $param) {
      $pair = explode('=', $param, 2);
      $var = urldecode($pair[0]);
      if ($var == 'oauth_token')
        $var = 'atoken';
      if ($var == 'oauth_token_secret')
        $var = 'asecret';
      $$var = urldecode($pair[1]);
    }
    return array( $atoken, $asecret );
  }

  function authorize_from_access( $token, $secret ){
    $this->token = new OAuthConsumer( $token, $secret );
  }

  function update( $status ) {
	  $tk = new TwitterToken();
	  $req = OAuthRequest::from_consumer_and_token(
		  $this->consumer,
		  $this->token,
		  'POST',
		  $tk->api_root.'/statuses/update.xml',
		  array( 'status' => $status )
		);
	  $req->sign_request(
		  $this->method,
		  $this->consumer,
		  $this->token
		);
	  $response = $this->http( $req->get_normalized_http_url(), $req->to_postdata() );
	  return $response;
  }

  function search( $string ) {
  }

	function http( $url, $post_data = null ){
		$ch = curl_init();
    if (defined("CURL_CA_BUNDLE_PATH"))
      curl_setopt($ch, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    if (isset($post_data)) {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }
    $response = curl_exec($ch);
    curl_close ($ch);
    return $response;
	}

}

/**
 * TwitterHelper
 *
 * @package   Structal
 * @author    Brian Hendrickson <brian@megapump.com>
 * @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link      http://structal.org/twitterhelper
 */

class TwitterHelper extends Helper {
	
	function header( $key ) {
		
		echo <<<EOD
			<meta http-equiv="Content-type" content="text/html; charset=utf-8">
			<script src="http://platform.twitter.com/anywhere.js?id=$key&v=1" type="text/javascript"></script>
EOD;

	}

	function login( $login, $logout ) {

		echo <<<EOD
	    <div id="twitter-connect-placeholder"></div>
			<script type="text/javascript">
		    // <![CDATA[
			  twttr.anywhere(onAnywhereLoad);
			  function onAnywhereLoad(twitter) {
				  if (twitter.isConnected()) {
	          document.getElementById("twitter-connect-placeholder").innerHTML = '<a href="#" onClick="twttr.anywhere.signOut();">Sign out of Twitter</a>';
				  } else {
				    twitter("#twitter-connect-placeholder").connectButton({
							   authComplete: function(loggedInUser) {
							     window.location='$login';
							   },
							   signOut: function() {
							     window.location='$logout';
							   }
							});
				  }
			  };
	      // ]]>
			</script>
EOD;

	}
	
}
