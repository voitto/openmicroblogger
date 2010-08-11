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
 * BuzzToken
 *
 * @package   Structal
 * @author    Brian Hendrickson <brian@megapump.com>
 * @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link      http://structal.org/buzztoken
 */

class BuzzToken extends AuthToken {

  var $api_root = 'https://www.googleapis.com/buzz/v1/activities';
  var $method;
  var $consumer;
  var $domain;
  var $scope;
  var $callback;

  function authorize_url() {
		$str = "?oauth_token=".$this->token;
		$str .= "&oauth_callback=".urlencode($this->callback);
		$str .= "&scope=".urlencode($this->scope);
		$str .= "&domain=".urlencode($this->domain);
    return 'https://www.google.com/buzz/api/auth/OAuthAuthorizeToken'.$str;
  }

  function request_token_url() {
    return 'https://www.google.com/accounts/OAuthGetRequestToken';
  }

  function access_token_url() {
    return 'https://www.google.com/accounts/OAuthGetAccessToken';
  }
	
}

/**
 * Buzz
 *
 * @package   Structal
 * @author    Brian Hendrickson <brian@megapump.com>
 * @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link      http://structal.org/buzz
 */

class Buzz {

  var $friends_timeline;
  var $user_timeline;
  var $replies;
  var $token = NULL;
  var $domain;
  var $scope;
  var $callback;

  function Buzz( $key, $secret, $callback ) {
    $this->method = new OAuthSignatureMethod_HMAC_SHA1();
    $this->consumer = new OAuthConsumer( $key, $secret );
    $this->callback = $callback;
    $this->scope = 'https://www.googleapis.com/auth/buzz';
		$parsed = parse_url($callback);
    $this->domain = $parsed['host'];
  }

  function request_token() {
	  $tk = new BuzzToken();
	  $tk->scope = $this->scope;
	  $tk->domain = $this->domain;
	  $tk->callback = $this->callback;
	  $addparams = array(
		  'oauth_callback'=>$this->callback,
		  'domain'=>$this->domain,
		  'scope'=>$this->scope
		);
		$parsed = parse_url( $tk->request_token_url() );
		parse_str($parsed['query'], $params);
		foreach($addparams as $k=>$v)
		  $params[$k] = $v;
		$req = OAuthRequest::from_consumer_and_token(
			$this->consumer, 
			NULL, 
			"GET", 
			$tk->request_token_url(), 
			$params
		);
	  $req->sign_request(
		  $this->method,
		  $this->consumer,
		  NULL
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

  function authorize_from_request( $token, $secret, $verifier ){
    $this->token = new OAuthConsumer( $token, $secret );
	  $tk = new BuzzToken();
	  $addparams = array(
		  'oauth_verifier'=>$verifier,
		  'domain'=>$this->domain
		);
		$parsed = parse_url( $tk->access_token_url() );
		parse_str($parsed['query'], $params);
		foreach($addparams as $k=>$v)
		  $params[$k] = $v;
		$req = OAuthRequest::from_consumer_and_token(
			$this->consumer, 
			$this->token, 
			"GET", 
			$tk->access_token_url(), 
			$params
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
	  $tk = new BuzzToken();
	  $apiroot = $tk->api_root;
	  $url = $apiroot . '/@me/@self';
		$params = array(
		  'oauth_consumer_key' => $this->consumer->key,
		  'oauth_timestamp' => time(),
		  'oauth_version' => OAuthRequest::$version,
		  'oauth_nonce' => md5(microtime().mt_rand()),
		  'oauth_token'=>$this->token->key
		);
	  $params['alt'] = 'json';
    $oauthRequest = OAuthRequest::from_request(
	    'POST',
	    $url,
	    $params
	  );
    $oauthRequest->sign_request(
	    $this->method,
	    $this->consumer,
	    $this->token
	  );
    $url = $oauthRequest->to_url();
    $w = new bz_data();
    $w->data = new bz_data();
    $w->data->object = new bz_odata($status);
    if (!function_exists('json_encode'))
      lib_include('json');
    $buzzjson = json_encode($w);
		$headers = array();
    $headers[] = 'Content-Type: application/json';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $buzzjson);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    return @curl_exec($ch);
  }

  function like( $id ) {
	  $tk = new BuzzToken();
	  $apiroot = $tk->api_root;
	  $url = $apiroot . '/@me/@liked/'.$id;
		$params = array(
		  'oauth_consumer_key' => $this->consumer->key,
		  'oauth_timestamp' => time(),
		  'oauth_version' => OAuthRequest::$version,
		  'oauth_nonce' => md5(microtime().mt_rand()),
		  'oauth_token'=>$this->token->key
		);
	  $params['alt'] = 'json';
    $oauthRequest = OAuthRequest::from_request(
	    'PUT',
	    $url,
	    $params
	  );
    $oauthRequest->sign_request(
	    $this->method,
	    $this->consumer,
	    $this->token
	  );
    $url = $oauthRequest->to_url();
    $data = new bz_data();
    $data->noop = 'noop';
    $data2 = new bz_data();
    $data2->data = $data;
    if (!function_exists('json_encode'))
      lib_include('json');
    $buzzjson = json_encode($data2);
		$headers = array();
    $headers[] = 'Content-Type: application/json';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $buzzjson);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    return @curl_exec($ch);
  }

  function search( $string ) {
  }

  function friends_timeline() {
	  $tk = new BuzzToken();
	  $apiroot = $tk->api_root;
	  $url = $apiroot . '/@me/@consumption';
		$params = array(
		  'oauth_consumer_key' => $this->consumer->key,
		  'oauth_timestamp' => time(),
		  'oauth_version' => OAuthRequest::$version,
		  'oauth_nonce' => md5(microtime().mt_rand()),
		  'oauth_token'=>$this->token->key
		);
    $oauthRequest = OAuthRequest::from_request(
	    'GET',
	    $url,
	    $params
	  );
    $oauthRequest->sign_request(
	    $this->method,
	    $this->consumer,
	    $this->token
	  );
    $url = $oauthRequest->to_url();
		$headers = array();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    return @curl_exec($ch);
  }

	function http( $url, $post_data = null, $headers = null ){
		$ch = curl_init();
    if (defined("CURL_CA_BUNDLE_PATH"))
      curl_setopt($ch, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);

    if ($headers && is_array($headers)) {
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

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
 * BuzzHelper
 *
 * @package   Structal
 * @author    Brian Hendrickson <brian@megapump.com>
 * @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link      http://structal.org/buzzhelper
 */

class BuzzHelper extends Helper {
	
	function header( $key ) {
		
		echo <<<EOD
EOD;

	}

	function login( $login, $logout ) {

		echo <<<EOD
EOD;

	}
	
}

/**
 * bz_data
 *
 * @package   Structal
 * @author    Brian Hendrickson <brian@megapump.com>
 * @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link      http://structal.org/bz_data
 */

class bz_data {
}

/**
 * bz_odata
 *
 * @package   Structal
 * @author    Brian Hendrickson <brian@megapump.com>
 * @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link      http://structal.org/bz_odata
 */

class bz_odata{
	var $type = 'note';
	var $content = 'test';
	function bz_odata($a){
		$this->content = $a;
	}
}

