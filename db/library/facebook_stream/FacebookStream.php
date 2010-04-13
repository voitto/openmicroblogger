<?php
/*
 *
 * Facebook Streams http://facebookstreams.com
 * 
 * Brian Hendrickson (brian@megapump.com) http://brianhendrickson.com
 *
 * Basic lib to work with Facebook Streams API
 *
 */

/**
 * Facebook Stream class
 */
class FacebookStream {
  
  private $http_status;
  private $last_api_call;
  private $api;
  private $agent;
  private $appid;
  
  /**
   * construct FacebookStream object
   */
  function __construct($consumer_key, $consumer_secret, $agent, $appid) {
    
    /* Set Facebook key/secret */
    Services_Facebook::$apiKey = $consumer_key;
    Services_Facebook::$secret = $consumer_secret;
    
    /* Instantiate Services_Facebook */
    $this->api = new Services_Facebook();
    
    $this->agent = $agent;

    $this->appid = $appid;
    
  }

  /**
   * Use an "infinite" key from Facebook
   */
  function setSess($s){
	  $this->api->sessionKey = $s; 
  }

  /**
   * Make a Request token
   */
  function getAccessToken() {
    return $this->api->auth->createToken();
  }
  
  /**
   * Return the API key
   */
  function getApiKey() { 
    return Services_Facebook::$apiKey;    
  }

	/**
	 * Return the session Key
	 */
	function getSessionKey() { 
	  return $this->api->sessionKey;
	}

  /**
   * Return the API secret
   */
  function getApiSecret() { 
    return Services_Facebook::$secret;
  }
  
  /**
   * get a session
   */
  function getSession($token) { 
    return $this->api->auth->getSession($token);
  }

  /**
   * get a permanent session key
   */
	function permanent_facebook_key($key,$secret){
	  add_lib_path('facebook-platform/php');
	  require_once "facebook.php";
	  $facebook = new Facebook($key, $secret);
	  $infinite_key_array = $facebook->api_client->auth_getSession($_GET['key']);
	  if ($infinite_key_array['session_key'])
	    echo "your permanent session key is ". $infinite_key_array['session_key'];
	  else
	    echo "sorry there was an error getting your permanent session key";
	  exit;
	}
  
  /**
   * Facebook Stream API request
   */
  function StreamRequest($userid) {
    
    // requires read_stream
		$hash = md5("app_id=".$this->appid."session_key=".$this->getSessionKey()."source_id=".$userid.$this->getApiSecret());

		$url = 'http://www.facebook.com/activitystreams/feed.php';
		$url .= '?source_id=';
		$url .= $userid;
		$url .= '&app_id=';
		$url .= $this->appid;
		$url .= '&session_key=';
		$url .= $this->getSessionKey();
		$url .= '&sig=';
		$url .= $hash;
		$url .= '&v=0.7&read';

    return $this->http($url);
    
  }
  
  function GetInfo($appid,$sesskey,$userid,$fields) {
    
    // http://wiki.developers.facebook.com/index.php/Users.getInfo
    
    $params = array(
      'api_key' => $this->getApiKey(),
      'call_id' => microtime(true),
      'sig' =>  md5("app_id=".$appid."session_key=".$sesskey."source_id=".$userid.$this->getApiSecret()),
      'v' => '1.0',
      'uids' => $userid,
      'fields' => $fields,
      'session_key' => $sesskey
    );
    
    return $this->api->users->callMethod( 'users.getInfo', $params );
    
  }
  
  function verifyPerms($userid,$perms,$path='') {

	  $showperms = array();

	  foreach($perms as $perm){
	    $params = array(
	      'ext_perm' => $perm,
	      'uid' => $userid
	    );
	    $response = $this->api->users->callMethod('users.hasAppPermission', $params);
	    $xml = simplexml_load_string($response->asXML());
	    if (is_object($xml))
	      $xml = (array) $xml;
	    if (!$xml[0])
		    $showperms[] = $perm;
    }

    if (count($showperms) > 0)
      $this->showPopup(implode(',',$showperms),$path);

  }
  
  function setStatus($status,$userid) {
    
    //$this->verifyPerms($userid,array('status_update','photo_upload'));
    
    $params = array(
      'uid' => $userid,
    );
    
    if (is_bool($status) && $status === true) {
      $params['clear'] = 'true';
    } else {
      $params['status'] = $status;
    }
    
    $response = $this->api->users->callMethod('users.setStatus', $params);
    
    return (intval((string)$res) == 1);
    
  }

  function PhotoUpload( $file, $aid=0, $caption='',$userid ) {
    
    //$this->verifyPerms($userid,array('status_update','photo_upload'));
	  
	  $params = array(
	    'method' => 'photos.upload',
	    'v' => '1.0',
	    'api_key' => $this->getApiKey(),
	    'call_id' => microtime(true),
	    'format' => 'XML',
	    'uid' => $userid
	  );
	
		if ($aid > 0)
		    $params['aid'] = $aid;
		
		if (strlen($caption))
		    $params['caption'] = $caption;
		
		$params = $this->signRequest($params);
		$params[basename($file)] = '@' . realpath($file);
		$url = $this->api->photos->getAPI() . '?method=photos.upload';
		
    return $this->http($url,$params);

  }
  
  /**
   * Make an HTTP request
   *
   * @return API results
   */
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
    curl_setopt($ch, CURLOPT_USERAGENT, $this->agent . phpversion());
    $response = curl_exec($ch);
    $this->http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $this->last_api_call = $url;
    curl_close ($ch);
    return $response;
  }

  function signRequest($params) {
		if (isset($params['sig']))
		  unset($params['sig']);
		ksort($params);
		$sig = '';
		foreach ($params as $k => $v)
		  $sig .= $k .'=' . $v;
		$sig  .= $this->getApiSecret();
		$params['sig'] = md5($sig);
		return $params;
  }

  function showJs(){

		echo <<<EOD
      <script src="http://static.ak.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php" type="text/javascript"></script>
EOD;

  }

  function showPopup($perms,$path){
	
    $key = $this->getApiKey();

    $path = $path.'xd_receiver.htm';

		echo <<<EOD
			<script type="text/javascript"> 
				FB_RequireFeatures(["XFBML"], function(){ 
				FB.Facebook.init('$key', '$path', null);
				FB.ensureInit(function () { 
					FB.Connect.showPermissionDialog('$perms', function(accepted) { window.close(); } )
				});
			});
			</script>
EOD;

  }

}

function add_lib_path($path,$prepend = false) {
   if (!file_exists($path) OR (file_exists($path) && filetype($path) !== 'dir'))
   {
       trigger_error("Include path '{$path}' not exists", E_USER_WARNING);
       continue;
   }
   
   $paths = explode(PATH_SEPARATOR, get_include_path());
   
   if (array_search($path, $paths) === false && $prepend)
       array_unshift($paths, $path);
   if (array_search($path, $paths) === false)
       array_push($paths, $path);
   
   set_include_path(implode(PATH_SEPARATOR, $paths));
}
