<?php

after_filter( 'send_to_flickr', 'insert_from_post' );

before_filter( 'copy_posted_blob_flickr', 'insert_from_post' );

function copy_posted_blob_flickr( &$model, &$req ) {

  if (!get_profile_id())
    return;

  if (get_option('flickr_status') != 'enabled')
    return;
	
	if (!file_exists($_FILES['post']['tmp_name']['attachment']))
	  return;
	
  $tmp = 'cache'.DIRECTORY_SEPARATOR.make_token().extension_for(type_of($_FILES['post']['name']['attachment']));
	$result = copy ( $_FILES['post']['tmp_name']['attachment'] , $tmp );
	$_SESSION['copied_blob_flickr'] = $tmp;
	
}

function send_to_flickr( &$model, &$rec ) {
	
  global $db;

	$key = environment( 'flickrKey' );
	$secret = environment( 'flickrSecret' );

  if (empty($key))
    return;

  if (!get_profile_id())
    return;

  if (!$rec->table == 'posts')
    return;

  if (!(isset($rec->title)) || !(isset($rec->uri)))
    return;
  
	$Setting =& $db->model('Setting');
	
	$Post =& $db->model('Post');
	$p= $Post->base();
	$p->set_value('title','waypoint1');
	$p->save_changes();

	$sql = "SELECT value FROM settings WHERE profile_id = '".get_profile_id()."' AND name = 'flickr_status'";
  $result = $db->get_result( $sql );
  $enabled = $db->result_value( $result, 0, "value" );
  
	//$enabled = $Setting->find_by(array('settings.name'=>'flickr_status','settings.profile_id'=>get_profile_id()));

	$p= $Post->base();
	$p->set_value('title','waypoint2');
	$p->save_changes();

  if (!($enabled == 'enabled'))
    return;

  global $db,$prefix;

  $notice_content = $rec->attributes['title'];

	add_include_path(library_path()."phpFlickr");

	include('phpFlickr.php');

	$f = new phpFlickr( $key, $secret );

  if (extension_for(type_of($_FILES['post']['name']['attachment'])) == 'jpg'
		&& (file_exists($_SESSION['copied_blob_flickr']))){
			$p= $Post->base();
			$p->set_value('title','waypoint3');
			$p->save_changes();

			$sql = "SELECT value FROM settings WHERE profile_id = '".get_profile_id()."' AND name = 'flickr_frob'";
		  $result = $db->get_result( $sql );
		  $stat = $db->result_value( $result, 0, "value" );
		
			//$stat = $Setting->find_by(array('settings.name'=>'flickr_frob','settings.profile_id'=>get_profile_id()));

			if (!empty($stat)) {
				$f->setToken($stat);
				$f->sync_upload(
					$_SESSION['copied_blob_flickr'],
					$notice_content,
					'',
					null,
					1,
					1,
					1
				);
				unlink($_SESSION['copied_blob_flickr']);
	      unset($_SESSION['copied_blob_flickr']);

				//$photo, 
				//$title = null, 
				//$description = null, 
				//$tags = null, 
				//$is_public = null, 
				//$is_friend = null, 
				//$is_family = null
				
			}
	} else {
		$p= $Post->base();
		$p->set_value('title','waypoint4');
		$p->save_changes();
		
		$Setting =& $db->model('Setting');

		$sql = "SELECT value FROM settings WHERE profile_id = '".get_profile_id()."' AND name = 'flickr_frob'";
	  $result = $db->get_result( $sql );
	  $stat = $db->result_value( $result, 0, "value" );

		//$stat = $Setting->find_by(array('settings.name'=>'flickr_frob','settings.profile_id'=>get_profile_id()));
		if (!empty($stat)) {
			$Entry =& $db->model('Entry');
			$e = $Entry->find($rec->entry_id);
	    if (extension_for($e->content_type) == 'jpg'){
			  $download = tempnam( "/tmp", "upload".$rec->id.".jpg" );
//        $result = download($e->uri.".jpg",$download);

set_time_limit(0);
ini_set('display_errors',false);//Just in case we get some errors, let us know....
$fp = fopen ($download, 'w+');//This is the file where we save the information
$ch = curl_init($rec->uri.".jpg");//Here is the file we are downloading
curl_setopt($ch, CURLOPT_TIMEOUT, 5000);
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);
curl_close($ch);
fclose($fp);

	    }	
			$f->setToken($stat);
			$f->sync_upload(
			  $download,
				$rec->title,
				'',
				null,
				1,
				1,
				1
			);
	   }
}}