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

  $twittercmd = handle_twitter_cmdline($request);

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
    $title = substr($rec->title,0,140);
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


  if ($request->client_wants == 'rss'){
	  $request->set_param('action','api_statuses_public_timeline_rss');
	  $response->render($request);
	  exit;
  }


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
