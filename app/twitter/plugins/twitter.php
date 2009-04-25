<?php

// attach a "hook" to the insert_from_post Model method
after_filter( 'send_to_twitter', 'insert_from_post' );

// the "hook" function itself
function send_to_twitter( &$model, &$rec ) {

  if (!get_profile_id())
    return;

  // if the Record does not have a title or uri, bail out
  if (!(isset($rec->title)) || !(isset($rec->uri)))
    return;
  
  if (get_option('twitter_status') != 'enabled')
    return;
    
  // truncate the tweet at 140 chars
  $notice_content = substr( $rec->title, 0, 140 );  
  
  // activate Twitter Tools
  $_GET['activate'] = true;
  
  // trip the init() function
  aktt_init();
  
  // get the Twitter Tools object
  global $aktt;
  
  // make a new tweet object
  $tweet = new aktt_tweet();
  
  // set the tweetbody
  $tweet->tw_text = stripslashes($notice_content);
  
  // send the tweet to Twitter
  $aktt->do_tweet( $tweet );
  
}

?>