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
  
  $password = $Setting->find_by(array('name'=>'aktt_identica_password','profile_id'=>get_profile_id()));
  if (!$password){
    $password = $Setting->base();
    $password->set_value('profile_id',get_profile_id());
    $password->set_value('person_id',get_person_id());
    $password->set_value('name','aktt_identica_password');
    $password->save_changes();
    $password->set_etag();
    $password = $Setting->find($password->id);
    $pword = "";
  }
  
  if (!empty($password->value))
    $pword = "******";
  
  // get the one-to-one-related child-record from "entries"
  $pEntry =& $password->FirstChild('entries');
  
  $passurl = $request->url_for(array('resource'=>'settings','id'=>$password->id,'action'=>'put'));
  
  $username = $Setting->find_by(array('name'=>'aktt_identica_username','profile_id'=>get_profile_id()));
  
  if (!$username) {
    $username = $Setting->base();
    $username->set_value('profile_id',get_profile_id());
    $username->set_value('person_id',get_person_id());
    $username->set_value('name','aktt_identica_username');
    $username->save_changes();
    $username->set_etag();
    $username = $Setting->find($username->id);
  }
  
  // get the one-to-one-related child-record from "entries"
  $uEntry =& $username->FirstChild('entries');
  
  $userurl = $request->url_for(array('resource'=>'settings','id'=>$username->id,'action'=>'put'));

  $stat = $Setting->find_by(array('name'=>'identica_status','profile_id'=>get_profile_id()));
  
  if (!$stat) {
    $stat = $Setting->base();
    $stat->set_value('profile_id',get_profile_id());
    $stat->set_value('person_id',get_person_id());
    $stat->set_value('name','identica_status');
    $stat->set_value('value','enabled');
    $stat->save_changes();
    $stat->set_etag();
    $stat = $Setting->find($stat->id);
  }
  
  // get the one-to-one-related child-record from "entries"
  $sEntry =& $stat->FirstChild('entries');
  
  $staturl = $request->url_for(array('resource'=>'settings','id'=>$stat->id,'action'=>'put'));
  
  $status = $stat->value;
  
  $akidentica_tw_text_options = array(
    'disabled'=>'disabled',
    'enabled'=>'enabled'
  );
  
  return vars(
    array( &$akidentica_tw_text_options,&$status,&$staturl,&$pword,&$userurl,&$passurl,&$password,&$sEntry,&$username,&$uEntry,&$pEntry, &$profile ),
    get_defined_vars()
  );
  
}




function identica_login_test() {
  $test = @dent_login_test(
  	@stripslashes(get_option('aktt_identica_username')),
  	@stripslashes(get_option('aktt_identica_password'))
  );
  if ($test)
  	echo 1;
  else
    echo 0;
  exit;
}

function dent_login_test($username, $password) {
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	$snoop = new Snoopy;
	$snoop->agent = 'Twitter Tools http://alexking.org/projects/wordpress';
	$snoop->user = $username;
	$snoop->pass = $password;
	$snoop->fetch('http://identi.ca/api/statuses/user_timeline.json');
	if (strpos($snoop->response_code, '200')) {
		return true;
	}
	else {
		return false;
	}
}



