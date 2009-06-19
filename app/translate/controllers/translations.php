<?php


function get( &$vars ) {
  extract( $vars );
  switch ( count( $collection->members )) {
    case ( 1 ) :
      if ($request->id && $request->entry_url())
        render( 'action', 'entry' );
    default :
      render( 'action', 'index' );
  }
}


function post( &$vars ) {
  extract( $vars );
  
  $resource->insert_from_post( $request );
  header_status( '201 Created' );
  redirect_to( array('resource'=>$request->resource,'id'=>$request->id,'action'=>'edit') );
}


function put( &$vars ) {
  extract( $vars );
  
  $arr = array();
  foreach($_POST as $k=>$v) {
    if (substr($k,0,6) == 'trans_') {
      $arr[substr($k,6)] = $db->escape_string($v);
    }
  }
  
  $request->set_param(array('translation','data'),serialize($arr));
  
  $resource->update_from_post( $request );
  header_status( '200 OK' );
  redirect_to( $request->url_for(array("resource"=>$profile->nickname))."/settings" );
}


function delete( &$vars ) {
  extract( $vars );
  $resource->delete_from_post( $request );
  header_status( '200 OK' );
  redirect_to( $request->resource );
}


function _doctype( &$vars ) {
  // doctype controller
}



function index( &$vars ) {
  extract( $vars );
  $theme = environment('theme');
  $blocks = environment('blocks');
  $atomfeed = $request->feed_url();
  return vars(
    array(
      &$blocks,
      &$profile,
      &$collection,
      &$atomfeed,
      &$theme
    ),
    get_defined_vars()
  );
}


function _index( &$vars ) {

  extract( $vars );
  
  $translation_files = array();
  
  $loadpath = 'wp-content/language/';
  
  if (!empty($loadpath) && $handle = opendir($loadpath)) {
    while (false !== ($file = readdir($handle))) {
      if ($file != '.' && $file != '..' && substr($file,-3) == 'php' && $file != 'lang_chooser.php') {
        $txt = array();
        include $loadpath.$file;
        $code = substr($file,0,-4);
        $lang = "";
        if ($code == 'eng')
          $lang = "English";
        if ($code == 'ger')
          $lang = "German";
        $translation_files[] = array(
            'id' => 0,
          'name' => $lang,
          'code' => $code,
          'data' => ''
        );
  }}}
  
  
  //$tran['id/name/code/data']
  $fieldcount = count($txt);
  
  $status = array();
  
  while ($lang = $collection->MoveNext()) {
    $txt = unserialize($lang->data);
    $thiscount = 0;
    foreach($txt as $phrase=>$trans) {
      if (!empty($trans)) {
        $thiscount++;
      }
    }
    $count1 = $thiscount/$fieldcount;
    $count2 = $count1 * 100;
    $count = number_format($count2, 0);
    $status[$lang->name] = $count."%";
  }
  
  $collection->rewind();
  
  return vars(
    array( &$collection, &$profile, &$translation_files, &$status ),
    get_defined_vars()
  );
}


function _entry( &$vars ) {
  // entry controller returns
  // a Collection w/ 1 member entry
  extract( $vars );
  $Member = $collection->MoveNext();
  $Entry = $Member->FirstChild( 'entries' );
  return vars(
    array( &$collection, &$Member, &$Entry, &$profile ),
    get_defined_vars()
  );
}


function _new( &$vars ) {
  extract( $vars );
  $model =& $db->get_table( $request->resource );
  $Member = $model->base();
  return vars(
    array( &$Member, &$profile ),
    get_defined_vars()
  );
}


function _edit( &$vars ) {
  extract( $vars );
  $Member = $collection->MoveFirst();
  $Entry = $Member->FirstChild( 'entries' );


  $translation_fields = array();
  
  $loadpath = 'wp-content/language/';
  
  if (!empty($loadpath) && $handle = opendir($loadpath)) {
    while (false !== ($file = readdir($handle))) {
      if ($file != '.' && $file != '..' && substr($file,-3) == 'php' && $file != 'lang_chooser.php') {
        $code = substr($file,0,-4);
        $lang = "";
        $txt = array();
        include $loadpath.$file;
        if ($code == 'eng')
          $lang = "English";
        if ($code == 'ger')
          $lang = "German";
        $translation_fields[] = array(
          'lang'=>$lang,
          'fields'=>$txt
        );
  }}}
  
  $template_fields = array_keys($txt);
  
  return vars(
    array( &$Member, &$Entry, &$profile, &$translation_fields, &$template_fields ),
    get_defined_vars()
  );
}

function export_eng( &$vars ) {
  $file = "eng.php";
  $loadpath = 'wp-content/language/';
  $txt = array();
  include $loadpath.$file;
  header( 'Content-Type: text/plain' );
  header( "Content-Disposition: attachment" );
  echo "\n";
  echo "English"."\n";
  echo "eng"."\n";
  echo serialize($txt)."\n";
  exit;
}

function export_ger( &$vars ) {
  $file = "ger.php";
  $loadpath = 'wp-content/language/';
  $txt = array();
  include $loadpath.$file;
  header( 'Content-Type: text/plain' );
  header( "Content-Disposition: attachment" );
  echo "\n";
  echo "German"."\n";
  echo "ger"."\n";
  echo serialize($txt)."\n";
  exit;
}

function _export( &$vars ) {
  extract( $vars );
  $Member = $collection->MoveFirst();
  $Entry = $Member->FirstChild( 'entries' );
  return vars(
    array( &$Member, &$Entry, &$profile ),
    get_defined_vars()
  );
}


function _remove( &$vars ) {
  extract( $vars );
  $Member = $collection->MoveFirst();
  $Entry = $Member->FirstChild( 'entries' );
  return vars(
    array( &$Member, &$Entry, &$profile ),
    get_defined_vars()
  );
}


function _block( &$vars ) {

  extract( $vars );
  return vars(
    array(
      &$Entry,
      &$collection
    ),
    get_defined_vars()
  );

}

