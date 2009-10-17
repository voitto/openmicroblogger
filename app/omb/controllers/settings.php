<?php





function put( &$vars ) {
  extract( $vars );
  if (!(get_profile_id()))
    trigger_error( 'Sorry, the setting could not be saved', E_USER_ERROR );
  $s = $Setting->find($request->id);
  if (strpos($s->name, 'password') !== false)
    $request->set_param(array('setting','value'),
      md5_encrypt($request->params['setting']['value'], $db->dbname)
    );
  $event = $s->name."_enabled";
  if (('enabled' == $request->params['setting']['value'])&&function_exists($event))
    $event();
  $event = $s->name."_disabled";
  if (('disabled' == $request->params['setting']['value'])&&function_exists($event))
    $event();
  $resource->update_from_post( $request );
  header_status( '201 Created' );
  redirect_to( $request->resource );
}


function post( &$vars ) {
  extract( $vars );
  
  if (!(get_profile_id()))
    trigger_error( 'Sorry, the setting could not be saved', E_USER_ERROR );
  
  $request->set_param( array( 'setting', 'profile_id' ), get_profile_id() );
  
  if (strpos($request->params['setting']['name'], 'password') !== false)
    $request->set_param(array('setting','value'),
      md5_encrypt($request->params['setting']['value'], $db->dbname)
    );
  
  $settingname = $request->params['setting']['name'];

  $set = split('\.',$settingname);
  
  if (is_array($set) && $set[0] == 'config') {
    if (!member_of('administrators'))
      trigger_error( 'Sorry, you must be an administrator to do that', E_USER_ERROR );
    $s = $Setting->find_by( 'name', $settingname );
    if ($s)
      $db->delete_record($s);
  }
  
  if ($settingname == 'app') {
    
    $do_install = false;
    
    $app = $settingname;
    
    $sources = environment('remote_sources');
    $remote_list = array();
    
    foreach($sources as $name=>$url) {
      $p = get_profile();
      $url = "http://".$url."&p=".urlencode($p->profile_url)."&a=".urlencode($app);
      $curl = curl_init($url);
      curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($curl, CURLOPT_HEADER, false);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      $result = curl_exec( $curl );
      if ($result) {
        if (trim($result) == 'install')
          $do_install = true;
          continue;
      }
      curl_close( $curl );  
    }
    
    if (!$do_install)
      trigger_error( 'Sorry, you are not authorized to install '.$app, E_USER_ERROR );
    
  }
  $event = $settingname."_enabled";
  if (('enabled' == $request->params['setting']['value'])&&function_exists($event))
    $event();
  $event = $settingname."_disabled";
  if (('disabled' == $request->params['setting']['value'])&&function_exists($event))
    $event();
  $resource->insert_from_post( $request );
  header_status( '201 Created' );
  redirect_to( $request->resource );
}



function delete( &$vars ) {
  extract( $vars );
  $s = $collection->MoveFirst();
  if (!$s || $s->profile_id != get_profile_id())
    trigger_error( 'Sorry, the setting could not be deleted', E_USER_ERROR );
  $resource->delete_from_post( $request );
  header_status( '200 OK' );
  redirect_to( $request->resource );
}




