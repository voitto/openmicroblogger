<?php

after_filter( 'send_to_buzz', 'insert_from_post' );

function send_to_buzz( &$model, &$rec ) {
  if (!($rec->table == 'posts'))
    return;
  if (!get_profile_id())
    return;
  if (!(isset($rec->title)) || !(isset($rec->uri)))
    return;

//  if ((get_option('buzz_status') != 'enabled') &&
//   (!isset($_POST['buzz_it'])))
//    return;

global $db,$prefix,$request;
$sql = "SELECT value FROM settings WHERE profile_id = '".get_profile_id()."' AND name = 'google_key'";
$result = $db->get_result( $sql );
$gkey = $db->result_value( $result, 0, "value" );
$sql = "SELECT value FROM settings WHERE profile_id = '".get_profile_id()."' AND name = 'google_secret'";
$result = $db->get_result( $sql );
$gsec = $db->result_value( $result, 0, "value" );

if (!$result)
  return;
	db_include('helper');
	db_include('buzz');
	if (!class_exists('TwitterOAuth'));
	  lib_include('twitteroauth');
   $callback = $request->url_for('authsub');
	$b = new buzz(
		environment( 'googleKey' ),
		environment( 'googleSecret' ),
		$callback
	);
//    $b->authorize_from_access( $_SESSION['atoken'], $_SESSION['asecret'] );
	$b->authorize_from_access( $gkey, $gsec );
  $result = $b->update( $rec->title );

   if (!class_exists('Services_JSON'));
	  lib_include('json');
	$json = new Services_JSON();
	$data = (array)$json->decode($result);
	$bzid = $data['data']->id;
	$Like =& $db->model('Like');
   $l = $Like->find_by(array('post_id'=>$rec->id));
   if (!$l->exists){
    $l = $Like->base();
    $l->set_value('post_id',$rec->id);
   }
   $l->set_value('bz_post_id',$bzid);
   $l->save_changes();
}

