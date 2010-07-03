<?php

function get( &$vars ) {
  extract( $vars );
  switch ( count( $collection->members )) {
    case ( 1 ) :
      if ($request->id && $request->entry_url())
        render( 'action', 'entry' );
    default :
      render( 'action', 'index' );
  }
}

function handle_tweetiepic(&$request){
	$cmd = '#fb!';
  if (strstr($request->params['post']['title'], $cmd )){
	  global $optiondata;
	  $request->set_param(array('post','title'),str_replace($cmd,'',$request->params['post']['title']));
	  $optiondata['facebook_status'] = 'enabled';
	  $optiondata['twitter_status'] = 'disabled';
  }
	$cmd = '#fb';
  if (strstr($request->params['post']['title'], $cmd )){
	  global $optiondata;
	  $request->set_param(array('post','title'),str_replace($cmd,'',$request->params['post']['title']));
	  $optiondata['facebook_status'] = 'enabled';
  }
}


function handle_twitter_cmdline(&$request){
  $commands = array(
	  'follow ',
	  'unfollow ',
	  'addtolist '
	);
  $parts = explode(" ",$request->params['post']['title']);
  $c = $parts[0]." ";
  $result = false;
  if (in_array($c,$commands)){
	  $c = trim($c)."_cmdfunc";
	  if (function_exists($c))
 	    $result = $c($parts);
  }
  return $result;
}

function follow_cmdfunc($parts){
	if (isset($parts[1])){
	  $to = get_twitter_oauth();
    if ($to){
		  $content = $to->OAuthRequest('https://twitter.com/friendships/create/'.$parts[1].'.xml', array(), 'POST');
	    return true;
    }
	}
	return false;
}

function unfollow_cmdfunc($parts){
	if (isset($parts[1])){
	  $to = get_twitter_oauth();
    if ($to){
		  $content = $to->OAuthRequest('https://twitter.com/friendships/destroy/'.$parts[1].'.xml', array(), 'POST');
	    return true;
    }
	}
	return false;
}

function addtolist_cmdfunc($parts){
	if (isset($parts[1])&&isset($parts[2])){
	  $to = get_twitter_oauth();
    if ($to){
		  $content = $to->OAuthRequest('https://twitter.com/users/show/'.$parts[1].'.json', array(), 'GET');
		  if (!(class_exists('Services_JSON')))
		    lib_include('json');
			$json = new Services_JSON();
			$data = $json->decode($content);
			if ($content && $data && $json)
  		  $content = $to->OAuthRequest('https://api.twitter.com/1/'.get_twitter_screen_name().'/'.$parts[2].'/members.xml', array('id'=>$data->id), 'POST');
      if (isset($parts[3]))
	      if ($parts[3] == '-u')
				  $content = $to->OAuthRequest('https://twitter.com/friendships/destroy/'.$parts[1].'.xml', array(), 'POST');
	    return true;
    }
	}
	return false;
}

