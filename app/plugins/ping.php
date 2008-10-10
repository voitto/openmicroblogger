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
  
  $go = false;
  
  if (!$go)
    return;
  
  //incomplete/experimental
    
  $curl = curl_init($url);
  $method = "GET";
  $params = array(); // not populated needs data
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($curl, CURLOPT_HEADER, false);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($curl, CURLOPT_HTTPGET, ($method == "GET"));
  curl_setopt($curl, CURLOPT_POST, ($method == "POST"));
  if ($method == "POST") curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($curl);
  
}

