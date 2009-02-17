<?php

require_once dirname(__FILE__).'/OAuth.php';

//Assumes a Wordpress context ($wpdb especially)

class OAuthWordpressStore extends OAuthDataStore {

  function __construct() {
    global $wpdb;
    if(get_option('oauth_version') < 0.12) {
      $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}oauth_consumers (consumer_key CHAR(40) PRIMARY KEY, secret CHAR(40), description CHAR(40))");
      $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}oauth_tokens (consumer_key CHAR(40), token CHAR(40), secret CHAR(40), token_type CHAR(7), nonce CHAR(40), user_id TINYINT DEFAULT 0, expires INT DEFAULT 0)");
      $wpdb->query("INSERT INTO {$wpdb->prefix}oauth_consumers (consumer_key, secret, description) VALUES ('DUMMYKEY', '', 'Unidentified Consumer')");
      update_option('oauth_version', 0.12);
    }
  }//end contructor

  function __destruct() {}

  function new_consumer($description) {
    global $wpdb;
    $description = $wpdb->escape($description);
    $consumer_key = sha1($_SERVER['REMOTE_ADDR'] . microtime() . (string)rand());
    $secret = sha1(md5($_SERVER['REMOTE_ADDR']) . microtime() . (string)time());
    $wpdb->query("INSERT INTO {$wpdb->prefix}oauth_consumers (consumer_key, secret, description) VALUES ('$consumer_key', '$secret', '$description')");
    return new OAuthConsumer($consumer_key, $secret);
  }//end function new_consumer

  function lookup_consumer($consumer_key) {
    global $wpdb;
    $secret = $wpdb->get_var("SELECT secret FROM {$wpdb->prefix}oauth_consumers WHERE consumer_key='$consumer_key' LIMIT 1");
    if($secret === FALSE) return NULL;
    return new OAuthConsumer($consumer_key, $secret);
  }//end function lookup_consumer

  function lookup_consumer_description($consumer_key) {
    global $wpdb;
    $description = $wpdb->get_var("SELECT description FROM {$wpdb->prefix}oauth_consumers WHERE consumer_key='$consumer_key' LIMIT 1");
    return $description;
  }//end function lookup_consumer_description

  function user_from_token($consumer_key, $token) {
    global $wpdb;
    return $wpdb->get_var("SELECT user_id FROM {$wpdb->prefix}oauth_tokens WHERE consumer_key='$consumer_key' AND token='$token'");
  }//end function user_from_token

  function authorize_request_token($consumer_key, $token, $user_id) {
    global $wpdb;
    $wpdb->query("UPDATE {$wpdb->prefix}oauth_tokens SET user_id=$user_id WHERE consumer_key='$consumer_key' AND token='$token'");
  }//end function authorize_roquest_token

  function token_expires($token) {
    global $wpdb;
    return $wpdb->get_var("SELECT expires FROM {$wpdb->prefix}oauth_tokens WHERE token='{$token->key}' AND secret='{$token->secret}'");
  }//end function teken_expires

  function lookup_token($consumer, $token_type, $token) {
    global $wpdb;
    @$consumer_key = $consumer->key;
    if(!$consumer_key)
      $consumer_key = $wpdb->get_var("SELECT consumer_key FROM {$wpdb->prefix}oauth_tokens WHERE token='$token' AND token_type='$token_type' LIMIT 1");
    $secret = $wpdb->get_var("SELECT secret FROM {$wpdb->prefix}oauth_tokens WHERE consumer_key='{$consumer_key}' AND token='$token' AND token_type='$token_type' LIMIT 1");
    if(!$secret) return NULL;
    $token = new OAuthToken($token, $secret);
    $expires = $this->token_expires($token);
    if($expires && $expires < time()) {
      $wpdb->get_var("DELETE FROM {$wpdb->prefix}oauth_tokens WHERE token='{$token->key}' AND secret='{$token->secret}' AND token_type='$token_type' LIMIT 1");
      return NULL;
    }//end if expired
    if($consumer) return $token;
      else return $consumer_key;
  }//end function lookup_token

  function lookup_nonce($consumer, $token, $nonce, $timestamp) {
    global $wpdb;
    $nonce = $wpdb->get_var("SELECT nonce FROM {$wpdb->prefix}oauth_tokens WHERE consumer_key='{$consumer->key}' AND token='$token' AND (token_type='request' OR token_type='access') AND nonce='$nonce' LIMIT 1");
    if(!$nonce || !preg_match("/(\S)/", $nonce)) return NULL;
    return $nonce;
  }//end function lookup_token

  function new_token($consumer, $token_type, $user_id=0) {
    global $wpdb;
    $token = sha1($_SERVER['REMOTE_ADDR'] . microtime() . (string)rand());
    $secret = sha1(md5($_SERVER['REMOTE_ADDR']) . microtime() . (string)time());
    $wpdb->query("INSERT INTO {$wpdb->prefix}oauth_tokens (consumer_key, token_type, token, secret, user_id, expires) VALUES ('{$consumer->key}', '$token_type', '$token', '$secret', $user_id, 0)");
    return new OAuthToken($token, $secret);
  }//end function now_token

  function new_request_token($consumer) {
    return $this->new_token($consumer, 'request');
  }//end new_request_token

  function new_access_token($request_token,$consumer) {
    global $wpdb;
    $request_token = $request_token->to_string();
    preg_match('/oauth_token=(.*?)&/',$request_token,$request_token);
    $request_token = $request_token[1];
    $user_id = $wpdb->get_var("SELECT user_id FROM {$wpdb->prefix}oauth_tokens WHERE token_type='request' AND consumer_key='{$consumer->key}' AND token='$request_token' LIMIT 1");
    if(!$user_id) trigger_error('Request token not authorized', E_USER_ERROR);
    $wpdb->query("DELETE FROM {$wpdb->prefix}oauth_tokens WHERE token_type='request' AND consumer_key='{$consumer->key}' AND token='$request_token'");
    return $this->new_token($consumer, 'access', $user_id);
  }//end new_request_token

}//end class OAuthWorepressStore

?>