function post( &$vars ) {
  extract( $vars );
  global $request;


if (isset($request->likedurl)) {
	
	$Like =& $db->model('Like');
  if (!$Like->exists)
    $Like->save();
	
	$Post =& $db->model('Post');
	$shorturl = $request->likedurl;
	$Post->has_one('like');
  $Post->set_order('asc');
	$Post->find_by(array(
		'eq'=>'like',
		'title'=>"%$shorturl%",
		1=>array(
		  'eq'=>'>'
		),
		2=>array(
			'likes.post_id' => 0
		)
	));


	if ($Post->rowcount() > 0){

	  $likedpost = $Post->MoveFirst();
		$Like =& $db->model('Like');

	  $l = $Like->find_by(array('post_id'=>$likedpost->id));

if (!$l) exit;

    $bzid = $l->bz_post_id;
    $fbid = $l->fb_post_id;
    $twid = $l->tw_post_id;

	  extract($vars);	

if (!class_exists('Facebook'))
	  foreach( array('helper','twitter','facebook') as $module )
		  require_once $GLOBALS['PATH']['dbscript'] . $module . '.php';

	  $next = $request->base;

	  $fbkey = environment('facebookKey');
	  $fbsec = environment('facebookSecret');
	  $appid = environment('facebookAppId');
	  $agent = environment('facebookAppName');

if (!class_exists('FacebookStream')){
	  add_include_path(library_path().'facebook_stream');
	  require_once "Services/Facebook.php";
}

$FacebookUser =& $db->model('FacebookUser');
$FacebookUser->has_one('profile_id:identities');
$fbu = $FacebookUser->find_by(array(
	'identities.person_id'=>get_person_id()
));


if ($fbu){
	$sess = $fbu->oauth_key;
  $f = new Facebook(
	  $fbkey,
	  $fbsec,
	  $appid,
	  $agent,
	  $sess,
	  $next
	);

	if ($l->exists && $l->fb_post_id && isset($_POST['face_it'])){

	  $f->like( $l->fb_post_id,$fbu->facebook_id );	

	}

}

		if ($l->exists && isset($_POST['buzz_it']) && $l->bz_post_id){

			$sql = "SELECT value FROM ".$prefix."settings WHERE person_id = '".get_person_id()."' AND name = 'google_key'";
			$result = $db->get_result( $sql );
			$gkey = $db->result_value( $result, 0, "value" );
			$sql = "SELECT value FROM ".$prefix."settings WHERE person_id = '".get_person_id()."' AND name = 'google_secret'";
			$result = $db->get_result( $sql );
			$gsec = $db->result_value( $result, 0, "value" );



			if (!class_exists('Helper'))
					db_include('helper');

			if (!class_exists('Buzz'))
					db_include('buzz');

			if (!class_exists('TwitterOAuth'));
			  lib_include('twitteroauth');
		
		  $callback = $request->url_for('authsub');

			$b = new buzz(
				environment( 'googleKey' ),
				environment( 'googleSecret' ),
				$callback
			);

			$b->authorize_from_access( $gkey, $gsec );

		  $result = $b->like( $l->bz_post_id );

	  }

	} else {
		exit;
	}

}




  $twittercmd = handle_twitter_cmdline($request);
  $twittercmd = handle_tweetiepic($request);


  if ($twittercmd)
    redirect_to($request->base);

  $modelvar = classify($request->resource);
  trigger_before( 'insert_from_post', $$modelvar, $request );
  $table = $request->resource;
  $content_type = 'text/html';
  $rec = $$modelvar->base();
  if (!($$modelvar->can_create( $table )))
    trigger_error( "Sorry, you do not have permission to " . $request->action . " " . $table, E_USER_ERROR );
  $fields = $$modelvar->fields_from_request($request);
  $fieldlist = $fields[$table];
  foreach ( $fieldlist as $field=>$type ) {
    if ($$modelvar->has_metadata && is_blob($table.'.'.$field)) {
      if (isset($_FILES[strtolower(classify($table))]['name'][$field]))
        $content_type = type_of( $_FILES[strtolower(classify($table))]['name'][$field] );
    }
    $rec->set_value( $field, $request->params[strtolower(classify($table))][$field] );
  }
  $rec->set_value('profile_id',get_profile_id());
  $result = $rec->save_changes();
  if ( !$result )
    trigger_error( "The record could not be saved into the database.", E_USER_ERROR );
  $atomentry = $$modelvar->set_metadata($rec,$content_type,$table,'id');
  $$modelvar->set_categories($rec,$request,$atomentry);
  if ((is_upload($table,'attachment'))) {
    
    $upload_types = environment('upload_types');
    
    if (!$upload_types)
      $upload_types = array('jpg','jpeg','png','gif');
    
    $ext = extension_for( type_of($_FILES[strtolower(classify($table))]['name']['attachment']));
    
    if (!(in_array($ext,$upload_types)))
      trigger_error('Sorry, this site only allows the following file types: '.implode(',',$upload_types), E_USER_ERROR);
    
    $url = $request->url_for(array(
      'resource'=>$table,
      'id'=>$rec->id
    ));
    $title = html_entity_decode(substr($rec->title,0,140));

    $over = ((strlen($title) + strlen($url) + 1) - 140);
    if ($over > 0)
      $rec->set_value('title',substr($title,0,-$over)." ".$url);
    else
      $rec->set_value('title',$title." ".$url);
    $rec->save_changes();
    
    $tmp = $_FILES[strtolower(classify($table))]['tmp_name']['attachment'];
    
    if (is_jpg($tmp)) {
      $thumbsize = environment('max_pixels');
      $Thumbnail =& $db->model('Thumbnail');
      $t = $Thumbnail->base();
      $newthumb = tempnam( "/tmp", "new".$rec->id.".jpg" );
      resize_jpeg($tmp,$newthumb,$thumbsize);
      $t->set_value('target_id',$atomentry->id);
      $t->save_changes();
      update_uploadsfile( 'thumbnails', $t->id, $newthumb );
      $t->set_etag();
    }
    
  }
  
  trigger_after( 'insert_from_post', $$modelvar, $rec );
  header_status( '201 Created' );
  redirect_to( $request->base );
  
}


