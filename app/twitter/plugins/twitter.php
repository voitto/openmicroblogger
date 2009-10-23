<?php

// attach a "hook" to the insert_from_post Model method
after_filter( 'send_to_twitter', 'insert_from_post' );

// the "hook" function itself
function send_to_twitter( &$model, &$rec ) {

  if (!($rec->table == 'posts'))
    return;

  if (!get_profile_id())
    return;

  // if the Record does not have a title or uri, bail out
  if (!(isset($rec->title)) || !(isset($rec->uri)))
    return;
  
  if (get_option('twitter_status') != 'enabled')
    return;
  
  global $db,$prefix,$request;

  $sql = "SELECT oauth_key,oauth_secret FROM ".$prefix."twitter_users WHERE profile_id = ".get_profile_id();
  $result = $db->get_result( $sql );
  
  if ($db->num_rows($result) == 1) {

  
    // http://abrah.am
    lib_include('twitteroauth');
    
    $key = $db->result_value($result,0,'oauth_key');
    $secret = $db->result_value($result,0,'oauth_secret');
    $consumer_key = environment( 'twitterKey' );
    $consumer_secret = environment( 'twitterSecret' );    
    $to = new TwitterOAuth(
      $consumer_key, 
      $consumer_secret, 
      $key, 
      $secret
    );

    $notice_content = substr( $rec->title, 0, 140 );  
    
    $content = $to->OAuthRequest('https://twitter.com/statuses/update.xml', array('status' => $notice_content), 'POST');
    
  } else {
    
    wp_plugin_include( 'twitter-tools' );
  
    // set a flag on aktt
    global $aktt;
    $aktt->tweet_from_sidebar = false;

    // truncate the tweet at 140 chars
    $notice_content = substr( $rec->title, 0, 140 );  
  
    // activate Twitter Tools
    $_GET['activate'] = true;
  
    // trip the init() function
    aktt_init();
  
    // make a new tweet object
    $tweet = new aktt_tweet();
  
    // set the tweetbody
    $tweet->tw_text = stripslashes($notice_content);
  
    // send the tweet to Twitter
    $aktt->do_tweet( $tweet );
  }
}

