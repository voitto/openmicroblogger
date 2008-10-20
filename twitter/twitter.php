<?php



function twitter_init() {
  
  // load Alex King's Twitter Tools WordPress plugin
  wp_plugin_include( 'twitter-tools' );
  
  // set a flag on aktt
  global $aktt;
  $aktt->tweet_from_sidebar = false;
  
  // set the resource, action, button label, app name, grouplevel-unimplemented
  app_register_init( 'ak_twitter', 'edit.html', 'Twitter', 'twitter', 2 );
  
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


