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
  redirect_to( $request->url_for(array('resource'=>'auctions','id'=>$request->id,'action'=>'entry')));
}


function put( &$vars ) {
  extract( $vars );
  $resource->update_from_post( $request );
  header_status( '200 OK' );
  redirect_to( $request->url_for(array('resource'=>'auctions','id'=>$request->id,'action'=>'entry')));
}


function delete( &$vars ) {
  extract( $vars );
  $resource->delete_from_post( $request );
  header_status( '200 OK' );
  redirect_to( $request->resource );
}


function post_as_notice( &$vars ) {
  
  extract($vars);
  
  $Member = $Auction->find($request->id);
  
  $o = owner_of($Member);
  
  if (!($o->id == get_profile_id()))
    trigger_error('your profile id does not match the owner of the auction', E_USER_ERROR);
  
  $adsrc = '
  
  <div style="background-color:#ddd; padding:15px; margin:15px;">

  <p>'.$Member->headline.'</p>

  <p>'.$Member->body.'</p>

  <ul style="list-style: square; margin-left: 20px; margin-top: 0px;">';

  while ($bullet = $Member->NextChild( "auction_bullets" )) {
    
    $adsrc .= '  <li>'.$bullet->bullet.'</li>';
  
  }

  $adsrc .='</ul>

  <p>
    '.$Member->close.'
  </p>';

  while ($photo = $Member->NextChild("auction_photos")) {
    $adsrc .= '  <img src="'.$request->url_for(array("resource"=>"auction_photos","id"=>$photo->id,"action"=>"photo.jpg")).'" border="0" />';
  }
  
  $adsrc .= '</div>';
  
  $p = $Post->base();
  $p->set_value( 'profile_id', $o->id );
  $p->set_value( 'parent_id', 0 );
  $p->set_value( 'title', $Member->headline );
  $p->set_value( 'body', $adsrc );
  $p->save_changes();
  $p->set_etag();
  
  header_status( '200 OK' );
  redirect_to( $request->base );
  
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

function _list( &$vars ) {
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

function _show( &$vars ) {
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

?>