<?php

after_filter( 'broadcast_email_notice', 'insert_from_post' );

function broadcast_email_notice( &$model, &$rec ) {
  
  if (!(isset($rec->title)))
    return;
  
  global $request, $db;
  
  $i = owner_of($rec);
  
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
    
    if (!in_array($sid->id,$sent_to) && $sub->email) {
      
      $html = false;
      // this is the body of the e-mail if ($html == false)
      $text = $rec->title;
      
      $subject = $i->nickname . " posted a notice";
      
      send_email( $sid->email_value, $subject, $text, environment('email_from'), environment('email_name'), $html );
      
      $sent_to[] = $sid->id;
      
    }
  }
}

?>