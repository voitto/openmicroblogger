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
    'false'=>'false',
    'true'=>'true'
  );
  
  return vars(
    array( 
      &$collection,
      &$aktwitter_tw_text_options,
      &$profile
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



