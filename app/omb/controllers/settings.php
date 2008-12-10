<?php





function put( &$vars ) {
  extract( $vars );
  if (!(get_profile_id()))
    trigger_error( 'Sorry, the setting could not be saved', E_USER_ERROR );
  $resource->update_from_post( $request );
  header_status( '201 Created' );
  redirect_to( $request->resource );
}


function post( &$vars ) {
  extract( $vars );
  if (!(get_profile_id()))
    trigger_error( 'Sorry, the setting could not be saved', E_USER_ERROR );
  $request->set_param( array( 'setting', 'profile_id' ), get_profile_id() );
  
  
  if ($request->params['setting']['name'] == 'app') {
    
    $do_install = false;
    
    $app = trim($request->params['setting']['value']);
    
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




