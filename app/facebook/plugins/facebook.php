<?php

after_filter( 'send_to_facebook', 'insert_from_post' );

before_filter( 'copy_posted_blob', 'insert_from_post' );

function copy_posted_blob( &$model, &$req ) {

  if (!get_profile_id())
    return;

  if (get_option('facebook_status') != 'enabled')
    return;
	
	if (!file_exists($_FILES['post']['tmp_name']['attachment']))
	  return;
	
  $tmp = 'cache'.DIRECTORY_SEPARATOR.make_token();
	$result = copy ( $_FILES['post']['tmp_name']['attachment'] , $tmp );
	$_SESSION['copied_blob'] = $tmp;
	
}

function send_to_facebook( &$model, &$rec ) {

  if (!get_profile_id())
    return;

  if (!$rec->table == 'posts')
    return;

  // if the Record does not have a title or uri, bail out
  if (!(isset($rec->title)) || !(isset($rec->uri)))
    return;
  
  if (get_option('facebook_status') != 'enabled')
    return;
  
  global $db,$prefix;

  $sql = "SELECT facebook_id FROM ".$prefix."facebook_users WHERE profile_id = ".get_profile_id();
  $result = $db->get_result( $sql );
  
  if ($db->num_rows($result) == 1) {

    // Facebook Streams http://brianjesse.com

		$app_id = environment('facebookAppId');
	  $consumer_key = environment('facebookKey');
	  $consumer_secret = environment('facebookSecret');
	  $agent = environment('facebookAppName')." (curl)";

	  add_include_path(library_path());
	  add_include_path(library_path().'facebook-platform/php');
	  add_include_path(library_path().'facebook_stream');

	  require_once "FacebookStream.php";
	  require_once "Services/Facebook.php";

    $userid = $db->result_value($result,0,'facebook_id');

	  $fs = new FacebookStream($consumer_key,$consumer_secret,$agent);

    $notice_content = $rec->attributes['title'];

		if ($userid && isset($_SESSION['copied_blob'])) {
			
		  if (extension_for(type_of($_FILES['post']['name']['attachment'])) == 'jpg')
	      $fs->PhotoUpload($_SESSION['copied_blob'], 0, $notice_content,$userid);
	
      unlink($_SESSION['copied_blob']);
      unset($_SESSION['copied_blob']);
			
		} elseif ($userid) {
			
			$fs->setStatus($notice_content,$userid);
	    
		}

  }
}

