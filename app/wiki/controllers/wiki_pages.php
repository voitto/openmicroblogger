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
  $request->set_param( array( 'wikipage', 'header' ), 'click to edit header...' );
  $request->set_param( array( 'wikipage', 'body' ), 'click to edit body...' );
  $request->set_param( array( 'wikipage', 'footer' ), 'click to edit footer...' );
  $resource->insert_from_post( $request );
  header_status( '201 Created' );
  redirect_to( array('resource'=>'wikis','id'=>$request->params['wikipage']['parent_id']) );
}


function put( &$vars ) {
  extract( $vars );


  $resource->update_from_post( $request, true );
  header_status( '200 OK' );
  redirect_to( array('resource'=>'wikis','id'=>$request->params['id']) );
}


function delete( &$vars ) {
  extract( $vars );
  $resource->delete_from_post( $request, true );
  header_status( '200 OK' );
  redirect_to( 'wikis' );
}


function _doctype( &$vars ) {
  // doctype controller
}



function index( &$vars ) {
  extract( $vars );
  $Member = $collection->MoveFirst();
  $theme = environment('theme');
  $blocks = environment('blocks');
  $atomfeed = $request->feed_url();
  if ($Member){
	  $wiki_rss = blog_url(lookup_wiki_nickname($Member->parent_id));
	  $wiki_title = lookup_wiki_title($Member->parent_id);
	
} else {
	$wiki_rss = '';
  $wiki_title = '';
  
}
  return vars(
    array(
	    &$wiki_rss,
	    &$wiki_title,
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

function _revisions( &$vars ) {
  extract($vars);

  $Member = $collection->MoveFirst();
  $Entry = $Member->FirstChild( 'entries' );

  $Revision =& $db->model('Revision');
  $Revision->set_limit(1000);
  $Revision->unset_relation('entries');
  $Revision->has_one('target_id:entries.id');
  $where = array(
    'entries.resource'=>'wiki_pages'
  );
  $Revision->set_param( 'find_by', $where );
  $Revision->find();
  $versions = array();
  while ($r = $Revision->MoveNext()) {
    $wp = mb_unserialize($r->data);
    if (is_object($wp)){
	    if ($wp->id == $Member->id) {
        $revisor = get_profile($r->profile_id);
				$versions[] = array(
					'avatar'=>$revisor->avatar,
					'link'=>$revisor->profile_url,
					'name'=>$revisor->fullname,
					'nickname'=>$revisor->nickname,
					'date'=>$r->created
				);
    	}
    }
  }

  extract( $vars );
  return vars(
    array( &$collection, &$profile, &$versions ),
    get_defined_vars()
  );
}


function _entry( &$vars ) {
  // entry controller returns
  // a Collection w/ 1 member entry
  extract( $vars );
  $Member = $collection->MoveNext();
  $Entry = $Member->FirstChild( 'entries' );
  $Wiki =& $db->model('Wiki');
  $w = $Wiki->find($Member->parent_id);
  $Blog =& $db->model('Blog');
  $b = $Blog->find($w->blog_id);
  $blognick = $b->nickname;
  $blogprefix = $b->prefix."_";
  return vars(
    array( &$collection, &$Member, &$Entry, &$profile, &$blognick, &$blogprefix ),
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


function lookup_wiki_prefix($wiki_id){
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
  $blogprefix = $b->prefix."_";
  return $blogprefix;
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

function lookup_wiki_title($wiki_id){
	global $db;
  $sql = "SELECT title FROM wikis WHERE id = $wiki_id";
  $result = $db->get_result( $sql );
  if ( $db->num_rows($result) == 1 ) {
    return $db->result_value( $result, 0, "title" );
  } else {
		$Wiki =& $db->model('Wiki');
	  $w = $Wiki->find($wiki_id);
    return $w->title;
  }
  return false;
}