function put( &$vars ) {
  extract( $vars );
  $resource->update_from_post( $request );
  header_status( '200 OK' );
  redirect_to( $request->resource );
}


function delete( &$vars ) {
  extract( $vars );
  $resource->delete_from_post( $request );
  header_status( '200 OK' );
  redirect_to( $request->resource );
}


function _doctype( &$vars ) {
  // doctype controller
}



function index( &$vars ) {
  extract( $vars );


if (signed_in()){

//  $stream = get_option('tweetiepic_stream',get_profile_id());

$Setting =& $db->model('Setting');
  $stream = $Setting->find_by(array('name'=>'tweetiepic_stream','person_id'=>get_person_id()));




if ($stream){
  $Blog =& $db->model('Blog');
  $b = $Blog->find_by('prefix',$stream);
  $blognick = $b->nickname;
  $blogprefix = $b->prefix;
}

  if (!$stream){
    $nickbase = '';
    $rec = $Blog->base();
    $letters = str_split(strtolower($profile->nickname));
 		foreach ($letters as $letter)
		  if (ereg("([a-z])", $letter))
		    $nickbase .= $letter;
		$prefix = substr($nickname,0,2);
    $nickname = $nickbase;
	  for ( $j=1; $j<5000; $j++ ) {
	    $sql = "SELECT nickname FROM blogs WHERE nickname LIKE '".$nickname."'";
	    $result = $db->get_result( $sql );
	    if ($db->num_rows($result) > 0) {
	      $nickname = $nickbase.$j;
	    } else {
	      break;
	    }
	  }
	  if ($db->num_rows($result) > 0)
	    trigger_error('sorry I could not create a stream for you', E_USER_ERROR);


		for ($i=0;$i<5000;$i++) {
		  $b = $Blog->find_by('prefix',$prefix);
		  if (!$b && !in_array($prefix."_db_sessions",$db->tables) && strlen($prefix) > 1)
		    break;
		  else
  	    $prefix = randomstring(2);
		}
		if ($b)
	    trigger_error('sorry I could not create a stream for you', E_USER_ERROR);
		$rec->set_value('prefix',$prefix);

		if (empty($nickname))
	  	$rec->set_value('nickname',$prefix);
		else
  		$rec->set_value('nickname',$nickname);

		$rec->save_changes();
		$rec->set_etag();

	  if ($rec->id)
      update_option('tweetiepic_stream',$prefix);
    else
	    trigger_error('sorry I could not create a ddstream for you', E_USER_ERROR);
	
	  setup_new_tweetiepic($rec);
	  $blognick = $rec->nickname;
	  $blogprefix = $rec->prefix;
  }
}

  if ($request->client_wants == 'rss'){
	  $request->set_param('action','api_statuses_public_timeline_rss');
	  $response->render($request);
	  exit;
  }
/*

	$app_id = environment('facebookAppId');
  $consumer_key = environment('facebookKey');
  $consumer_secret = environment('facebookSecret');
  $agent = environment('facebookAppName')." (curl)";
	$next = $request->base;

	add_include_path(library_path().'facebook_stream');
	require_once "Services/Facebook.php";
	db_include('helper');
	db_include('facebook');

  global $prefix;
  $prefix = $b->prefix."_";
  $db->prefix = $prefix;
	
	$Setting =& $db->model('Setting');

  $stat = $Setting->find_by(array('name'=>'facebook_uid','person_id'=>get_person_id()));

  $prefix = $b->prefix."_";
  $db->prefix = $prefix;

if ($stat->exists){
	$f = new Facebook(
	  $consumer_key,
	  $consumer_secret,
	  $appid,
	  $agent,
	  $sesskey,
	  $next
	);
 // $f->permission_to( 'publish_stream', $stat->value );

}
 */
 $theme = environment('theme');
  $blocks = environment('blocks');
  $atomfeed = $request->feed_url();
  return vars(
    array(
&$blogprefix,
	   &$blognick,
      &$blocks,
      &$profile,
      &$collection,
      &$atomfeed,
      &$theme
    ),
    get_defined_vars()
  );
}

