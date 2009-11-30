<?php

function send_ping( &$model, &$rec ) {

  if (defined('PING') && !PING)
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
  
  if (defined('REALTIME_HOST') && REALTIME_HOST && $rec->table == 'posts') {
    
    $o = owner_of($rec);
    $payload = array();
    
    if (environment('threaded') && isset($rec->parent_id) && $rec->parent_id > 0) {
      
      // push a P2 comment
      
      $par = $db->get_record( 'posts',$rec->parent_id );
      $tweet = render_comment($rec,$o,$par);
      $payload['html'] = $tweet;
      if ($rec->parent_id > 0)
        $payload['in_reply_to'] = "#commentcontent-".$rec->parent_id;
      else
        $payload['in_reply_to'] = "#content-".$rec->parent_id;
            
    } else {
      
      // push a P2 tweet
      $o = owner_of($rec);
      
      $tweet = '<hr />'."\n";
      $tweet .= '<h4>'."\n";
      $tweet .= '<span class="meta"> <span class="actions"> <a href="'.$request->url_for(array('resource'=>$notify_table,'id'=>$recid)).'" class="thepermalink">Permalink</a> | <a href="'.$request->url_for(array('resource'=>$notify_table,'id'=>$recid)).'" class="post-reply-link" rel="'.$recid.'">Reply</a> <br />'."\n";
      $tweet .= '</span> <br />'."\n";
      $tweet .= '<img alt="" src="'.$o->avatar.'" class="avatar avatar-48" height="48" width="48" /> <a class="nick" href="'.$o->profile.'" title="Posts by '.$o->nickname.'">'.$o->nickname.'</a> '.laconica_time($rec->created).' | <a href="">0</a> </span>'."\n";
      $tweet .= '</h4>'."\n";
      $tweet .= '<div class="postcontent" id="content-<?php echo $recid; ">'."\n";
      $tweet .= '<p>'."\n";
      $tweet .= render_notice($rec->title,$rec,$o);
      $tweet .= '</p>'."\n";
      $tweet .= '</div>'."\n";
      $tweet .= '<!-- // postcontent -->'."\n";
      $tweet .= '<div class="bottom_of_entry">'."\n";
      $tweet .= '&nbsp;'."\n";
      $tweet .= '</div>'."\n";
      $tweet .= '<div class="commentlist">'."\n";
      $tweet .= '</div>'."\n";
      
      $oldstyle = false;
      
      if ($oldstyle) {
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
      }
      
      $payload['html'] = $tweet;
      $payload['in_reply_to'] = 0;
    }

    $payload['avatar'] = $o->avatar;
    $payload['profile_url'] = $o->profile_url;
    $payload['profile_id'] = $o->id;
    $payload['link'] = $request->url_for(array('resource'=>$notify_table,'action'=>'entry.html','id'=>$recid));
    $payload['tweet'] = render_notice($rec->title,$rec,$o);
    $payload['name'] = $o->fullname;
    $payload['nickname'] = $o->nickname;
    $payload['created'] = date( "g:i A" , strtotime($rec->created) );
    $payload['id'] = $rec->id;
    $payload['callback'] = '';

    if ($rec->parent_id)
	    $payload['comment'] = 1;
		else
		  $payload['comment'] = 0;
		
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

