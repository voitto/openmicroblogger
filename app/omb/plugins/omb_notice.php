<?php

after_filter( 'broadcast_omb_notice', 'insert_from_post' );

function broadcast_omb_notice( &$model, &$rec ) {

  if (!($rec->table == 'posts'))
    return;

  if (!(isset($rec->title)) || !(isset($rec->uri)))
    return;
  
  global $request, $db;
  
  if (empty($rec->uri)) {
    $rec->set_value( 'uri', $request->url_for( array(
      'resource'=>'__'.$rec->id,
    )));
    $rec->save_changes();
  }

  if (!$request->resource == 'posts')
    return;
  
  wp_plugin_include(array(
    'wp-oauth'
  ));
  
  $i = owner_of($rec);
  
  $listenee_uri = $i->profile;
  
  $notice_uri = $rec->uri;
  
  $notice_content = substr($rec->title,0,140);
  
  $notice_url = $notice_uri;
  
  $license = $i->license;
  
  $sent_to = array();
  
  $Subscription = $db->model('Subscription');
  
  $Subscription->has_one( 'subscriber:identity' );
  
  $where = array(
    'subscriptions.subscribed'=>$i->id,
  );
  
  $Subscription->set_param( 'find_by', $where );
  
  $Subscription->find();
  
  while ($sub = $Subscription->MoveNext()) {
    
    $sub_token = trim($sub->token);
    $sub_secret = trim($sub->secret);
    
    $sid = $sub->FirstChild('identities');
    $url = $sid->post_notice;
    
    if (!in_array($url,$sent_to) && !empty($url) && !(strstr( $url, $request->base ))) {
      
      
      
      $sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
   
      
      $wp_plugins = "wp-plugins" . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR . "enabled";
      $path = plugin_path() . $wp_plugins . DIRECTORY_SEPARATOR . 'wp-openid' . DIRECTORY_SEPARATOR;
      add_include_path( $path ); 
      require_once "Auth/Yadis/Yadis.php";

      
      $fetcher = Auth_Yadis_Yadis::getHTTPFetcher();
      
      
      //for ($i=0;$i<5;$i++) {
        
      
        $consumer = new OAuthConsumer($request->base, '');
        $token = new OAuthToken($sub_token, $sub_secret);
        $parsed = parse_url($url);
        $params = array();
        parse_str($parsed['query'], $params);
        $req = OAuthRequest::from_consumer_and_token($consumer, $token, "POST", $url, $params );
        $req->set_parameter('omb_version', OMB_VERSION );
        $req->set_parameter('omb_listenee', $listenee_uri );
        $req->set_parameter('omb_notice', $notice_uri );
        $req->set_parameter('omb_notice_content', $notice_content );
        $req->set_parameter('omb_notice_url', $notice_url );
        $req->set_parameter('omb_notice_license', $license );

        $req->sign_request($sha1_method, $consumer, $token);

        $result = $fetcher->post($req->get_normalized_http_url(),
                     $req->to_postdata());

        if ( $result->status == 403 ) {
          $db->delete_record($sub);
        } else {
          parse_str( $result->body, $return );
          if ( is_array($return) && $return['omb_version'] == OMB_VERSION ) {
            $sent_to[] = $url;
          } else {
            admin_alert('failed to post'."\n\n".$url."\n\n".$result->body."\n\n".$notice_content);
          }
        }

      //}
      
      
      // this is the old CURL version of omb_notice
      
      //$curl = curl_init($url);
      //curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
      //curl_setopt($curl, CURLOPT_HEADER, false);
      //curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      //curl_setopt($curl, CURLOPT_POST, true);
      //curl_setopt($curl, CURLOPT_POSTFIELDS, $req->to_postdata());
      //curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      //$result = curl_exec($curl);
      //curl_close($curl);
      
      
      
      
    
    }
    
  }
}

?>