function setpass(&$vars){
	 set_my_tweetiepic_pass();
}

function _profile( &$vars ) {
  // entry controller returns
  // a Collection w/ 1 member entry
  extract( $vars );
  $Identity =& $db->model('Identity');
  if (!isset($request->byid))
    $request->set_param('byid',get_profile_id());
  $Member = $Identity->find($request->byid);
  $Entry = $Member->FirstChild( 'entries' );
  $installed_apps = array();
  $Subscription->set_limit(10);
  return vars(
    array( &$collection, &$Member, &$Entry, &$profile, &$Identity, &$Subscription, &$installed_apps ),
    get_defined_vars()
  );
}


function _replies( &$vars ) {
  // index controller returns
  // a Collection of recent entries
  extract( $vars );
  return vars(
    array( &$collection, &$profile ),
    get_defined_vars()
  );
}

function _index( &$vars ) {
  // index controller returns
  // a Collection of recent entries
  extract( $vars );
  return vars(
    array( &$collection, &$profile ),
    get_defined_vars()
  );
}

function _like( &$vars ) {
  // entry controller returns
  // a Collection w/ 1 member entry
  extract( $vars );

  $Member = $collection->MoveNext();
  $Entry = $Member->FirstChild( 'entries' );

  $fblogin = $request->url_for('facebook_login');
  $twlogin = $request->url_for('oauth_login');



	$fbchecked = "";
	$twchecked = "";
	$fbuid = 0;
	if (signed_in() && has_facebook_account()){
		$fbchecked = " checked";
	  $fbuid = $_SESSION['fb_userid'];
	}
	if (signed_in() && has_twitter_account())
		$twchecked = " checked";

  $fbkey = environment('facebookKey');
  $fbsec = environment('facebookSecret');
  $appid = environment('facebookAppId');
  $agent = environment('facebookAppName');

  add_include_path(library_path());
  add_include_path(library_path().'facebook-platform/php');
  add_include_path(library_path().'facebook_stream');
  require_once "FacebookStream.php";
  require_once "Services/Facebook.php";


	$fs = new FacebookStream($fbkey,$fbsec,$agent,$appid);
	
	$post_text = '';
	if ($request->params['id'] > 0){
		$Post =& $db->model('Post');
		$p = $Post->find($request->params['id']);
		$e =& $p->FirstChild('entries');
		if ($p->exists && $e->exists){
			$sql = "SELECT screen_name FROM identities,twitter_users WHERE twitter_users.profile_id = identities.id and identities.person_id = ".$e->person_id;
		  $result = $db->get_result( $sql );
		  if ($db->num_rows($result) == 1) {
			  $post_text = "RT @".$db->result_value($result,0,'screen_name').' '.$p->title;
			}
		}
	}
	
  return vars(
    array( &$collection, &$Member, &$Entry, &$profile, &$fbchecked,&$twchecked,&$fs,&$fbuid, &$post_text, &$fblogin,&$twlogin ),
    get_defined_vars()
  );

}

