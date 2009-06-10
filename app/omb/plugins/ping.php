<?php

function send_ping( &$model, &$rec ) {
  
  if (!PING)
    return;

  global $db;
  global $request; 
  $req =& $request;
  $Entry =& $db->get_table('entries');
  
  $notify_table = $model->table;
  $recid = $rec->id;
  
  if (!empty($db->prefix))
    $chan = $db->prefix;
  else
    $chan = "chan";
  
  if (REALTIME_HOST) {
    
    $o = owner_of($rec);
    $payload = array();
    
    if (environment('threaded') && isset($rec->parent_id) && $rec->parent_id > 0) {
      
      // push a P2 comment
      
      $par = $db->get_record( 'posts',$rec->parent_id );
      $tweet = render_comment($rec,$o,$par);
      $payload['html'] = $tweet;
      $payload['in_reply_to'] = $rec->parent_id;
      
    } else {
      
      // push a P2 tweet
      
      $tweet = '';
      $tweet .= '<li id="prologue-'.$rec->id.'" class="user_id_'.$o->id.'">';
      $tweet .= '<img alt=\'\' src=\''.$o->avatar.'\' class=\'avatar avatar-48\' height=\'48\' width=\'48\' />';
      $tweet .= '<h4>';
      $tweet .= '<a href="'.$o->profile.'" title="Posts by '.$o->nickname.'">'.$o->nickname.'</a>    <span class="meta">'.date( "g:i A" , strtotime($rec->created) ).'<em>on</em> '.date( get_settings('date_format'), strtotime($rec->created) ).' |';
      $tweet .= '        <span class="actions">';
      $tweet .= '    <a href="'.$request->url_for(array('resource'=>$notify_table,'action'=>'entry.html','id'=>$recid)).'" class="thepermalink">Permalink</a>';
      $tweet .= '                  </span>';
      $tweet .= '  <br />';
      $tweet .= '          </span>';
      $tweet .= '  </h4>';
      $tweet .= '  <div class="postcontent" id="content-'.$rec->id.'"><p>'.render_notice($rec->title,$rec,$o).'</p></div>';
      $tweet .= '    <div class="bottom_of_entry">&nbsp;</div>';
      $tweet .= '   <ul class="commentlist">';
//      $tweet .= '   <ul id="comments" class="commentlist">';
      $tweet .= '  </ul>';
      $tweet .= '</li>';
      $payload['html'] = $tweet;
      $payload['in_reply_to'] = 0;
    }
    if (!(class_exists('Services_JSON')))
      lib_include( 'json' );
    $json = new Services_JSON();
    $load = $json->encode($payload);
    
    $curl = curl_init( "http://".REALTIME_HOST.":".REALTIME_PORT );
    
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $curl, CURLOPT_TIMEOUT, 1);
    curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'ADDMESSAGE '.$chan.' '.addslashes($load) );
    
    $output = curl_exec($curl);
    
  }

  if (!(get_profile_id()))
    return;
  
  if (array_key_exists( 'target_id', $model->field_array )) {
    $e = $Entry->find($rec->attributes['target_id']);
    if ($e) {
      $notify_table = $e->resource;
      $recid = $e->record_id;
    }
  }
  
  $url = environment('ping_server');
  
  if (empty($url))
    return;
  
  $url .= "=".$request->url_for(array('resource'=>$notify_table,'action'=>'entry.html','id'=>$recid));
  
  $curl = curl_init($url);
  $method = "GET";
  curl_setopt($curl, CURLOPT_HEADER, false);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($curl, CURLOPT_HTTPGET, ($method == "GET"));
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($curl);
  
}

