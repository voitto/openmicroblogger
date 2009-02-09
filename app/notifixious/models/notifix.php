<?php


after_filter( 'broadcast_notifixious_notice', 'insert_from_post' );

function broadcast_notifixious_notice( &$model, &$rec ) {
  
  $notifixkey = '';
  
  $login = 'brianjesse';
  $pass = '';
  $notifixurl = 'notifixio.us';
  
  if (!(isset($rec->title)))
    return;
  
  if (!get_profile_id())
    return;
  
  $installed = environment( 'installed' );
  
  if ( !in_array( 'notifixious', $installed ))
    return;
  
  if (!(class_exists('Services_JSON')))
    lib_include( 'json' );
  
  $url = "http://".$notifixurl."/sources/find.json";
  $params = "url=".urlencode(get_bloginfo('rss2_url'));
  
  $results = notifixious_http_request($url."?".$params, "GET");
  
  $jsonobj = json_decode($results[1]);
  
  $source_id = $jsonobj->sources->source->permalink;
  
  if($source_id != "")
  {
      update_option('notifixiousSourceId',''.$source_id.'', '', 'no');
      update_option('notifixiousRegistered','1', '', 'no');
      update_option('notifixiousClaimed','0', '', 'yes');
  }
  else
  {
      update_option('notifixiousSourceId','0', '', 'no');
      update_option('notifixiousRegistered','0', '', 'no');
  }
  
  $post = get_post($rec);
  
  $title = urlencode($post->post_title);
  $text = urlencode($post->post_content);
  $link = urlencode($post->guid);
  $url = "http://".urlencode($login).":".urlencode($pass)."@".
  $notifixurl."/sources/".$source_id."/events.json?"."event[title]=".$title."&event[text]=".$text."&event[link]=".$link;
  
  echo $url; exit;
  //http://:@?event[title]=&event[text]=&event[link]=
  
  $arr = notifixious_http_request($url, "POST");
  print_r($arr);
  exit;
}

function notifixious_http_request($link, $method){
   $url_parts = @parse_url( $link );
    $host = $url_parts["host"];
    $path = $url_parts["path"];
    if($url_parts["query"])
    {
        $query = $url_parts["query"];
        $http_request  = "$method $path?$query HTTP/1.0\r\n";
    }
    else
    {
        $http_request  = "$method $path HTTP/1.0\r\n";
    }
    if($url_parts["user"] && $url_parts["pass"])
    {
        $user = $url_parts["user"];
        $pass = $url_parts["pass"];
        $auth = $user.":".$pass ; 
        $encoded_auth = base64_encode($auth);
        $http_request .= "Authorization: Basic ".$encoded_auth."\r\n";
    }
    $port = 80;
    $http_request .= "Host: $host\r\n";        	
    $http_request .= "User-Agent: WordPress \r\n";
    $http_request .= "\r\n";
    $response = '';
    if( false != ( $fs = @fsockopen($host, $port, $errno, $errstr, 10) ) ) {
        fwrite($fs, $http_request);
        while ( !feof($fs) )
            $response .= fgets($fs); // One TCP-IP packet
        fclose($fs);
    }
    $response = explode("\r\n\r\n", $response, 2);
    return $response;
    
  }