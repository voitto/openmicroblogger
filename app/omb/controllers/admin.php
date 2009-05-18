<?php

function _theme( &$vars ) {
  
  extract( $vars );
  
  $paths = array( $GLOBALS['PATH']['app'], 'wp-content/' );
  
  $previews = array();
  $themepaths = array();
  
  $installedtheme = '';
  
  foreach($paths as $loadpath) {
  
  if (!empty($loadpath) && $handle = opendir($loadpath)) {
  
    while (false !== ($file = readdir($handle))) {
      if ($file != '.' && $file != '..') {
      $subload = $loadpath.$file;
      if (is_dir($subload) && ($file != '.svn') && !empty($subload) && $subhandle = opendir($subload)) {
        while (false !== ($subfile = readdir($subhandle))) {
          if ($subfile != '.' && $subfile != '..' && $subfile != ".svn") {
           $subsub = $subload."/".$subfile;
          if (is_dir($subsub) && ($subfile != '.svn') && !empty($subsub) && $subsubhandle = opendir($subsub)) {
          while (false !== ( $subsubf = readdir($subsubhandle))) {
          if ($subsubf == 'screenshot.png') {
            $previews[$subfile] = $subsub.'/screenshot.png';
            $Setting =& $db->model('Setting');
            $tp = $Setting->find_by('name','config.env.themepath'.$subfile);
            if ($tp)
              $db->delete_record($tp);
            $tp = $Setting->base();
            $tp->set_value('name','config.env.themepath'.$subfile);
            $tp->set_value('value',$subload);
            $tp->save_changes();
          }
          }}
        }}
        closedir($subhandle);
      }
    }}
    closedir($handle);
  }
  
  }
  

  
  return vars(
    array( 
      &$previews,
      &$installedtheme
    ),
    get_defined_vars()
  );
  
}

function index( &$vars ) {
  extract( $vars );
  
  $aktwitter_tw_text_options = array(
    '0'=>'false',
    '1'=>'true'
  );
  
  $Setting =& $db->model('Setting');

  $threadmode = $Setting->find_by(array('name'=>'config.env.threaded','profile_id'=>get_profile_id()));
  if (!$threadmode) {
    $threadmode = $Setting->base();
    $threadmode->set_value('profile_id',get_profile_id());
    $threadmode->set_value('person_id',get_person_id());
    $threadmode->set_value('name','config.env.threaded');
    $threadmode->set_value('value',1);
    $threadmode->save_changes();
    $threadmode->set_etag();
    $threadmode = $Setting->find($threadmode->id);
  }
  $threadurl = $request->url_for(array('resource'=>'settings','id'=>$threadmode->id,'action'=>'put'));
  $threadentry = $threadmode->FirstChild('entries');

  $catmode = $Setting->find_by(array('name'=>'config.env.categories','profile_id'=>get_profile_id()));
  if (!$catmode) {
    $catmode = $Setting->base();
    $catmode->set_value('profile_id',get_profile_id());
    $catmode->set_value('person_id',get_person_id());
    $catmode->set_value('name','config.env.categories');
    $catmode->set_value('value',0);
    $catmode->save_changes();
    $catmode->set_etag();
    $catmode = $Setting->find($catmode->id);
  }
  $caturl = $request->url_for(array('resource'=>'settings','id'=>$catmode->id,'action'=>'put'));
  $catentry = $catmode->FirstChild('entries');
  
  return vars(
    array( 
      &$collection,
      &$aktwitter_tw_text_options,
      &$profile,
      &$threadmode,
      &$threadurl,
      &$threadentry,
      &$catmode,
      &$caturl,
      &$catentry
    ),
    get_defined_vars()
  );
}


function _index( &$vars ) {
  // index controller returns
  // a Collection of recent entries
  extract( $vars );
  return vars(
    array( 
      &$collection,
      &$profile
    ),
    get_defined_vars()
  );
}