function like( &$vars ) {


  extract( $vars );

  $url = urldecode($_GET['url']);
if (!function_exists('shortener_init'))
  app_init('shortener');
  shortener_init();
if (!class_exists('Services_JSON'))
  lib_include( 'json' );
	global $wp_ozh_yourls;
	if (!$wp_ozh_yourls)
		wp_ozh_yourls_admin_init();
	$service = wp_ozh_yourls_service();
	if (empty($service)) {
    add_option('ozh_yourls',array(
      'service'=>'other',
      'location'=>'',
      'yourls_path'=>'',
      'yourls_url'=>'',
      'yourls_login'=>'',
      'yourls_password'=>'',
      'rply_login'=>'',
      'rply_password'=>'',
      'other'=>'rply'
    ));
  	global $wp_ozh_yourls;
  	if (!$wp_ozh_yourls)
  		wp_ozh_yourls_admin_init();
  	$service = wp_ozh_yourls_service();
  }
	$shorturl = wp_ozh_yourls_api_call( wp_ozh_yourls_service(), $url);


  if (!$shorturl)
    exit;


  if ($request->client_wants == 'rss'){
	  $request->set_param('action','api_statuses_public_timeline_rss');
	  $response->render($request);
	  exit;
  }

  $theme = environment('theme');
  $blocks = environment('blocks');
  $atomfeed = $request->feed_url();
  foreach( array('helper','twitter','facebook') as $module )
	  require_once $GLOBALS['PATH']['dbscript'] . $module . '.php';


  $twitter = new TwitterHelper();

  $facebook = new FacebookHelper();

  $next = $request->uri;

 
  $xd = 'resource/xd_receiver.htm';

  $twkey = environment('twitterKey');

  $fbkey = environment('facebookKey');
  $fbsec = environment('facebookSecret');
  $appid = environment('facebookAppId');
  $agent = environment('facebookAppName');

  $fblogin = $request->url_for('facebook_login');
  $twlogin = $request->url_for('oauth_login');
  $bzlogin = $request->url_for('authsub');

	$fbchecked = "";
	$twchecked = "";
	$bzchecked = "";

	$fbuid = 0;
	if (signed_in() && has_facebook_account()){
		$fbchecked = " checked";
	  $fbuid = $_SESSION['fb_userid'];
	}
	if (signed_in() && has_twitter_account())
		$twchecked = " checked";

	if (signed_in() && has_google_account())
		$bzchecked = " checked";


  add_include_path(library_path().'facebook_stream');
  require_once "Services/Facebook.php";

  if (isset($_SESSION['fb_userid']) && !empty($_SESSION['fb_userid'])) {
		global $prefix,$db;
		$db->prefix = $prefix;
		$uid = $_SESSION['fb_userid'];
		$sql = "SELECT DISTINCT oauth_key FROM ".$prefix."facebook_users WHERE facebook_id = ".$uid;
		$result = $db->get_result( $sql );
		if (!(mysql_num_rows($result) == 1)) {
			$sql = "SELECT DISTINCT oauth_key FROM facebook_users WHERE facebook_id = ".$uid;
			$result = $db->get_result( $sql );
		}
			if (!(mysql_num_rows($result) == 1))
		  trigger_error('unable to find facebook user',E_USER_ERROR);
		$sess = $db->result_value($result,0,'oauth_key');
  } else {
    $sess = false;
  }

	
	  $next = $fblogin;

  $f = new Facebook(
	  $fbkey,
	  $fbsec,
	  $appid,
	  $agent,
	  $sess,
	  $next
	);
  $permurl = '';
  if (!$_SESSION['fb_userid']) {
	

		unset($_SESSION['fb_ask_perm'] );
		//$tok = $f->request_token();
		//$fblogin = $tok->authorize_url();
		
  } else {
	  

	  //$f->authorize_from_access();
		  $_SESSION['fb_ask_perm'] = true;

		  $permurl = $f->permission_to('publish_stream',$_SESSION['fb_userid'], true, true);
	//echo $url;
	  $fbuid = $_SESSION['fb_userid'];

  }

$Post =& $db->model('Post');
$Post->set_order('asc');

	$p = $Post->find_by(array('eq'=>'like','title'=>"%$shorturl%"));
if (!$p)
  exit;

$the_post = $Post->MoveFirst();
$e = $the_post->FirstChild('entries');
$postbody = 'RT @'.get_twitter_screen_name($e->person_id).' '.$the_post->title;
$likedurl = $shorturl;
  return vars(
    array( &$bzlogin, &$bzchecked,&$postbody,
 &$fbkey, &$xd, &$next, &$facebook, &$twitter, &$fbuid, &$twkey, &$fblogin, &$twlogin, &$likedurl,
 &$twchecked,
 &$fbchecked,

      &$blocks,
      &$profile,
      &$collection,
      &$atomfeed,
      &$theme,&$permurl
    ),
    get_defined_vars()
  );
}

