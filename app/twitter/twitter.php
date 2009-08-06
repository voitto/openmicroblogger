<?php

function download_tweets(&$request,&$route) {
  
  if (!($request->resource == 'posts' && isset($request->params['byid'])))
    return;
    
  if (!($request->params['byid'] == get_profile_id()))
    return;
    
  global $response;
  
  require_once $GLOBALS['PATH']['dbscript'] . 'aggregatefeed' . '.php';
  
  update_my_tweets();
  
  $find_by = array(
    'ak_twitter_identities.identity_id'=>get_profile_id()
  );
  
  $tweets = new Collection( 'ak_twitter', $find_by );
  
  $colls = array(
    'ak_twitter'=>$tweets,
    'posts'=>$response->collection
  );

  $collection = new AggregateFeed($colls);
  
  $response->collection = $collection;
  
}

function twitter_init() {
  include 'wp-content/language/lang_chooser.php'; //Loads the language-file  
  // load Alex King's Twitter Tools WordPress plugin
  
  // set the resource, action, button label, app name, grouplevel-unimplemented
  app_register_init( 'ak_twitter', 'edit.html', $txt['twitter_twitter'], 'twitter', 2 );
  
  //before_filter('download_tweets','get');
}

function twitter_show() {
  // show something to profile visitors
  // the_content
}

function twitter_head() {
  // always load in head
  // wp_head, admin_head
}

function twitter_menu() {
  // trigger before Admin menu renders
  // admin_menu
}

function twitter_post() {
  // publish_post
}




function update_my_tweets() {
  
  $profile_id = get_profile_id();
  
  if (!$profile_id)
    return;

  // activate Twitter Tools
  $_GET['activate'] = true;
  
  // trip the init() function
  aktt_init();
  
  // get the Twitter Tools object
  global $wpdb, $aktt, $db;
  
	if (empty($aktt->twitter_username) 
		|| empty($aktt->twitter_password) 
	) {
		return;
	}
  
  // make a new tweet object
  $tweet = new aktt_tweet();

  // let the last update run for 5 minutes
  if (time() - intval(get_option('aktt_doing_tweet_download')) < 300) {
    return;
  }
  
  update_option('aktt_doing_tweet_download', time());
  
  if (empty($aktt->twitter_username) || empty($aktt->twitter_password)) {
    update_option('aktt_doing_tweet_download', '0');
    die();
  }
  
  require_once(ABSPATH.WPINC.'/class-snoopy.php');
  
  $snoop = new Snoopy;
  
  $snoop->agent = 'Twitter Tools http://alexking.org/projects/wordpress';
  
  $snoop->user = $aktt->twitter_username;
  $snoop->pass = $aktt->twitter_password;
  
  $snoop->fetch('http://tweetpass.com/statuses/friends_timeline.json');
  
  if (!strpos($snoop->response_code, '200')) {
    update_option('aktt_doing_tweet_download', '0');
    return;
  }
  
  $data = $snoop->results;
  
  $hash = md5($data);
  if ($hash == get_option('aktt_update_hash')) {
    update_option('aktt_doing_tweet_download', '0');
    return;
  }
  
  $json = new Services_JSON();
  
  $tweets = $json->decode($data);
  
  if (is_array($tweets) && count($tweets) > 0) {
    
    $tweet_ids = array();
    
    foreach ($tweets as $tweet) {
      $tweet_ids[] = $wpdb->escape($tweet->id);
    }
    
    $existing_ids = $wpdb->get_col("
      SELECT tw_id
      FROM $wpdb->aktt
      WHERE tw_id
      IN ('".implode("', '", $tweet_ids)."')
    ");
    
    $new_tweets = array();
    
    foreach ($tweets as $tw_data) {
      if (!$existing_ids || !in_array($tw_data->id, $existing_ids)) {
        $tweet = new aktt_tweet(
          $tw_data->id
          , $tw_data->text
        );
        $tweet->tw_created_at = $tweet->twdate_to_time($tw_data->created_at);
        $new_tweets[] = $tweet;
      }
    }
    
    foreach ($new_tweets as $tweet) {
      $AkTwitter =& $db->get_table( 'ak_twitter' );
      $Entry     =& $db->get_table( 'entries' );
      $t = $AkTwitter->find_by('tw_id',$tweet->tw_id);
      if (!$t) {
        $tweet->add();
        $created = date("Y-m-d H:i:s",($tweet->tw_created_at - (8 * 3600)));
        $t = $AkTwitter->find( $db->last_insert_id( $AkTwitter ) );
        if ($t)
          $t->set_etag();
        $atomentry = $Entry->find_by( array('resource'=>'ak_twitter', 'record_id'=>$t->id), $t->id );
        if ($atomentry)
          $result = $db->get_result("UPDATE entries SET last_modified = '$created' WHERE id = ".$atomentry->id);
        $user = new Snoopy;
        $user->agent = 'Twitter Tools http://alexking.org/projects/wordpress';
        $user->user = $aktt->twitter_username;
        $user->pass = $aktt->twitter_password;
        $user->fetch( 'http://tweetpass.com/statuses/show/'.$tweet->tw_id.'.json' );
        $data = $user->results;
        $json = new Services_JSON();
        $notice = $json->decode($data);
        $uarr = $notice->user;
        $TwitterUser =& $db->model('TwitterUser');
        $twuser = $TwitterUser->find_by('twitter_id',$uarr->id);
        if (!$twuser) {
          $twuser = $TwitterUser->base();
          $twuser->set_value('description',$uarr->description);
          $twuser->set_value('screen_name',$uarr->screen_name);
          $twuser->set_value('url',$uarr->url);
          $twuser->set_value('name',$uarr->name);
          $twuser->set_value('protected',$uarr->protected);
          $twuser->set_value('followers_count',$uarr->followers_count);
          $twuser->set_value('profile_image_url',$uarr->profile_image_url);
          $twuser->set_value('location',$uarr->location);
          $twuser->set_value('twitter_id',$uarr->id);
          $twuser->save_changes();
        }
        $t->set_value('profile_id',$twuser->twitter_id);
        $t->save_changes();
      }
      
      $AkTwitter->has_and_belongs_to_many('identities');
      
      $join =& $db->get_table($Entry->join_table_for('ak_twitter', 'identities'));
      
      $j = $join->base();
      $j->set_value('aktwitter_id',$t->id);
      $j->set_value('identity_id',get_profile_id());
      $j->save_changes();
      
      
    }
    
  }
  
  update_option('aktt_update_hash', $hash);
  update_option('aktt_last_tweet_download', time());
  update_option('aktt_doing_tweet_download', '0');

}

