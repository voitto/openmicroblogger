<?php

function send_ping( &$model, &$rec ) {
  
  global $db;
  global $request; 
  $req =& $request;
  $Entry =& $db->get_table('entries');
  
  $notify_table = $model->table;
  
  if (array_key_exists( 'target_id', $model->field_array )) {
    $e = $Entry->find($rec->attributes['target_id']);
    if ($e)
      $notify_table = $e->resource;
  }
  
  $datamodel =& $db->get_table($notify_table);
  // http://dejafeed.com/?submit
  $url = environment('ping_server');
  
  if (empty($url))
    return;
    
  $url .= "=".$request->url_for(array('resource'=>$rec->table,'action'=>'entry.html','id'=>$rec->id));
  
  $curl = curl_init($url);
  $method = "GET";
  curl_setopt($curl, CURLOPT_HEADER, false);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($curl, CURLOPT_HTTPGET, ($method == "GET"));
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($curl);
  
}

