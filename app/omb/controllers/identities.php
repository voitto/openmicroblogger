<?php



function validate_identities_photo( $value ) {
  if (!(is_upload('identities','photo')))
    return true;
  $size = filesize($_FILES['identity']['tmp_name']['photo']);
  if (!$size || $size > 409600) {
    if (file_exists($_FILES['identity']['tmp_name']['photo']))
      unlink($_FILES['identity']['tmp_name']['photo']);
    trigger_error( "That photo is too big. Please find one that is smaller than 400K.", E_USER_ERROR );
  }
  if (!is_jpg($_FILES['identity']['tmp_name']['photo']))
    trigger_error( "Sorry for the trouble, but your photo must be a JPG file.", E_USER_ERROR );
  return true;
}


before_filter('resize_uploaded_jpg','pre_insert');
before_filter('resize_uploaded_jpg','pre_update');

function resize_uploaded_jpg( &$rec, &$db ) {
  
  if (!(is_upload('identities','photo')))
    return;
  
  $orig = $rec->attributes['photo'];
  $newthumb = tempnam( "/tmp", "new".$rec->id.".jpg" );
  photoCreateCropThumb( $newthumb, $orig, 96 );
  $rec->attributes['photo'] = $newthumb;
  
}


function validate_identities_url( $value ) {
  
  global $db;
  
  wp_plugin_include(array(
    'wp-openid'
  ));
  
  $logic = new WordPressOpenID_Logic(null);
  
  $logic->activate_plugin();
  
  if ( !WordPressOpenID_Logic::late_bind() )
    trigger_error( 'Sorry, there was an error in the OpenID plugin.', E_USER_ERROR);
  
  $consumer = WordPressOpenID_Logic::getConsumer();
  
  $auth_request = $consumer->begin( $value );
  
  if ( null === $auth_request )
    trigger_error('Sorry, an OpenID server could not be located from: '.htmlentities( $value ), E_USER_ERROR);
  
  return true;
  
}


function validate_identities_nickname( $nick ) {
  
  if (!ereg("^([a-zA-Z0-9]+)$", $nick))
    trigger_error('Sorry, the username can\'t have numbers, spaces, punctuation, etc.', E_USER_ERROR);
    
  return true;
  
}


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


function post( &$vars ) {
  extract( $vars );
  $a = trim( $request->params['identity']['email_value'] );
  $i = $Identity->find_by( 'email_value', $a );
  if (is_email($a) && $i)
    trigger_error( 'Sorry, the e-mail address already exists.', E_USER_ERROR );
  $p = $Person->base();
  $p->save();
  if (empty($request->params['identity']['url']))
  $request->params['identity']['url'] = $a;
  $request->params['identity']['token'] = make_token($p->id);
  $request->params['identity']['person_id'] = $p->id;
  $resource->insert_from_post( $request );
  $i = $Identity->find( $request->id );
  $i->set_etag();
  
  $installed = environment('installed');
  
  if (is_array($installed)) {
    
    foreach($installed as $appname) {
      
      $app = $Setting->base();
      $app->set_value('profile_id',$i->id);
      $app->set_value('person_id',$p->id);
      $app->set_value('name','app');
      $app->set_value('value',$appname);
      $app->save_changes();
      $app->set_etag();
      
    }
    
  }
  
  header_status( '201 Created' );
  redirect_to( $request->resource );
}


function put( &$vars ) {
  extract( $vars );
  
  $nick = strtolower($request->params['identity']['nickname']);
  
  $request->set_param( array( 'identity', 'nickname' ), $nick );
  
  if ($profile->nickname == $nick) {
    // nickname did not change
  } else {
    // if post_notice is set it's a remote user and can share a nickname with a local user
    $sql = "SELECT nickname FROM identities WHERE nickname LIKE '".$db->escape_string($nick)."' AND (post_notice = '' OR post_notice IS NULL)";
    $result = $db->get_result( $sql );
    if ($db->num_rows($result) > 0)
      trigger_error( 'Sorry, that nickname is already being used.', E_USER_ERROR );
  }
  
  if (strpos($request->params['identity']['url'], 'http') === false)
    $request->params['identity']['url'] = 'http://'.$request->params['identity']['url'];
  
  $resource->update_from_post( $request );
  
  $rec = $Identity->find($request->id);
  
  $sql = "SELECT photo FROM identities WHERE id = ".$db->escape_string($request->id);
  $result = $db->get_result($sql);
  
  if ($blobval = $db->result_value($result,0,"photo"))
    $rec->set_value( 'avatar',  $request->url_for(array('resource'=>"_".$rec->id)) . ".jpg" );
  else
    $rec->set_value( 'avatar',  '' );
  
  $rec->set_value( 'profile', $request->url_for(array('resource'=>"_".$rec->id)));
  $rec->set_value( 'profile_url', $request->url_for(array('resource'=>"".$rec->nickname)));
  $rec->save_changes();
  
  broadcast_omb_profile_update();
  
  header_status( '200 OK' );
  redirect_to( $request->url_for( array(
    'resource'=>'posts',
    'byid'=>$rec->id,
    'page'=>1
  )));
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
  $theme = environment('theme');
  $blocks = environment('blocks');
  $atomfeed = $request->feed_url();
  return vars(
    array(
      &$blocks,
      &$profile,
      &$collection,
      &$atomfeed,
      &$theme
    ),
    get_defined_vars()
  );
}


