<?php
/*
Plugin Name: WP-OAuth
Plugin URI: http://singpolyma.net/plugins/oauth/
Description: Enables OAuth services on your Wordpress blog.
Version: 0.13
Author: Stephen Paul Weber
Author URI: http://singpolyma.net/
*/

//Licensed under an MIT-style licence

function oauth_accept() {

  require_once dirname(__FILE__).'/common.inc.php';
  require_once dirname(__FILE__).'/../../../wp-includes/pluggable.php';
  @include_once dirname(__FILE__).'/../xrds-simple.php';
  
  if(function_exists('register_xrd')) {
    $xrds = get_option('xrds_simple');
    if(!$xrds['oauth']) {
      register_xrd_service('main', 'OAuth Dummy Service', array(
        'Type' => array( array('content' => 'http://oauth.net/discovery/1.0') ),
        'URI' => array( array('content' => '#oauth' ) ),
      ) );

      register_xrd('oauth');
      register_xrd_service('oauth', 'OAuth Request Token', array(
        'Type' => array( 
          array('content' => 'http://oauth.net/core/1.0/endpoint/request'),
          array('content' => 'http://oauth.net/core/1.0/parameters/uri-query'),
          array('content' => 'http://oauth.net/core/1.0/signature/HMAC-SHA1'),
        ),
        'URI' => array( array('content' => get_bloginfo('wpurl').'/wp-content/plugins/wp-oauth/request_token.php' ) ),
      ) );
      register_xrd_service('oauth', 'OAuth Authorize Token', array(
        'Type' => array( 
          array('content' => 'http://oauth.net/core/1.0/endpoint/authorize'),
          array('content' => 'http://oauth.net/core/1.0/parameters/uri-query'),
        ),
        'URI' => array( array('content' => get_bloginfo('wpurl').'/wp-content/plugins/wp-oauth/authorize_token.php' ) ),
      ) );
      register_xrd_service('oauth', 'OAuth Access Token', array(
        'Type' => array( 
          array('content' => 'http://oauth.net/core/1.0/endpoint/access'),
          array('content' => 'http://oauth.net/core/1.0/parameters/uri-query'),
          array('content' => 'http://oauth.net/core/1.0/signature/HMAC-SHA1'),
        ),
        'URI' => array( array('content' => get_bloginfo('wpurl').'/wp-content/plugins/wp-oauth/access_token.php' ) ),
      ) );
      register_xrd_service('oauth', 'OAuth Resources', array(
        'Type' => array( 
          array('content' => 'http://oauth.net/core/1.0/endpoint/resource'),
          array('content' => 'http://oauth.net/core/1.0/parameters/uri-query'),
          array('content' => 'http://oauth.net/core/1.0/signature/HMAC-SHA1'),
        ),
      ) );
      register_xrd_service('oauth', 'OAuth Static Token', array(
        'Type' => array( 
          array('content' => 'http://oauth.net/discovery/1.0/consumer-identity/static'),
        ),
        'LocalID' => array( array('content' => 'DUMMYKEY' ) ),
      ) );
    }//end if ! oauth
  }//end if register_xrd

  $services = get_option('oauth_services');
  $services['Post Comments'] = array('wp-comments-post.php');
  $services['Edit and Create Entries and Categories'] = array('wp-app.php');
  
  $store = new OAuthWordpressStore();
  
  global $request,$omb_routes;
  
  if (isset($request->action) && in_array($request->action,$omb_routes)) {
    //$server = new OAuthServer($store);
    //$sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
    //$plaintext_method = new OAuthSignatureMethod_PLAINTEXT();
    //$server->add_signature_method($sha1_method);
    //$server->add_signature_method($plaintext_method);

    //$req = OAuthRequest::from_request();
    //list($consumer, $token) = $server->verify_request($req);
    //$userid = $store->user_from_token($consumer->key, $token->key);
    //$authed = get_usermeta($userid, 'oauth_consumers');
    //$authed = $authed[$consumer->key];
    //if($authed && $authed['authorized']) {
    //  $allowed = false;
  //    foreach($authed as $ends)
  //      if(is_array($ends))
  //        foreach($ends as $end)
  //          if(strstr($_SERVER['SCRIPT_URI'], $end))
  //            $allowed = true;
  //    if($allowed)
  //      set_current_user($userid);
    //}//end if
  }
}//end function oauth_accept
//if(!$NO_oauth)
  oauth_accept();

