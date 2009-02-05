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

function put( &$vars ) {
  extract( $vars );
  $rec = $Category->find( $request->id );
  $rec->set_value('name',$request->params['category']['name']);
  $rec->set_value('term',$request->params['category']['term']);
  if (isset($request->params['category']['scheme']))
    $rec->set_value('scheme',$request->params['category']['scheme']);
  $rec->save_changes();
  //$Category->update_from_post( $request );
  header( 'Status: 200 OK' );
  redirect_to( array('resource'=>'categories','action'=>'manage') );
}

function post( &$vars ) {
  extract( $vars );
  $Category->insert_from_post( $request );
  header( 'Status: 201 Created' );
  redirect_to( 'categories' );
}

function delete( &$vars ) {
  extract( $vars );
  
  //$Category->delete_from_post( $request );
  
  $rec = $Category->find( $request->id );
  
  $result = $db->delete_record($rec);
  
  header( 'Status: 200 OK' );
  redirect_to( 'categories' );
}

function index( &$vars ) {
  extract( $vars );
  $theme = environment('theme');
  $atomfeed = $request->feed_url();
  return vars(
    array(
      &$Category,
      &$profile,
      &$atomfeed,
      &$collection,
      &$theme
    ),
    get_defined_vars()
  );
}


function _manage( &$vars ) {

  extract( $vars );
  return vars(
    array(
      &$Entry,
      &$collection
    ),
    get_defined_vars()
  );

}

function _index( &$vars ) {

  extract( $vars );
  return vars(
    array(
      &$Entry,
      &$collection
    ),
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


function _remove( &$vars ) {
  extract( $vars );
  $Member = $collection->MoveFirst();
  $Entry = $Member->FirstChild( 'entries' );
  return vars(
    array( &$Member, &$Entry, &$profile ),
    get_defined_vars()
  );
}


function _new( &$vars ) {

  // bring controller vars into scope
  extract( $vars );

  if ( $request->error )
    $Category = session_restore( $db->models['categories'] );
  else
    $Category = $Category->find( $request->id );


  return vars(
    array(

      // return vars to the _new partial
      &$Category,
    ),
    get_defined_vars()
  );

}



function _edit( &$vars ) {

  // bring controller vars into scope
  extract( $vars );

  if ( $request->error )
    $Category = session_restore( $db->models['categories'] );
  else
    $Category = $Category->find( $request->id );

  $Entry = $Entry->find_by( array('resource'=>'categories', 'record_id'=>$Category->id), $Category->id );

  return vars(
    array(

      // return vars to the _edit partial
      &$Category,
      &$Entry

    ),
    get_defined_vars()
  );

}


function _entry( &$vars ) {
  
  // bring controller vars into scope
  extract( $vars );
  
  $Category = $Category->find( $request->id );
  
  if (!$Category)
    trigger_error( "Sorry, I could not find that entry in categories.", E_USER_ERROR );
  
  $Category->set_etag();
  
  $Entry = $Entry->find_by( array('resource'=>'categories', 'record_id'=>$Category->id), $Category->id );
  
  return vars(
    array(
  
      // return vars to the _entry partial
      &$Category,
      &$Entry
  
    ),
    get_defined_vars()
  );
  
}

