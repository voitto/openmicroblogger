<?php
return;
function set_up_cloud_ping(){
	$pid = get_profile_id();
	global $optiondata,$request,$blogdata;
	$optiondata['cloud_domain'] = get_option('cloud_domain',$pid);
	$optiondata['cloud_port'] = get_option('cloud_port',$pid);
	$optiondata['cloud_path'] = get_option('cloud_path',$pid);
	$optiondata['cloud_function'] = get_option('cloud_function',$pid);
	$optiondata['cloud_protocol'] = get_option('cloud_protocol',$pid);
	$blogdata['rss2_url'] = $request->url_for(array('resource'=>'api/statuses/user_timeline/')).$pid.'.rss';
}

before_filter( 'set_up_cloud_ping', 'insert_from_post');

before_filter( 'rss_cloud_ping', 'insert_from_post' );