function oauth_page() {
  global $wpdb;
  if($_POST['new_consumer']) {
    $store = new OAuthWordpressStore();
    $store->new_consumer($_POST['new_consumer']);
    echo '<div id="message" class="updated fade"><strong><p>New Consumer pair generated.</p></strong></div>';
  }//end if new consumer
  echo '<div class="wrap">';
  echo '<h2>OAuth Consumers</h2>';
  $consumers = $wpdb->get_results("SELECT description, consumer_key, secret FROM {$wpdb->prefix}oauth_consumers", ARRAY_A);
  echo '<ul>';
  foreach($consumers as $consumer) {
    echo '  <li><b>'.($consumer['description'] ? $consumer['description'] : 'Oauth Consumer').'</b>';
    echo '    <dl>';
    echo '      <dt>consumer_key</dt>';
    echo '        <dd>'.$consumer['consumer_key'].'</dd>';
    echo '      <dt>secret</dt>';
    echo '        <dd>'.$consumer['secret'].'</dd>';
    echo '    </dl>';
    echo '  </li>';
    $aconsumer = $consumer;//for test link
  }//end foreach consumers
  echo '</ul>';
  echo '<h3>Add OAuth Consumer</h3>';
  echo '<form method="post" action=""><div>';
  echo '<input type="text" name="new_consumer" />';
  echo '<input type="submit" value="Create Key/Secret Pair">';
  echo '</div></form>';
  echo '<h3>Endpoints</h3>';
  echo '<dl>';
  echo '  <dt>Request token endpoint</dt>';
  echo '    <dd>'.get_bloginfo('wpurl').'/wp-content/plugins/wp-oauth/request_token.php</dd>';
  echo '  <dt>Authorize token endpoint</dt>';
  echo '    <dd>'.get_bloginfo('wpurl').'/wp-content/plugins/wp-oauth/authorize_token.php</dd>';
  echo '  <dt>Access token endpoint</dt>';
  echo '    <dd>'.get_bloginfo('wpurl').'/wp-content/plugins/wp-oauth/access_token.php</dd>';
  echo '</dl>';
  $anid = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_type='post' ORDER BY post_date DESC LIMIT 1");
  echo '<a href="http://singpolyma.net/oauth/example/wp_auto_client3.php?'
.'&amp;blog='.urlencode(get_bloginfo('home').'/')
.'&amp;api_endpoint='.urlencode(get_bloginfo('wpurl').'/wp-comments-post.php')
.'&amp;post_id='.$anid
.'">Click here for a test page &raquo;</a>';
}//end function oauth_page

function oauth_tab($s) {
  add_submenu_page('options-general.php', 'OAuth', 'OAuth', 1, __FILE__, 'oauth_page');
  return $s;
}//end function
add_action('admin_menu', 'oauth_tab');

function oauth_services_render() {
  global $userdata;
  get_currentuserinfo();
  $services = get_option('oauth_services');

  if($_POST['save']) {
    $userdata->oauth_consumers = array();
    if(!$_POST['services']) $_POST['services'] = array();
    foreach($_POST['services'] as $key => $value) {
      $service = array('authorized' => true);
      foreach($services as $k => $v)
        if(in_array($k, array_keys($value)))
          $service[$k] = $v;
      $userdata->oauth_consumers[$key] = $service;
    }//end foreach services
    update_usermeta($userdata->ID, 'oauth_consumers', $userdata->oauth_consumers);
  }//end if save

  require_once dirname(__FILE__).'/OAuthWordpressStore.php';
  $store = new OAuthWordpressStore();
  echo '<div class="wrap">';
  echo '  <h2>Change Service Permissions</h2>';
  echo '  <form method="post" action="">';
  foreach($userdata->oauth_consumers as $key => $values) {
    echo '    <h3>'.$store->lookup_consumer_description($key).'</h3><ul>';
    foreach($services as $k => $v)
      echo '      <li><input type="checkbox" '.($values[$k] && count($values[$k]) ? 'checked="checked"' : '').' name="services['.htmlentities($key).']['.htmlentities($k).']" /> '.$k.'</li>';
    echo '    </ul>';
  }//end foreach
  echo '    <p><input type="submit" name="save" value="Save &raquo;" /></p>';
  echo '  </form>';
  echo '</div>';
}//end function oauth_services_render
function oauth_services_tab($s) {
  add_submenu_page('profile.php', 'Services', 'Services', 1, __FILE__, 'oauth_services_render');
  return $s;
}//end function
add_action('admin_menu', 'oauth_services_tab');

?>