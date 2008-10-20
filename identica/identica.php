<?php


function identica_init() {
  
  // load Alex King's Twitter Tools WordPress plugin
  wp_plugin_include( 'twitter-tools' );
  
  // set a flag on aktt
  global $aktt;
  $aktt->tweet_from_sidebar = false;
  
  // set the resource, action, button label, app name, grouplevel-unimplemented
  app_register_init( 'dents', 'edit.html', 'Identica', 'identica', 2 );
  
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

