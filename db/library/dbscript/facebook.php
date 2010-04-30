<?php

class FacebookHelper extends Helper {
	
	function header( $key, $xd, $next ) {
		
		echo <<<EOD
			<script type="text/javascript">
			  function facebook_onlogin() {
			    window.location='$next';
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

class Facebook {

  var $friends_timeline;
  var $user_timeline;
  var $replies;
  var $api;
  var $agent;
  var $appid;
  var $userid;
  var $api_root = 'http://www.facebook.com';

  function Facebook( $key, $secret, $appid, $agent, $session, $next ){
    Services_Facebook::$apiKey = $key;
    Services_Facebook::$secret = $secret;
    $this->api = new Services_Facebook();
    $this->agent = $agent;
    $this->appid = $appid;
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
	  $this->userid = $this->api->users->getLoggedInUser();
	}
	
	function permission_to( $perm ) {
    $params = array(
      'ext_perm' => $perm,
      'uid' => $this->userid
    );
    $response = $this->api->users->callMethod( 'users.hasAppPermission', $params );
		$xml = simplexml_load_string($response->asXML());
		$xml = (array) $xml;
    if (!$xml[0]) {
      $url = $this->api_root . '/authorize.php';
	    $params = array('api_key' => Services_Facebook::$apiKey,
	                    'v'       => '1.0');
			$params['ext_perm'] = $perm;
	    $params['next'] = $this->next;
	    $url = $url . '?' . http_build_query($params);
      header( 'Location:' . $url );
      exit;
    }
  }

  function update( $status ) {
	  $this->permission_to( 'status_update' );
	  $params = array(
	    'uid' => $this->userid,
	  );
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

}

