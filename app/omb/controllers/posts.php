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


function post( &$vars ) {
  extract( $vars );
  global $request;
  trigger_before( 'insert_from_post', $Post, $request );
  $table = 'posts';
  $content_type = 'text/html';
  $rec = $Post->base();
  if (!($Post->can_create( $table )))
    trigger_error( "Sorry, you do not have permission to " . $request->action . " " . $table, E_USER_ERROR );
  $fields = $Post->fields_from_request($request);
  $fieldlist = $fields['posts'];
  foreach ( $fieldlist as $field=>$type ) {
    if ($Post->has_metadata && is_blob($table.'.'.$field)) {
      if (isset($_FILES[strtolower(classify($table))]['name'][$field]))
        $content_type = type_of( $_FILES[strtolower(classify($table))]['name'][$field] );
    }
    $rec->set_value( $field, $request->params[strtolower(classify($table))][$field] );
  }
  $rec->set_value('profile_id',get_profile_id());
  $result = $rec->save_changes();
  if ( !$result )
    trigger_error( "The record could not be saved into the database.", E_USER_ERROR );
  $atomentry = $Post->set_metadata($rec,$content_type,$table,'id');
  $Post->set_categories($rec,$request,$atomentry);
  if ((is_upload('posts','attachment'))) {
    $url = $request->url_for(array(
      'resource'=>'posts',
      'id'=>$rec->id
    ));
    $title = substr($rec->title,0,140);
    $over = ((strlen($title) + strlen($url) + 1) - 140);
    if ($over > 0)
      $rec->set_value('title',substr($title,0,-$over)." ".$url);
    else
      $rec->set_value('title',$title." ".$url);
    $rec->save_changes();
  }
  trigger_after( 'insert_from_post', $Post, $rec );
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
  
  $type = 'photo'; // photo video link rich
  
  $p = $Post->find($id);
  $e = $p->FirstChild('entries');
  $title = $p->title;
  
  $o = owner_of($p);
  
  $author_name = $o->nickname;
  $author_url = $o->profile;
  $cache_age = 3600;
  $provider_name = "myphotos";
  $provider_url = $request->base;

  $thumbnail_url = 0;
  $thumbnail_width = 0;
  $thumbnail_height = 0;
  
  $url = $request->url_for(array(
    'resource'=>'posts',
    'id'=>$id,
    'action'=>'attachment.'.extension_for($e->content_type)
  ));
  
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