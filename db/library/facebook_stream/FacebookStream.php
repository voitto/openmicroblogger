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
  
  /**
   * construct FacebookStream object
   */
  function __construct($consumer_key, $consumer_secret, $agent) {
    
    /* Set Facebook key/secret */
    Services_Facebook::$apiKey = $consumer_key;
    Services_Facebook::$secret = $consumer_secret;
    
    /* Instantiate Services_Facebook */
    $this->api = new Services_Facebook();
    
    $this->agent = $agent;
    
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
   * Facebook Stream API request
   */
  function StreamRequest($appid,$sesskey,$userid) {
    
    $this->VerifyStream($userid);
    
    $hash = md5("app_id=".$appid."session_key=".$sesskey."source_id=".$userid.$this->getApiSecret());
    
    $url = 'http://www.facebook.com/activitystreams/feed.php';
    $url .= '?source_id=';
    $url .= $userid;
    $url .= '&app_id=';
    $url .= $appid;
    $url .= '&session_key=';
    $url .= $sesskey;
    $url .= '&sig=';
    $url .= $hash;
    $url .= '&v=0.7&read';
    
    echo $this->http($url);
    
    exit;
    
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
  
  function VerifyStream($userid) {
    
    $perm = 'read_stream';
    
    $params = array(
      'ext_perm' => $perm,
      'uid' => $userid
    );
    
    // optional URL-encoded GET parameters 
    //  next
    //  next_cancel
    
    $response = $this->api->users->callMethod('users.hasAppPermission', $params);
    
    if (!strpos($response->asXML(),"1</users_hasAppPermission")) {
      $url = 'http://www.facebook.com/authorize.php';
      $url .= '?api_key=';
      $url .= $this->getApiKey();
      $url .= '&v=1.0';
      $url .= '&ext_perm=';
      $url .= $perm;
      header('Location:'.$url);
      exit;
    }
    
  }

  function VerifyUpdate($userid) {
    
    $perm = 'status_update';
    
    $params = array(
      'ext_perm' => $perm,
      'uid' => $userid
    );
    
    // optional URL-encoded GET parameters 
    //  next
    //  next_cancel
    
    $response = $this->api->users->callMethod('users.hasAppPermission', $params);
    
    if (!strpos($response->asXML(),"1</users_hasAppPermission")) {
      $url = 'http://www.facebook.com/authorize.php';
      $url .= '?api_key=';
      $url .= $this->getApiKey();
      $url .= '&v=1.0';
      $url .= '&ext_perm=';
      $url .= $perm;
      header('Location:'.$url);
      exit;
    }
    
  }
  
  function setStatus($status,$userid) {
    
    $this->VerifyUpdate($userid);
    
    $params = array(
      'uid' => $userid,
    );
    
    if (is_bool($status) && $status === true) {
      $params['clear'] = 'true';
    } else {
      $params['status'] = $status;
    }
    
    // optional URL-encoded GET parameters 
    //  next
    //  next_cancel
    
    $response = $this->api->users->callMethod('users.setStatus', $params);
    
    return (intval((string)$res) == 1);
    
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
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
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

}