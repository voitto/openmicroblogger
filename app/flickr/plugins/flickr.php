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
  
  if (get_option('flickr_status') != 'enabled')
    return;
  
  global $db,$prefix;

  $notice_content = $rec->attributes['title'];

  if (extension_for(type_of($_FILES['post']['name']['attachment'])) == 'jpg'
		&& (file_exists($_SESSION['copied_blob_flickr']))){
		
			add_include_path(library_path()."phpFlickr");

			include('phpFlickr.php');

			$f = new phpFlickr( $key, $secret );

			$Setting =& $db->model('Setting');

			$stat = $Setting->find_by(array('name'=>'flickr_frob','profile_id'=>get_profile_id()));
			
			if (!empty($stat->value)) {
				$f->setToken($stat->value);
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
	}
}