<?php

global $request;

function mobile_event( &$vars ) {
  
 // if (!($_SERVER['REMOTE_ADDR'] == '204.244.102.2'))
 //   trigger_error('Sorry but your IP address is unlike the Zeep server',E_USER_ERROR);
  
  extract($vars);
  
  $Post =& $db->model('Post');
  $Identity =& $db->model('Identity');
  
  if ($_POST['event'] == 'MO') {
    
    // Array ( [sms_prefix] => omb__ [short_code] => 88147 [uid] => 243132 
    // [body] => test from deep [min] => +15035554444 [event] => MO )

    $p = $Post->base();
    $p->set_value( 'profile_id', $_POST['uid'] );
    $p->set_value( 'parent_id', 0 );
    $p->set_value( 'title', $_POST['body'] );
    $p->save_changes();
    $i = $Identity->find($_POST['uid']);
    if ($i)
      $p->set_etag($i->person_id);
    
    $response->set_var('profile',$i);
    load_apps();
    trigger_after( 'insert_from_post', $Post, $p );
    
    $response = "";
    
  } else {
    $response = "Welcome ".$_SERVER['REMOTE_ADDR']." to ".environment('site_title');  
  }
  
  $data = substr($response,0,100);

  header("Status: 200 OK");
  header("Date: ".gmdate(DATE_RFC822));
  header("Content-Type: text/plain");
  header("Content-Length: " . strval(strlen($data)));
  echo $data;  
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
  
  $smskey = environment('zeepAccessKey');
  
  if (empty($smskey))
    return;
  
  if (!(isset($rec->title)))
    return;
  
  global $request, $db;
  
  $i = owner_of($rec);
  
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
      //echo $response; exit;
      curl_close($ch);
      
    }
  }
}

