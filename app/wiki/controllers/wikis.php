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
  $resource->insert_from_post( $request );
  header_status( '201 Created' );
  redirect_to( $request->resource );
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

function _pagelist( &$vars ) {

  extract( $vars );
  $Member = $collection->MoveFirst();
  $blogprefix = $Member->prefix;
  $find_by = array(
    'parent_id'=>$request->id
  );
  
  $collection = new Collection( 'wiki_pages', $find_by );

  return vars(
    array( &$collection, &$profile, &$blogprefix ),
    get_defined_vars()
  );
}


function _entry( &$vars ) {
  // entry controller returns
  // a Collection w/ 1 member entry
  extract( $vars );
  $Member = $collection->MoveNext();
  $Entry = $Member->FirstChild( 'entries' );
  $blogprefix = $Member->prefix;

  return vars(
    array( &$collection, &$Member, &$Entry, &$profile, &$blogprefix ),
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

function lookup_wiki_nickname($wiki_id){
	global $db;
  $sql = "SELECT blog_id FROM wikis WHERE id = $wiki_id";
  $result = $db->get_result( $sql );
  if ( $db->num_rows($result) == 1 ) {
    $blog_id = $db->result_value( $result, 0, "blog_id" );
  } else {
		$Wiki =& $db->model('Wiki');
	  $w = $Wiki->find($wiki_id);
    $blog_id = $w->blog_id;
  }
  $Blog =& $db->model('Blog');
  $b = $Blog->find($blog_id);
  $blognick = $b->nickname;
  return $blognick;
}
