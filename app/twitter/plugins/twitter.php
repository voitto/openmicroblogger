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
  
  if ((get_option('twitter_status') != 'enabled') &&
   (!isset($_POST['tweet_it']))
     
)
    return;

  
  global $db,$prefix,$request;

  $sql = "SELECT oauth_key,oauth_secret FROM ".$prefix."identities,".$prefix."twitter_users WHERE ".$prefix."twitter_users.profile_id = ".$prefix."identities.id and ".$prefix."identities.person_id = ".get_person_id();
  $result = $db->get_result( $sql );
  
  if ($db->num_rows($result) == 1) {
	
	} else {
	  $sql = "SELECT oauth_key,oauth_secret FROM identities,twitter_users WHERE twitter_users.profile_id = identities.id and identities.person_id = ".get_person_id();
	  $result = $db->get_result( $sql );

	}
	
  if ($db->num_rows($result) == 1) {
    // http://abrah.am
		if (!class_exists('TwitterOAuth'));
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

		  $content = $to->OAuthRequest('https://twitter.com/statuses/update.json', array('status' => $notice_content), 'POST');

		  if (!(class_exists('Services_JSON')))
		    lib_include('json');

	    $json = new Services_JSON();
	    $tw_post = (array) $json->decode($content);

			$Like =& $db->model('Like');

	    $l = $Like->find_by(array('post_id'=>$rec->id));

	    if (!$l->exists){

		    $l = $Like->base();
		    $l->set_value('post_id',$rec->id);
	    }

	    $l->set_value('tw_post_id',$tw_post['id']);

	    $l->save_changes();


    
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