function _profile( &$vars ) {
  // index controller returns
  // a Collection of recent entries
  extract( $vars );
  //echo $Identity->get_query(); exit;
  return vars(
    array( &$collection, &$profile,&$Identity ),
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


function _entry( &$vars ) {
  // entry controller returns
  // a Collection w/ 1 member entry
  extract( $vars );
  $Member = $collection->MoveFirst();
  $Entry = $Member->FirstChild( 'entries' );
  $installed_apps = array();
  while ($s = $Member->NextChild('settings')) {
    if ($s->name == 'app')
      $installed_apps[] = $s->value; 
  }
  return vars(
    array( &$collection, &$Member, &$Entry, &$profile, &$Identity, &$Subscription, &$installed_apps ),
    get_defined_vars()
  );
}


function _new( &$vars ) {
  extract( $vars );
  $model =& $db->get_table( $request->resource );
  $Member = $model->base();
  $identity_tz_options = array(
    'PST',
    'MST',
    'CST',
    'EST'
  );
  
  return vars(
    array( &$Member, &$profile, &$identity_tz_options ),
    get_defined_vars()
  );
}


function _edit( &$vars ) {
  extract( $vars );
  $Member = $collection->MoveFirst();
  $Entry = $Member->FirstChild( 'entries' );
  $identity_tz_options = array(
    'PST',
    'MST',
    'CST',
    'EST'
  );
  return vars(
    array( &$Member, &$Entry, &$profile, &$identity_tz_options ),
    get_defined_vars()
  );
}

function _admin( &$vars ) {
  extract($vars);
  global $submenu,$current_user;
  trigger_before( 'admin_menu', $current_user, $current_user );
  $menuitems = array();
  $apps_list = array();
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
    )).'/partial'] = 'Profile';
  $menuitems[$request->url_for(array(
    'resource'=>'identities',
    'id'=>get_profile_id(),
    'action'=>'subs'
    )).'/partial'] = 'Friends';
  $menuitems[$request->url_for(array(
    'resource'=>'identities',
    'id'=>get_profile_id(),
    'action'=>'apps'
    )).'/partial'] = 'Apps';
  foreach ($submenu as $arr) {
    if (in_array($arr[0][0],$apps_list))
      $menuitems[$arr[0][4]] = $arr[0][3];
  }
  return vars(
    array(&$menuitems),
    get_defined_vars()
  );
}


function _subs( &$vars ) {
  // entry controller returns
  // a Collection w/ 1 member entry
  extract( $vars );
  $Member = $collection->MoveFirst();
  $Entry = $Member->FirstChild( 'entries' );
  return vars(
    array( &$collection, &$Member, &$Entry, &$profile, &$Identity, &$Subscription, &$installed_apps ),
    get_defined_vars()
  );
}


function _apps( &$vars ) {
  // entry controller returns
  // a Collection w/ 1 member entry
  extract( $vars );
  $Member = $collection->MoveFirst();
  $Entry = $Member->FirstChild( 'entries' );
  $curl = curl_init("http://openappstore.com/?apps/show/partial");
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
  curl_setopt($curl, CURLOPT_HEADER, false);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  $result = curl_exec( $curl );
  $store = "";
  if ($result) {
    $store = $result;
  }
  //curl_close( $curl ); 
  
  return vars(
    array( &$store,&$collection, &$Member, &$Entry, &$profile, &$Identity, &$Subscription, &$installed_apps ),
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

function app_installer_json( &$vars ) {
  extract($vars);
  if (!(class_exists('Services_JSON')))
    lib_include( 'json' );
  $json = new Services_JSON();
  $apps_list = array();
  
  if (isset($GLOBALS['PATH']['apps']))
    foreach($GLOBALS['PATH']['apps'] as $k=>$v)
      if ($k != 'omb')
        $apps_list[$k] = $k;
  
  // apps_list = physical apps on this host
  
  $sources = environment('remote_sources');
  $remote_list = array();
  
  // remote_list = all not-installed apps on remote sources
  
  foreach($sources as $name=>$url) {
    $url = "http://".$url."&p=".urlencode($request->uri);
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $result = false;
    $result = curl_exec( $curl );
    if ($result) {
      $data = unserialize($result);
      foreach($data as $appname=>$appdata) {
        $remote_list[$appname] = $appname;
      }
    }
    curl_close( $curl );  
  }
  
  $i = $Identity->find(get_app_id());
  
  while ($s = $i->NextChild('settings')) {
    if ($s->name == 'app' && in_array($s->value, $apps_list))
      $apps_list = drop_array_element($apps_list,$s->value);
  }
  
  $i = $Identity->find(get_app_id());
  
  while ($s = $i->NextChild('settings')) {
    if ($s->name == 'app' && in_array($s->value, $remote_list))
      $remote_list = drop_array_element($remote_list,$s->value);
  }
  
  $all_apps = array_merge($apps_list,$remote_list);
  
  header( "Content-Type: application/javascript" );
  
  print $json->encode($all_apps);
  
  exit;
}


function installed_apps_json( &$vars ) {
  extract($vars);
  if (!(class_exists('Services_JSON')))
    lib_include( 'json' );
  $json = new Services_JSON();
  $apps_list = array();
  $i = $Identity->find(get_profile_id());
  while ($s = $i->NextChild('settings')){
    if ($s->name == 'app') {
      $s = $Setting->find($s->id);
      $e = $s->FirstChild('entries');
      $apps_list[$e->etag] = $s->value;
    }
  }
  
  header( "Content-Type: application/javascript" );
  
  print $json->encode($apps_list);
  exit;
}

