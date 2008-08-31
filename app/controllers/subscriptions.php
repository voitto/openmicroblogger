<?php

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


function delete( &$vars ) {
  extract( $vars );
  $resource->delete_from_post( $request );
  header_status( '200 OK' );
  redirect_to($request->url_for(array(
        'resource'=>'identities',
        'id'=>$profile->id,
        'action'=>'edit' )));
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


?>