<?php

global $request;

function mobile_event( &$vars ) {
  
  $response = "Welcome to ".environment('site_title');  
  
  $header = array(
      "Status: 200 OK",
      "Date: ".gmdate(DATE_RFC822),
      "Content-Type: text/plain",
      "Content-Length: " . strval(strlen($response))
  );
  
  foreach ($header as $str)
    header($str);
  
  echo substr($response,0,100);
  
  exit;
  
}

function mobile_settings( &$vars ) {
  render( 'action', 'mobile' );
}

function _mobile( &$vars ) {

  extract( $vars );
  $foo = "";
  return vars(
    array(&$foo),
    get_defined_vars()
  );
  
}



after_filter( 'broadcast_sms_notice', 'insert_from_post' );

function broadcast_sms_notice( &$model, &$rec ) {
  
  $smsurl = environment('zeepUrl');
  
  if (empty($smsurl))
    return;
  
  if (!(isset($rec->title)))
    return;
  
  global $request, $db;
  
  $i = get_profile();
  
  $notice_content = substr($rec->title,0,100);
  
  $sent_to = array();
  
  $Subscription = $db->model('Subscription');
  
  $Subscription->has_one( 'subscriber:identity' );
  
  $where = array(
    'subscriptions.subscribed'=>$i->id,
  );
  
  $Subscription->set_param( 'find_by', $where );
  
  $Subscription->find();
  
  while ($sub = $Subscription->MoveNext()) {
    
    $sid = $sub->FirstChild('identities');
    
    if (!in_array($sid->id,$sent_to) && $sub->sms) {
      
      $sent_to[] = $sid->id;
      
      $apiurl = environment('zeepUrl');
      
      $secret = environment('zeepSecretKey');
      
      $apikey = environment('zeepAccessKey');
      
      $http_date = gmdate( DATE_RFC822 );
      
      $parameters = "user_id=".$sid->id."&body=".urlencode($notice_content);
      
      $canonical_string = $apikey . $http_date . $parameters;
      
      $b64_mac = base64_encode(hash_hmac("sha1", $canonical_string, $secret, TRUE));
      
      $authentication = "Zeep $apikey:$b64_mac";
      
      $header = array(
          "Authorization: ".$authentication,
          "Date: ".$http_date,
          "Content-Type: application/x-www-form-urlencoded",
          "Content-Length: " . strval(strlen($parameters))
      );
      
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $apiurl );
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters );
      $response = curl_exec($ch);
      curl_close($ch);
      
    }
  }
}

?>