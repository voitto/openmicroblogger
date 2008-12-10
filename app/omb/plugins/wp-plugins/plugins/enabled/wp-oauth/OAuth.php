<?php




if(!function_exists('apache_request_headers')) {
        function apache_request_headers() {
            foreach($_SERVER as $key=>$value) {
                if (substr($key,0,5)=="HTTP_") {
                    $key=str_replace(" ","-",ucwords(strtolower(str_replace("_"," ",substr($key,5)))));
                    $out[$key]=$value;
                }
            }
            return $out;
        }
}
/* END HEADERS ON SHARED HOSTS */

/* Generic exception class
 */
class OAuthException {
  // pass
}

class OAuthConsumer {
  var $key;
  var $secret;

  function OAuthConsumer($key, $secret, $callback_url=NULL) {
    $this->key = $key;
    $this->secret = $secret;
    $this->callback_url = $callback_url;
  }
}

class OAuthToken {
  // access tokens and request tokens
  var $key;
  var $secret;

  /**
   * key = the token
   * secret = the token secret
   */
  function OAuthToken($key, $secret) {/*{{{*/
    $this->key = $key;
    $this->secret = $secret;
  }/*}}}*/

  /**
   * generates the basic string serialization of a token that a server
   * would respond to request_token and access_token calls with
   */
  function to_string() {/*{{{*/
    return "oauth_token=" . urlencode($this->key) . 
        "&oauth_token_secret=" . urlencode($this->secret);
  }/*}}}*/

  function __toString() {/*{{{*/
    return $this->to_string();
  }/*}}}*/
}/*}}}*/

class OAuthSignatureMethod {/*{{{*/

}/*}}}*/

class OAuthSignatureMethod_HMAC_SHA1 extends OAuthSignatureMethod {/*{{{*/
  function get_name() {/*{{{*/
    return "HMAC-SHA1";
  }/*}}}*/

  function build_signature($request, $consumer, $token) {/*{{{*/
    $sig = array(
      urlencode($request->get_normalized_http_method()),
      urlencode($request->get_normalized_http_url()),
      urlencode($request->get_signable_parameters()),
    );

    $key = $consumer->secret . "&";

    if ($token) {
      $key .= $token->secret;
    }

    $raw = implode("&", $sig);

    if (!(function_exists('hash_hmac'))) {
      
      function &hash_hmac($algo, $data, $key, $raw_output ) {
        $data = trim($data);
        $key = trim($key);
            $blocksize=64;
            $hashfunc='sha1';
            if (strlen($key)>$blocksize)
                $key=pack('H*', $hashfunc($key));
            $key=str_pad($key,$blocksize,chr(0x00));
            $ipad=str_repeat(chr(0x36),$blocksize);
            $opad=str_repeat(chr(0x5c),$blocksize);
            $hmac = pack(
                        'H*',$hashfunc(
                            ($key^$opad).pack(
                                'H*',$hashfunc(
                                    ($key^$ipad).$data
                                )
                            )
                        )
                    );
            return $hmac;
        
      }
      
    }
    
    $raw = str_replace("%2B","%2520",$raw);
    $raw = str_replace("%257E","~",$raw);
    $raw = str_replace("%7E","~",$raw);

    $hashed = base64_encode(hash_hmac("sha1", trim($raw), trim($key), TRUE));
    return $hashed;
  }/*}}}*/
}/*}}}*/

class OAuthSignatureMethod_PLAINTEXT extends OAuthSignatureMethod {/*{{{*/
  function get_name() {/*{{{*/
    return "PLAINTEXT";
  }/*}}}*/
  function build_signature($request, $consumer, $token) {/*{{{*/
    $sig = array(
      urlencode($consumer->secret)
    );

    if ($token) {
      array_push($sig, urlencode($token->secret));
    } else {
      array_push($sig, '');
    }

    $raw = implode("&", $sig);
    return $raw;
  }/*}}}*/
}/*}}}*/

class OAuthRequest {
  var $parameters;
  var $http_method;
  var $http_url;
  var $base_string;
  var $version = '1.0';

  function OAuthRequest($http_method, $http_url, $parameters=NULL) {/*{{{*/
    if ($parameters == NULL) $parameters = array();
    $defaults = array("oauth_version" => $this->version,
                        "oauth_nonce" => $this->generate_nonce(),
                        "oauth_timestamp" => $this->generate_timestamp()
                        );
                      
    
    $parameters = array_merge($defaults, $parameters);
    $this->parameters = $parameters;
    $this->http_method = $http_method;
    $this->http_url = $http_url;
  }/*}}}*/

