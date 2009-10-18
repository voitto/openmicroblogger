<?php

function rsscloud_schedule_post_notifications() {
	// prevent Joseph Scott's plugin from loading its update feature
}

function load_my_cloud_element() {
	global $request;
  if (!signed_in() && $request->byid > 0){
	  global $optiondata;
		$optiondata['cloud_domain'] = get_option('cloud_domain',$request->byid);
		$optiondata['cloud_port'] = get_option('cloud_port',$request->byid);
		$optiondata['cloud_path'] = get_option('cloud_path',$request->byid);
		$optiondata['cloud_function'] = get_option('cloud_function',$request->byid);
		$optiondata['cloud_protocol'] = get_option('cloud_protocol',$request->byid);
  }
}

function rsscloud_init(){

  lib_include( 'rsscloud/rsscloud' );

	add_action('rss2_head','load_my_cloud_element');

  lib_include( 'rsscloud_element' );
	
  if (!signed_in())
    return;
  
  if ( isset( $_POST['cloud_domain'] ))
    update_cloud_options();
  
  elseif ( '' == get_option( 'cloud_domain' ) )
    set_default_cloud_options();

  app_register_init( 'admin', 'cloud.html', 'rssCloud Options', 'rsscloud', 2 );

}
  
