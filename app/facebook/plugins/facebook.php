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
  
  global $db,$prefix,$request;

//  $sql = "SELECT facebook_id FROM ".$prefix."facebook_users WHERE profile_id = ".get_profile_id();
  $sql = "SELECT facebook_id FROM identities,facebook_users WHERE facebook_users.profile_id = identities.id and identities.person_id = ".get_person_id();
  $result = $db->get_result( $sql );
  
  if ($db->num_rows($result) == 1) {

    // Facebook Streams http://brianjesse.com
	  ini_set('display_errors','1');
	  ini_set('display_startup_errors','1');
	  error_reporting (E_ALL & ~E_NOTICE );

		$app_id = environment('facebookAppId');
	  $consumer_key = environment('facebookKey');
	  $consumer_secret = environment('facebookSecret');
	  $agent = environment('facebookAppName')." (curl)";

    $Post =& $db->model('Post');
	  add_include_path(library_path());
	  add_include_path(library_path().'facebook-platform/php');
	  add_include_path(library_path().'facebook_stream');

if (!function_exists('json_encode'))
  lib_include('json');
	  require_once "facebook.php";
	  require_once "FacebookStream.php";
	  require_once "Services/Facebook.php";
 

   	$sesskey = environment('facebookSession');

    $uid = $db->result_value($result,0,'facebook_id');


		//$fb = new Facebook($consumer_key, $consumer_secret, true);

		//$fb->api_client->session_key = $sesskey;
		//$fb->api_client->user = $uid;
		$fs = new FacebookStream($consumer_key,$consumer_secret,$agent,$app_id);
	  
//    $fs->api->sessionKey = $sesskey;
    $fs->setSess($sesskey);
    $notice_content = $rec->attributes['title'];

		if ($uid && isset($_SESSION['copied_blob'])) {
			
		  //if (extension_for(type_of($_FILES['post']['name']['attachment'])) == 'jpg')
	    //  $fs->photoUpload($_SESSION['copied_blob'], 0, $notice_content,$uid);
	
      unlink($_SESSION['copied_blob']);
      unset($_SESSION['copied_blob']);
			
		} elseif ($uid) {
			
			$Upload =& $db->model('Upload');
			$Entry =& $db->model('Entry');
			$u = $Upload->find_by(array(
				'profile_id'=>get_profile_id()
				));
			if (!$u->exists) return;

			$e = $Entry->find($u->entry_id);

			$download = false;
		  $origurl = $request->url_for(array('resource'=>'uploads','action'=>'entry.'.extension_for($e->content_type),'id'=>$u->id));
			$thumburl = $request->url_for(array('resource'=>'uploads','action'=>'preview.'.extension_for($e->content_type),'id'=>$u->id));
	    $posturl = $request->url_for(array('resource'=>'posts','id'=>$rec->id));
	    if (extension_for($e->content_type) == 'jpg'){
			  $download = tempnam( "/tmp", "upload".$u->id.".jpg" );
				
					set_time_limit(0);
					ini_set('display_errors',false);//Just in case we get some errors, let us know....
					$fp = fopen ($download, 'w+');//This is the file where we save the information
					$ch = curl_init($origurl);//Here is the file we are downloading
					curl_setopt($ch, CURLOPT_TIMEOUT, 5000);
					curl_setopt($ch, CURLOPT_FILE, $fp);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
					curl_exec($ch);
					curl_close($ch);
					fclose($fp);
	    }
			
			if ($download){
				
		      $fs->photoUpload($download, 0, $notice_content,$uid);
				
				$message = $notice_content;
				$attachment = array(
				      'name' => $notice_content,
				      'href' => $posturl,
				      'caption' => '',
				      'description' => $notice_content,
				      'media' => array(array(
								'type' => 'image',
				        'src' => $origurl,
				        'href' => $posturl
				)));

				$attachment = json_encode($attachment);
//				$fb->api_client->stream_publish($notice_content, $attachment);
				
		 
		 } else {
//			  $fb->api_client->stream_publish($notice_content);
			  $fs->setStatus($notice_content,$uid);
			}
	    
		}

  }
}

