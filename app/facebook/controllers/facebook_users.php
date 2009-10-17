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




function facebook_oauth_login_test(&$vars) {
  extract($vars);
  $success = false;
  
  if ($success)
  	echo 1;
  else
    echo 0;
  
  exit;
  
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




function _edit( &$vars ) {
  extract( $vars );
  
  if (!(class_exists('Services_JSON')))
    lib_include('json');
  
  $stat = $Setting->find_by(array('name'=>'facebook_status','profile_id'=>get_profile_id()));
  
  if (!$stat) {
    $stat = $Setting->base();
    $stat->set_value('profile_id',get_profile_id());
    $stat->set_value('person_id',get_person_id());
    $stat->set_value('name','facebook_status');
    $stat->set_value('value','enabled');
    $stat->save_changes();
    $stat->set_etag();
    $stat = $Setting->find($stat->id);
  }
  
  // get the one-to-one-related child-record from "entries"
  $sEntry =& $stat->FirstChild('entries');
  
  $staturl = $request->url_for(array('resource'=>'settings','id'=>$stat->id,'action'=>'put'));
  
  $status = $stat->value;
  
  $aktwitter_tw_text_options = array(
    'disabled'=>'disabled',
    'enabled'=>'enabled'
  );
  
    return vars(
      array( &$aktwitter_tw_text_options,&$status,&$staturl,&$sEntry,&$profile ),
      get_defined_vars()
    );
  
}

