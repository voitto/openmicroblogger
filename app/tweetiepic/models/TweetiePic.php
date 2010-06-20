<?php

global $request;




$request->connect('fb_loggedin');
$request->connect('tw_loggedin');
$request->connect('bz_loggedin');

function fb_loggedin(&$vars){
  $callback = $_GET['callback'];
	$_SESSION['requested_url'] = $_SERVER[HTTP_REFERER];
	echo $callback."(";
	if (get_profile_id() && has_facebook_account())
	  echo "1";
	else
	  echo "0";
	echo ");";
	exit;
}
function tw_loggedin(&$vars){
  $callback = $_GET['callback'];
	$_SESSION['requested_url'] = $_SERVER[HTTP_REFERER];
	echo $callback."(";
	if (get_profile_id() && has_twitter_account())
	  echo "1";
	else
	  echo "0";
	echo ");";
	exit;
}
function bz_loggedin(&$vars){
  $callback = $_GET['callback'];
	$_SESSION['requested_url'] = $_SERVER[HTTP_REFERER];
	echo $callback."(";
	if (get_profile_id() && has_google_account())
	  echo "1";
	else
	  echo "0";
	echo ");";
	exit;
}





$request->connect( 'posts/myapps/partial/tweetiepic/installed', array('action'=>'add_tweetiepic_registration') );

function add_tweetiepic_registration(&$vars){

  extract($vars);

  if (!signed_in()) exit;

  $Setting =& $db->model('Setting');
  
  $added_app = $Setting->find_by(array('name'=>'app','value'=>'tweetiepic','profile_id'=>get_app_id()));

  if (!$added_app){
	  $app = $Setting->base();
	  $app->set_value('profile_id',get_app_id());
	  $app->set_value('person_id',get_person_id());
	  $app->set_value('name','app');
	  $app->set_value('value','tweetiepic');
	  $app->save_changes();
	  $app->set_etag();
  }

  redirect_to($request->url_for(array('resource'=>'posts','action'=>'myapps/partial')));
	
}


$request->connect( 'posts/myapps/partial/tweetiepicpro/installed', array('action'=>'add_tweetiepicpro_registration') );

function add_tweetiepicpro_registration(&$vars){

  extract($vars);

  if (!signed_in()) exit;

  $Setting =& $db->model('Setting');
  
  $added_app = $Setting->find_by(array('name'=>'app','value'=>'tweetiepicpro','profile_id'=>get_app_id()));

  if (!$added_app){
	  $app = $Setting->base();
	  $app->set_value('profile_id',get_app_id());
	  $app->set_value('person_id',get_person_id());
	  $app->set_value('name','app');
	  $app->set_value('value','tweetiepicpro');
	  $app->save_changes();
	  $app->set_etag();
  }

  redirect_to($request->url_for(array('resource'=>'posts','action'=>'myapps/partial')));
	
}

