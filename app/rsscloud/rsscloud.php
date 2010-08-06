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

	if ( '' == get_option( 'cloud_domain' ) )
    set_default_omb_cloud_options();

	add_action('rss2_head','load_my_cloud_element');

  lib_include( 'rsscloud_element' );
	
  if (!signed_in())
    return;
  
  if ( isset( $_POST['cloud_domain'] ))
    update_cloud_options();
  
  elseif ( '' == get_option( 'cloud_domain' ) )
    set_default_omb_cloud_options();

  app_register_init( 'feeds', 'index.html', 'Feeds', 'rsscloud', 2 );

}
  
function set_default_omb_cloud_options(){
	global $request;
  $cloud_domain = $request->domain;
	$cloud_path = '/api/rsscloud/callback';
	$cloud_ping = '/api/rsscloud/ping';
	if (empty($cloud_domain)) {
    if ($request->values[1] == 'http://' && !pretty_urls())
      $cloud_domain = $request->values[2];
	} elseif (strlen($request->path)>1) {
		$cloud_path = '/'.$request->path.$cloud_path;
		$cloud_ping = '/'.$request->path.$cloud_ping;
  }
  if (!pretty_urls()) {
		$cloud_path = $request->path.'?api/rsscloud/callback';
		$cloud_ping = $request->path.'?api/rsscloud/ping';
	}
  add_option('cloud_domain',$cloud_domain,'Cloud Domain');
  add_option('cloud_port','80','Cloud Port');
  add_option('cloud_path',$cloud_path,'Cloud Path');
  add_option('cloud_function','','Cloud Function');
  add_option('cloud_protocol','http-post','Cloud Protocol');
  add_option('cloud_ping',$cloud_ping,'Cloud Ping Path');
}