  /**
   * attempt to build up a request from what was passed to the server
   */
  function from_request($http_method=NULL, $http_url=NULL, $parameters=NULL) {/*{{{*/
    @$http_url or $http_url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    @$http_method or $http_method = $_SERVER['REQUEST_METHOD'];
    
    // we need this to get the actual Authorization: header
    // because apache tends to tell us it doesn't exist
    $request_headers = apache_request_headers();

    // let the library user override things however they'd like, if they know
    // which parameters to use then go for it, for example XMLRPC might want to
    // do this
    if ($parameters) {
      $req = new OAuthRequest($http_method, $http_url, $parameters);
    }
    // next check for the auth header, we need to do some extra stuff
    // if that is the case, namely suck in the parameters from GET or POST
    // so that we can include them in the signature
    else if (@substr($request_headers['Authorization'], 0, 5) == "OAuth") {
      $header_parameters = OAuthRequest::split_header($request_headers['Authorization']);
      if ($http_method == "GET") {
        $req_parameters = $_GET;
      } 
      else if ($http_method = "POST") {
        $req_parameters = $_POST;
      } 
      $parameters = array_merge($header_parameters, $req_parameters);
      $req = new OAuthRequest($http_method, $http_url, $parameters);
    }
    else if ($_GET['oauth_version'] || $_GET['oauth_token']) {
      $req = new OAuthRequest($http_method, $http_url, $_GET);
    }
    else { //must return an OAuthRequest, even if empty, so just use this
      $req = new OAuthRequest($http_method, $http_url, $_POST);
    }
    return $req;
  }/*}}}*/

  /**
   * pretty much a helper function to set up the request
   */
  function from_consumer_and_token($consumer, $token, $http_method, $http_url, $parameters=NULL) {
    if ($parameters == NULL)
      $parameters = array();
    $defaults = array("oauth_consumer_key" => $consumer->key);
    $parameters = array_merge($defaults, $parameters);

    if ($token) {
      $parameters['oauth_token'] = $token->key;
    }
    return new OAuthRequest($http_method, $http_url, $parameters);
  }/*}}}*/

  function set_parameter($name, $value) {
    $this->parameters[$name] = $value;
  }

  function get_parameter($name) {
    return $this->parameters[$name];
  }

  function get_parameters() {
    return $this->parameters;
  }

  /**
   * return a string that consists of all the parameters that need to be signed
   */
  function get_signable_parameters() {/*{{{*/
    $sorted = $this->parameters;
    ksort($sorted);

    $total = array();
    foreach ($sorted as $k => $v) {
      if ($k == "oauth_signature") continue;
      //$total[] = $k . "=" . $v;
      $total[] = urlencode($k) . "=" . urlencode($v);
    }
    return implode("&", $total);
  }/*}}}*/

  /**
   * just uppercases the http method
   */
  function get_normalized_http_method() {/*{{{*/
    return strtoupper($this->http_method);
  }/*}}}*/

  /**
   * parses the url and rebuilds it to be
   * scheme://host/path
   */
  function get_normalized_http_url() {/*{{{*/
    $parts = parse_url($this->http_url);
    $url_string = "{$parts['scheme']}://{$parts['host']}{$parts['path']}";
    return $url_string;
  }/*}}}*/

  /**
   * builds a url usable for a GET request
   */
  function to_url() {/*{{{*/
    $out = $this->get_normalized_http_url() . "?";
    $out .= $this->to_postdata();
    return $out;
  }/*}}}*/

  /**
   * builds the data one would send in a POST request
   */
  function to_postdata() {/*{{{*/
    $total = array();
    foreach ($this->parameters as $k => $v) {
      $total[] = urlencode($k) . "=" . urlencode($v);
    }
    $out = implode("&", $total);
    return $out;
  }/*}}}*/

  /**
   * builds the Authorization: header
   */
  function to_header() {/*{{{*/
    $out ='"Authorization: OAuth realm="",';
    $total = array();
    foreach ($this->parameters as $k => $v) {
      if (substr($k, 0, 5) != "oauth") continue;
      $total[] = urlencode($k) . '="' . urlencode($v) . '"';
    }
    $out = implode(",", $total);
    return $out;
  }/*}}}*/

  function __toString() {/*{{{*/
    return $this->to_url();
  }/*}}}*/


  function sign_request($signature_method, $consumer, $token) {/*{{{*/
    $this->set_parameter("oauth_signature_method", $signature_method->get_name());
    $signature = $this->build_signature($signature_method, $consumer, $token);
    $this->set_parameter("oauth_signature", $signature);
  }/*}}}*/