function config() {

  if (isset($_GET['forward'])){
	  if (!empty($_SERVER['HTTP_REFERER']))
		  $_SESSION['cfg_forward'] = $_SERVER['HTTP_REFERER'];
		if (isset($_GET['callbackurl']))
			$_SESSION['cfg_forward'] = $_GET['callbackurl'];
	} 
  extract( $vars );



  if (isset($_POST['posted'])){
echo "Sorry, this feature is not enabled for you yet."; exit;
	
}

	$fb = false; $bz = false; $tw = false;

  $fb = true;
	if (signed_in() && has_facebook_account())
	  $fb = true;

	if (signed_in() && has_twitter_account())
		$tw = true;

	if (signed_in() && has_google_account())
		$bz = true;

	return vars(array(&$tw,&$bz,&$fb),get_defined_vars());

}

function _config() {
	$foo = '';
	return render(array(&$foo));
}


function _widget( &$vars ) {
  // index controller returns
  // a Collection of recent entries
  extract( $vars );
  return vars(
    array( &$collection, &$profile ),
    get_defined_vars()
  );
}


function _entry( &$vars ) {
  // entry controller returns
  // a Collection w/ 1 member entry
  extract( $vars );
  $Member = $collection->MoveNext();
  $Entry = $Member->FirstChild( 'entries' );
  return vars(
    array( &$collection, &$Member, &$Entry, &$profile ),
    get_defined_vars()
  );
}


function _upload( &$vars ) {
  extract( $vars );
  $model =& $db->get_table( $request->resource );
  $Member = $model->base();
  
  $Post->find();
  $p = $Post->MoveFirst();
  if (!$p) $p = 0;
  $url = $request->url_for(array(
    'resource'=>'posts',
    'id'=>$p->id
  ));
  $url_length = strlen($url);
  
  return vars(
    array( &$Member, &$profile, &$url_length ),
    get_defined_vars()
  );
}





function _new( &$vars ) {
  extract( $vars );
  $model =& $db->get_table( $request->resource );
  $Member = $model->base();
  return vars(
    array( &$Member, &$profile ),
    get_defined_vars()
  );
}


function _edit( &$vars ) {
  extract( $vars );
  $Member = $collection->MoveFirst();
  $Entry = $Member->FirstChild( 'entries' );
  return vars(
    array( &$Member, &$Entry, &$profile ),
    get_defined_vars()
  );
}


function _remove( &$vars ) {
  extract( $vars );
  $Member = $collection->MoveFirst();
  $Entry = $Member->FirstChild( 'entries' );
  return vars(
    array( &$Member, &$Entry, &$profile ),
    get_defined_vars()
  );
}


function _block( &$vars ) {

  extract( $vars );
  return vars(
    array(
      &$Entry,
      &$collection
    ),
    get_defined_vars()
  );

}

