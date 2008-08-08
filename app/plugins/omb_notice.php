<?php

after_filter( 'broadcast_omb_notice', 'insert_from_post' );

function broadcast_omb_notice( &$model, &$rec ) {
  
  global $request, $db;
  
  wp_plugin_include(array(
    'wp-oauth'
  ));
  
  $i = get_profile();
  
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
    
    $sub_token = $sub->token;
    $sub_secret = $sub->secret;
    
    $sid = $sub->FirstChild('identities');
    $url = $sid->post_notice;
    
    if (!empty($url) && !(strstr( $url, $request->base ))) {
    
      $sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
      $consumer = new OAuthConsumer($request->base, '', NULL);
      $token = new OAuthToken($sub_token, $sub_secret);
      $parsed = parse_url($url);
      $params = array();
    
      parse_str($parsed['query'], $params);
      $req = OAuthRequest::from_consumer_and_token($consumer, $token, "POST", $url, $params);
      $req->set_parameter( 'omb_version', OMB_VERSION );
      $req->set_parameter( 'omb_listenee', $listenee_uri );
      $req->set_parameter( 'omb_notice', $notice_uri );
      $req->set_parameter( 'omb_notice_content', $notice_content );
      $req->set_parameter( 'omb_notice_url', $notice_url );
      $req->set_parameter( 'omb_notice_license', $license );
      $req->sign_request( $sha1_method, $consumer, $token );
    
      $curl = curl_init($url);
      curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($curl, CURLOPT_HEADER, false);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $req->to_postdata());
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      $result = curl_exec($curl);
      curl_close($curl);
    
    }
    
  }
}

?>