  function build_signature($signature_method, $consumer, $token) {/*{{{*/
    $signature = $signature_method->build_signature($this, $consumer, $token);
    return $signature;
  }/*}}}*/

  /**
   * util function: current timestamp
   */
  function generate_timestamp() {/*{{{*/
    return time();
  }/*}}}*/

  /**
   * util function: current nonce
   */
  function generate_nonce() {/*{{{*/
    $mt = microtime();
    $rand = mt_rand();

    return md5($mt . $rand); // md5s look nicer than numbers
  }/*}}}*/

  /**
   * util function for turning the Authorization: header into
   * parameters, has to do some unescaping
   */
  function split_header($header) {/*{{{*/
    // this should be a regex
    // error cases: commas in parameter values
    $parts = explode(",", $header);
    $out = array();
    foreach ($parts as $param) {
      $param = ltrim($param);
      // skip the "realm" param, nobody ever uses it anyway
      if (substr($param, 0, 5) != "oauth") continue;

      $param_parts = explode("=", $param);

      // rawurldecode() used because urldecode() will turn a "+" in the
      // value into a space
      $out[$param_parts[0]] = rawurldecode(substr($param_parts[1], 1, -1));
    }
    return $out;
  }/*}}}*/

}/*}}}*/

class OAuthServer {/*{{{*/
  var $timestamp_threshold = 300; // in seconds, five minutes
  var $version = 1.0;             // hi blaine
  var $signature_methods = array();

  var $data_store;

  function OAuthServer($data_store) {/*{{{*/
    $this->data_store = $data_store;
  }/*}}}*/

  function add_signature_method($signature_method) {/*{{{*/
    $this->signature_methods[$signature_method->get_name()] = 
        $signature_method;
  }/*}}}*/
  
  // high level functions

  /**
   * process a request_token request
   * returns the request token on success
   */
  function fetch_request_token(&$request) {/*{{{*/
    $this->get_version($request);

    $consumer = $this->get_consumer($request);

    // no token required for the initial token request
    $token = NULL;

    $this->check_signature($request, $consumer, $token);

    $new_token = $this->data_store->new_request_token($consumer);

    return $new_token;
  }/*}}}*/

  /**
   * process an access_token request
   * returns the access token on success
   */
  function fetch_access_token(&$request) {/*{{{*/
    $this->get_version($request);

    $consumer = $this->get_consumer($request);

    // requires authorized request token
    $token = $this->get_token($request, $consumer, "request");

    $this->check_signature($request, $consumer, $token);

    $new_token = $this->data_store->new_access_token($token, $consumer);

    return $new_token;
  }/*}}}*/

  /**
   * verify an api call, checks all the parameters
   */
  function verify_request(&$request) {/*{{{*/
    $this->get_version($request);
    $consumer = $this->get_consumer($request);
    $token = $this->get_token($request, $consumer, "access");
    $this->check_signature($request, $consumer, $token);
    return array($consumer, $token);
  }/*}}}*/

  // Internals from here
  /**
   * version 1
   */
  function get_version(&$request) {/*{{{*/
    $version = $request->get_parameter("oauth_version");
    if (!$version) {
      $version = 1.0;
    }
    if ($version && $version != $this->version) {
      trigger_error("OAuth version '$version' not supported",E_USER_ERROR);
    }
    return $version;
  }/*}}}*/

  /**
   * figure out the signature with some defaults
   */
  function get_signature_method(&$request) {/*{{{*/
    $signature_method =  
        @$request->get_parameter("oauth_signature_method");
    if (!$signature_method) {
      $signature_method = "PLAINTEXT";
    }
    if (!in_array($signature_method, 
                  array_keys($this->signature_methods))) {
      trigger_error(
        "Signature method '$signature_method' not supported try one of the following: " . implode(", ", array_keys($this->signature_methods),E_USER_ERROR)
      );      
    }
    return $this->signature_methods[$signature_method];
  }/*}}}*/

  /**
   * try to find the consumer for the provided request's consumer key
   */
  function get_consumer(&$request) {/*{{{*/
    $consumer_key = @$request->get_parameter("oauth_consumer_key");
    if (!$consumer_key) {
      trigger_error("Invalid consumer key",E_USER_ERROR);
    }

    $consumer = $this->data_store->lookup_consumer($consumer_key);
    if (!$consumer) {
      trigger_error("Invalid consumer",E_USER_ERROR);
    }

    return $consumer;
  }/*}}}*/

  /**
   * try to find the token for the provided request's token key
   */
  function get_token(&$request, $consumer, $token_type="access") {/*{{{*/
    $token_field = @$request->get_parameter('oauth_token');
    $token = $this->data_store->lookup_token(
      $consumer, $token_type, $token_field
    );
    if (!$token) {
      trigger_error("Invalid $token_type token: $token_field",E_USER_ERROR);
    }
    return $token;
  }/*}}}*/

