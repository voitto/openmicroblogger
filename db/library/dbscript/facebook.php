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
 * FacebookHelper
 *
 * @package   Structal
 * @author    Brian Hendrickson <brian@megapump.com>
 * @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link      http://structal.org/facebookhelper
 */

class FacebookHelper extends Helper {
	
	function header( $key, $xd, $next ) {
		
		global $request;
		$url = $request->url_for('facebook_login');

		echo <<<EOD
			<script type="text/javascript">
			  function facebook_onlogin() {
			    window.location='$url';
			  }
			  function facebook_dologin() {
					FB_RequireFeatures(["XFBML"], function(){ 
						FB.Facebook.init('$key', '$xd', null);
						FB.ensureInit(function () { 
							FB.Connect.requireSession(facebook_onlogin, true);
						});
					});
				}
			</script>
EOD;

	}

  function xmlns() {
		echo <<<EOD
 xmlns:fb="http://www.facebook.com/2008/fbml
EOD;
	
  }

	function login() {
    
    //$tag = $this->content_tag( 'a', 'Login with Facebook', array( 'href' => 'JavaScript:facebook_dologin();' )); 
		echo <<<EOD
			<script src="http://static.ak.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php" type="text/javascript"></script>
      <a href="JavaScript:facebook_dologin();">Login with Facebook</a>
EOD;

	}

	function redirect() {

		echo <<<EOD
			<script src="http://static.ak.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php" type="text/javascript"></script>
      <script type="text/javascript">
		  // <![CDATA[
        facebook_dologin();
      // ]]>
      </script>
EOD;

	}

  function doctype() {
	
	echo <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml">
EOD;
	}
	
}

/**
 * FacebookToken
 *
 * @package   Structal
 * @author    Brian Hendrickson <brian@megapump.com>
 * @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link      http://structal.org/facebooktoken
 */

class FacebookToken extends AuthToken {

  var $api_root;
  var $next;

  function FacebookToken( $next, $root ) {
		$this->next = $next;
	  $this->api_root = $root;
  }

  function authorize_url() {
    $url = $this->api_root . '/login.php';
    $params = array('api_key' => Services_Facebook::$apiKey,
                    'v'       => '1.0');
    $params['next'] = $this->next;
    return $url . '?' . http_build_query($params);
  }
	
}

/**
 * Facebook
 *
 * @package   Structal
 * @author    Brian Hendrickson <brian@megapump.com>
 * @license   http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link      http://structal.org/facebook
 */

class Facebook {

  var $friends_timeline;
  var $user_timeline;
  var $replies;
  var $api;
  var $agent;
  var $appid;
  var $userid;
  var $api_root = 'http://www.facebook.com';

  function Facebook( $key, $secret, $appid, $agent, $session=false, $next ){
    Services_Facebook::$apiKey = $key;
    Services_Facebook::$secret = $secret;
    $this->api = new Services_Facebook();
    $this->agent = $agent;
    $this->appid = $appid;
    if (!$session)
      $_SESSION['fb_session'] = $this->api->sessionKey;
    else
      $this->api->sessionKey = $session;
    $this->next = $next;
  }

  function request_token() {
    $token = $this->api->auth->createToken();
    $this->token = new FacebookToken($this->next,$this->api_root);
    $this->token->token = $token;
    return $this->token;
  }

	function authorize_from_access() {
		global $request;
		$sess_data = (array) $this->api->auth->callMethod('auth.getSession',array('auth_token'=>$request->auth_token));
	  $this->userid = $sess_data['uid'];
	  return array($sess_data['uid'],$sess_data['session_key']);
	}
	
	function permission_to( $perm, $uid=false, $force=false, $return=false ) {
    $params = array(
      'ext_perm' => $perm,
      'uid' => $this->userid
    );
    if ($uid)
      $params['uid'] = $uid;
    if (!$force){
      $response = $this->api->users->callMethod( 'users.hasAppPermission', $params );
  		$xml = simplexml_load_string($response->asXML());
	  	$xml = (array) $xml;
	  }
    if ($force || !$xml[0]) {

      $url = $this->api_root . '/connect/prompt_permissions.php';
	    $params = array('api_key' => Services_Facebook::$apiKey,
	                    'v'       => '1.0');
	    if ($uid){
	      $params['uid'] = $uid;
	    } elseif ($this->userid) {
	      $params['uid'] = $this->userid;
	    } else {
	      unset($params['uid']);
	      $params['session_key'] = $this->api->sessionKey;
      }
	   
			$params['ext_perm'] = $perm;
	    $params['next'] = $this->next;
	    $url = $url . '?' . http_build_query($params);

	    if ($return)
	      return $url;
      header( 'Location:' . $url );
      exit;
    }
  }

