<?php


function identica_init() {
  include 'wp-content/language/lang_chooser.php'; //Loads the language-file  
  // load Alex King's Twitter Tools WordPress plugin
  wp_plugin_include( 'twitter-tools' );
  
  // set a flag on aktt
  global $aktt;
  $aktt->tweet_from_sidebar = false;
  
  // set the resource, action, button label, app name, grouplevel-unimplemented
  app_register_init( 'dents', 'edit.html', $txt['identica_identica'], 'identica', 2 );
  
}

function identica_show() {
  // show something to profile visitors
  // the_content
}

function identica_head() {
  // always load in head
  // wp_head, admin_head
}

function identica_menu() {
  // trigger before Admin menu renders
  // admin_menu
}

function identica_post() {
  // publish_post
}


function do_dent($tweet = '') {
  
  global $aktt;
  
  
	if (empty($aktt->twitter_username) 
		|| empty($aktt->twitter_password) 
		|| empty($tweet)
		|| empty($tweet->tw_text)
	) {
		return;
	}
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	$snoop = new Snoopy;
	$snoop->agent = 'OpenMicroBlogger http://openmicroblogger.org';
	$snoop->rawheaders = array(
		'X-Twitter-Client' => 'OpenMicroBlogger'
		, 'X-Twitter-Client-Version' => $aktt->version
		, 'X-Twitter-Client-URL' => 'http://alexking.org/projects/wordpress/twitter-tools.xml'
	);
	$snoop->user = $aktt->twitter_username;
	$snoop->pass = $aktt->twitter_password;
	$snoop->submit(
		'http://identi.ca/api/statuses/update.json'
		, array(
			'status' => $tweet->tw_text
			, 'source' => 'twittertools'
		)
	);
	if (strpos($snoop->response_code, '200')) {
		update_option('aktt_last_dent_download', strtotime('-28 minutes'));
		return true;
	}
	return false;
}


