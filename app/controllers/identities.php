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
  if (!in_string("JpG",$_FILES['identity']['name']['photo'],1))
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
  $rec->save_changes();
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
  return vars(
    array( &$collection, &$Member, &$Entry, &$profile, &$Identity, &$Subscription ),
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


function _remove( &$vars ) {
  extract( $vars );
  $Member = $collection->MoveFirst();
  $Entry = $Member->FirstChild( 'entries' );
  return vars(
    array( &$Member, &$Entry, &$profile ),
    get_defined_vars()
  );
}


function test_get( &$vars ) {
  
  # get( array( 'index', 'id' => $collection->id ));
  # assert_response( 200 );
  # assert_template( 'index' );
  # assert_equal( get(), assigns( $collection ) );
  
}


?>