  function friends_timeline( $uid ) {

		$hash = md5("app_id=".$this->appid."session_key=".$this->api->sessionKey."source_id=".$uid.Services_Facebook::$secret);

		$url = 'http://www.facebook.com/activitystreams/feed.php';
		$url .= '?source_id=';
		$url .= $uid;
		$url .= '&app_id=';
		$url .= $this->appid;
		$url .= '&session_key=';
		$url .= $this->api->sessionKey;
		$url .= '&sig=';
		$url .= $hash;
		$url .= '&v=0.7&read';

    return $this->http($url);

  }

  function like( $id, $uid=false ) {
	  //$this->permission_to( 'publish_stream', $uid );
	  $params = array(
	    'uid' => $this->userid,
	  );
	  if ($uid)
	    $params['uid'] = $uid;
    $params['post_id'] = $id;
	  $res = $this->api->users->callMethod( 'stream.addLike', $params );
	  return (intval((string)$res) == 1);
  }

  function publish( $status, $uid=false ) {
	  //$this->permission_to( 'publish_stream', $uid );
	  $params = array(
	    'uid' => $this->userid,
	  );
	  if ($uid)
	    $params['uid'] = $uid;
    $params['message'] = $status;
	  $res = $this->api->users->callMethod( 'stream.publish', $params );
	  return (string)$res;
  }

  function update( $status, $uid=false ) {
	  $this->permission_to( 'status_update', $uid );
	  $params = array(
	    'uid' => $this->userid,
	  );
	  if ($uid)
	    $params['uid'] = $uid;
	  if (is_bool($status) && $status === true) {
	    $params['clear'] = 'true';
	  } else {
	    $params['status'] = $status;
	  }
	  $res = $this->api->users->callMethod( 'users.setStatus', $params );
	  return (intval((string)$res) == 1);
  }

  function search( $string ) {
  }

  function getpages() {
	  $fieldlist = array(
	    'page_id',
	    'name'
	  );
	  $fields = implode(',',$fieldlist);
	  $params = array(
	    'uid' => $this->userid,
      'api_key' => Services_Facebook::$apiKey,
      'call_id' => microtime(true),
      'sig' =>  md5("app_id=".$this->appid."session_key=". $this->api->sessionKey."source_id=".$this->userid.Services_Facebook::$secret),
      'v' => '1.0',
      'fields' => $fields,
      'session_key' => $this->api->sessionKey
	  );
	  $pages = array();
	  $response = $this->api->users->callMethod( 'pages.getinfo', $params );
		$xml = simplexml_load_string($response->asXML());
		foreach($xml as $k=>$v){
		  foreach($v as $b=>$r){
			  if ((string) $b == 'name')
			    $name = (string) $r;
			  if ((string) $b == 'page_id')
			    $pid = (string) $r;
		  }
	    $pages[$pid] = array('name'=>$name);
		}
		return $pages;
  }

  function ispageadmin( $p ) {
	  $params = array(
	    'page_id' => $p,
	    'uid' => $this->userid,
      'api_key' => Services_Facebook::$apiKey,
      'call_id' => microtime(true),
      'sig' =>  md5("app_id=".$this->appid."session_key=". $this->api->sessionKey."source_id=".$this->userid.Services_Facebook::$secret),
      'v' => '1.0',
      'session_key' => $this->api->sessionKey
	  );
	  //return true;
	  $response = $this->api->users->callMethod( 'pages.isAdmin', $params );
		$xml = simplexml_load_string($response->asXML());
		$xml = (array) $xml;
    if (!$xml[0])
	    return false;
		return true;
  }

  function http($url, $post_data = null) {/*{{{*/
    $ch = curl_init();
    if (defined("CURL_CA_BUNDLE_PATH")) curl_setopt($ch, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //////////////////////////////////////////////////
    ///// Set to 1 to verify SSL Cert //////
    //////////////////////////////////////////////////
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    if (isset($post_data)) {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }
    curl_setopt($ch, CURLOPT_USERAGENT, 'Structal');
    $response = curl_exec($ch);
    $this->http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $this->last_api_call = $url;
    curl_close ($ch);
    return $response;
  }

}