function _oembed( &$vars ) {
  
  extract( $vars );
  
  $width = $_GET['maxWidth'];
  $height = $_GET['maxHeight'];
  
  $id = array_pop(split("\/",$_GET['url']));

  $version = '1.0';
  
  $p = $Post->find($id);
  $e = $p->FirstChild('entries');
  $title = $p->title;
  
  $o = owner_of($p);
  
  if (extension_for($e->content_type) == 'mp3') {
    $type = 'rich'; // photo video link rich
    $url = $request->url_for(array(
      'resource'=>'posts',
      'id'=>$id,
      'action'=>'attachment.mp3'
    ));
  } elseif (extension_for($e->content_type) == 'jpg') {
    $type = 'photo';
    $url = $request->url_for(array(
      'resource'=>'posts',
      'id'=>$id,
      'action'=>'preview'
    ));
  } elseif (extension_for($e->content_type) == 'mov') {
    $type = 'video';
    $url = $request->url_for(array(
      'resource'=>'posts',
      'id'=>$id,
      'action'=>'attachment.mov'
    ));
  } elseif (extension_for($e->content_type) == 'avi') {
    $type = 'video';
    $url = $request->url_for(array(
      'resource'=>'posts',
      'id'=>$id,
      'action'=>'attachment.avi'
    ));
  } else {
    exit;
  }
  
  
  $author_name = $o->nickname;
  $author_url = $o->profile;
  $cache_age = 3600;
  $provider_name = "myphotos";
  $provider_url = $request->base;

  $thumbnail_url = 0;
  $thumbnail_width = 0;
  $thumbnail_height = 0;
  

  
  return vars(
    array(
      &$version,
      &$type,
      &$title,
      &$author_name,
      &$author_url,
      &$cache_age,
      &$provider_name,
      &$provider_url,
      &$width,
      &$height,
      &$thumbnail_url,
      &$thumbnail_width,
      &$thumbnail_height,
      &$url
    ),
    get_defined_vars()
  );
  
}



function _apps( &$vars ) {
  extract($vars);
  $Identity =& $db->model('Identity');
  global $submenu,$current_user;
  trigger_before( 'admin_menu', $current_user, $current_user );
  $menuitems = array();
  $apps_list = array();
  global $env;
  if (is_array($env['apps']))
    $apps_list = $env['apps'];
  $i = $Identity->find(get_profile_id());
  while ($s = $i->NextChild('settings')){
    $s = $Setting->find($s->id);
    $e = $s->FirstChild('entries');
    $apps_list[] = $s->value;
  }
  $menuitems[$request->url_for(array(
    'resource'=>'identities',
    'id'=>get_profile_id(),
    'action'=>'edit'
    )).'/partial'] = 'Settings';
  $menuitems[$request->url_for(array(
    'resource'=>'identities',
    'id'=>get_profile_id(),
    'action'=>'subs'
    )).'/partial'] = 'Friends';
  //$menuitems[$request->url_for(array(
  //  'resource'=>'identities',
  //  'id'=>get_profile_id(),
  //  'action'=>'apps'
  //  )).'/partial'] = 'Apps';
  foreach ($submenu as $arr) {
    if (in_array($arr[0][0],$apps_list))
      $menuitems[$arr[0][4]] = $arr[0][3];
  }
  return vars(
    array(&$menuitems),
    get_defined_vars()
  );
}


function preview( &$vars ) {
  extract($vars);
  $model =& $db->get_table( $request->resource );
  $Entry =& $db->model('Entry');
  $p = $model->find($request->id);
  $e = $Entry->find($p->entry_id);
  $t = $Thumbnail->find_by('target_id',$e->id);
  if ($t) {
    $request->set_param('resource','thumbnails');
    $request->set_param('id',$t->id);
    render_blob($t->attachment,extension_for($e->content_type));
  } else {
    render_blob($p->attachment,extension_for($e->content_type));
  }
}

function invitecode( &$vars ){
  extract( $vars );
  $result = $db->get_result( "SELECT name 
                              FROM tokens 
                              WHERE name = '".$_POST['invitecode']."' and active = 0" );
  if ($result && $db->num_rows($result) > 0) {
	  $result = $db->get_result( "UPDATE tokens
		  													SET active = 1 
	                              WHERE name = '".$_POST['invitecode']."'" );

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


  } else {
	  echo 'Sorry, that invite code was not found'; exit; 
  }
  redirect_to($request->base);
}
function _myapps( &$vars ){
  extract( $vars );
  return vars(
    array( &$collection, &$profile ),
    get_defined_vars()
  );
}

function _pagelist( &$vars ) {
  extract( $vars );
  return vars(
    array( &$collection, &$profile ),
    get_defined_vars()
  );
}

function _pagenew( &$vars ) {
  extract( $vars );
  $model =& $db->get_table( $request->resource );
  $Member = $model->base();
  return vars(
    array( &$Member, &$profile ),
    get_defined_vars()
  );
}

function _pagespan( &$vars ) {
  extract( $vars );
  return vars(
    array( &$collection, &$profile ),
    get_defined_vars()
  );
}