  /**
   * all-in-one function to check the signature on a request
   * should guess the signature method appropriately
   */
  function check_signature(&$request, $consumer, $token) {/*{{{*/
    // this should probably be in a different method
    $timestamp = @$request->get_parameter('oauth_timestamp');
    $nonce = @$request->get_parameter('oauth_nonce');

    $this->check_timestamp($timestamp);
    $this->check_nonce($consumer, $token, $nonce, $timestamp);

    $signature_method = $this->get_signature_method($request);

    $signature = $request->get_parameter('oauth_signature');    
    $built = $signature_method->build_signature(
      $request, $consumer, $token
    );
    
    
    if ($signature != $built) {
      trigger_error("Invalid signature",E_USER_ERROR);
    }
  }/*}}}*/

  /**
   * check that the timestamp is new enough
   */
  function check_timestamp($timestamp) {/*{{{*/
    // verify that timestamp is recentish
    $now = time();
    if ($now - $timestamp > $this->timestamp_threshold) {
      trigger_error("Expired timestamp, yours $timestamp, ours $now",E_USER_ERROR);
    }
  }/*}}}*/

  /**
   * check that the nonce is not repeated
   */
  function check_nonce($consumer, $token, $nonce, $timestamp) {/*{{{*/
    // verify that the nonce is uniqueish
    $found = $this->data_store->lookup_nonce($consumer, $token, $nonce, $timestamp);
    if ($found) {
      trigger_error("Nonce already used: $nonce",E_USER_ERROR);
    }
  }/*}}}*/



}/*}}}*/

class OAuthDataStore {/*{{{*/
  function lookup_consumer($consumer_key) {/*{{{*/
    // implement me
  }/*}}}*/

  function lookup_token($consumer, $token_type, $token) {/*{{{*/
    // implement me
  }/*}}}*/

  function lookup_nonce($consumer, $token, $nonce, $timestamp) {/*{{{*/
    // implement me
  }/*}}}*/

  function fetch_request_token($consumer) {/*{{{*/
    // return a new token attached to this consumer
  }/*}}}*/

  function fetch_access_token($token, $consumer) {/*{{{*/
    // return a new access token attached to this consumer
    // for the user associated with this token if the request token
    // is authorized
    // should also invalidate the request token
  }/*}}}*/

}/*}}}*/


/*  A very naive dbm-based oauth storage
 */
class SimpleOAuthDataStore extends OAuthDataStore {/*{{{*/
  var $dbh;

  function SimpleOAuthDataStore($path = "oauth.gdbm") {/*{{{*/
    $this->dbh = dba_popen($path, 'c', 'gdbm');
  }/*}}}*/

  function __destruct() {/*{{{*/
    dba_close($this->dbh);
  }/*}}}*/

  function lookup_consumer($consumer_key) {/*{{{*/
    $rv = dba_fetch("consumer_$consumer_key", $this->dbh);
    if ($rv === FALSE) {
      return NULL;
    }
    $obj = unserialize($rv);
    if (!(get_class($obj) == 'OAuthConsumer')) {
      return NULL;
    }
    return $obj;
  }/*}}}*/

  function lookup_token($consumer, $token_type, $token) {/*{{{*/
    $rv = dba_fetch("${token_type}_${token}", $this->dbh);
    if ($rv === FALSE) {
      return NULL;
    }
    $obj = unserialize($rv);
    if (!(get_class($obj) == 'OAuthToken')) {
      return NULL;
    }
    return $obj;
  }/*}}}*/

  function lookup_nonce($consumer, $token, $nonce, $timestamp) {/*{{{*/
    return dba_exists("nonce_$nonce", $this->dbh);
  }/*}}}*/

  function new_token($consumer, $type="request") {/*{{{*/
    $key = md5(time());
    $secret = time() + time();
    $token = new OAuthToken($key, md5(md5($secret)));
    if (!dba_insert("${type}_$key", serialize($token), $this->dbh)) {
      trigger_error("doooom!",E_USER_ERROR);
    }
    return $token;
  }/*}}}*/

  function new_request_token($consumer) {/*{{{*/
    return $this->new_token($consumer, "request");
  }/*}}}*/

  function new_access_token($token, $consumer) {/*{{{*/

    $token = $this->new_token($consumer, 'access');
    dba_delete("request_" . $token->key, $this->dbh);
    return $token;
  }/*}}}*/
}/*}}}*/

?>