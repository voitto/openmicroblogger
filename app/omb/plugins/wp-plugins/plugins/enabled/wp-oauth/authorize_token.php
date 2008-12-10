<?php

if(!$_REQUEST['oauth_token'] && !$_POST['authorize']) die('No token passed');

$NO_oauth = true;
require_once dirname(__FILE__).'/common.inc.php';
$store = new OAuthWordpressStore();

if(!$_POST['authorize']) {
  $token = $wpdb->escape($_REQUEST['oauth_token']);
  $consumer_key = $store->lookup_token('','request',$token);//verify token
  if(!$consumer_key) die('Invalid token passed');
}//end if ! POST authorize

get_currentuserinfo();
if(!$userdata->ID) {
  $redirect_to = urlencode(get_bloginfo('wpurl').'/wp-content/plugins/wp-oauth/authorize_token.php?oauth_token='.urlencode($_REQUEST['oauth_token']).'&oauth_callback='.urlencode($_REQUEST['oauth_callback']));
  header('Location: '.get_bloginfo('wpurl').'/wp-login.php?redirect_to='.$redirect_to,true,303);
  exit;
}//end if ! userdata->ID

if($_POST['authorize']) {
  session_start();
  $_REQUEST['oauth_callback'] = $_SESSION['oauth_callback']; unset($_SESSION['oauth_callback']);
  $token = $_SESSION['oauth_token']; unset($_SESSION['oauth_token']);
  $consumer_key = $_SESSION['oauth_consumer_key']; unset($_SESSION['oauth_consumer_key']);
  if($_POST['authorize'] != 'Ok') {
    if($_REQUEST['oauth_callback']) {
      header('Location: '.$_REQUEST['oauth_callback'],true,303);
    } else {
      get_header();
      echo '<h2 style="text-align:center;">You chose to cancel authorization.  You may now close this window.</h2>';
      get_footer();
    }//end if-else callback
    exit;
  }//cancel authorize
  $consumers = $userdata->oauth_consumers ? $userdata->oauth_consumers : array();
  $services = get_option('oauth_services');
  $yeservices = array();
  foreach($services as $k => $v)
    if(in_array($k, array_keys($_REQUEST['services'])))
      $yeservices[$k] = $v;
  $consumers[$consumer_key] = array_merge(array('authorized' => true), $yeservices);//it's an array so that more granular data about permissions could go in here
  $userdata->oauth_consumers = $consumers;
  update_usermeta($userdata->ID, 'oauth_consumers', $consumers);
}//end if authorize

if($userdata->oauth_consumers && in_array($consumer_key,array_keys($userdata->oauth_consumers))) {
  $store->authorize_request_token($consumer_key, $token, $userdata->ID);
  if($_REQUEST['oauth_callback']) {
    header('Location: '.$_REQUEST['oauth_callback'],true,303);
  } else {
    get_header();
    echo '<h2 style="text-align:center;">Authorized!  You may now close this window.</h2>';
    get_footer();
  }//end if-else callback
  exit;
} else {
  session_start();//use a session to prevent the consumer from tricking the user into posting the Yes answer
  $_SESSION['oauth_token'] = $token;
  $_SESSION['oauth_callback'] = $_REQUEST['oauth_callback'];
  $_SESSION['oauth_consumer_key'] = $consumer_key;
  get_header();
  $description = $store->lookup_consumer_description($consumer_key);
  if($description) $description = 'Allow '.$description.' to access your Wordpress account and...';
    else $description = 'Allow the service you came from to access your Wordpress account and...';
  ?>
  <div style="text-align:center;">
    <h2><?php echo $description; ?></h2>
    <form method="post" action=""><div>
      <div style="text-align:left;width:15em;margin:0 auto;">
        <ul style="padding:0px;">
      <?php
        $services = get_option('oauth_services');
        foreach($services as $k => $v)
          echo '<li><input type="checkbox" checked="checked" name="services['.htmlentities($k).']" /> '.$k.'</li>';
      ?>
        </ul>
        <br />
        <input type="submit" name="authorize" value="Ok" />
        <input type="submit" name="authorize" value="No" />
      </div>
    </div></form>
  </div>
  <?php
  get_footer();
}//end if user has authorized this consumer

?>
