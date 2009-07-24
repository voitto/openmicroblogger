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
  
  if (!member_of('administrators'))
    trigger_error('sorry you must be an administrator to do that', E_USER_ERROR);
  
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

  $uplmode = $Setting->find_by(array('name'=>'config.env.uploads','profile_id'=>get_profile_id()));
  if (!$uplmode) {
    $uplmode = $Setting->base();
    $uplmode->set_value('profile_id',get_profile_id());
    $uplmode->set_value('person_id',get_person_id());
    $uplmode->set_value('name','config.env.uploads');
    $uplmode->set_value('value',0);
    $uplmode->save_changes();
    $uplmode->set_etag();
    $uplmode = $Setting->find($uplmode->id);
  }
  $uplurl = $request->url_for(array('resource'=>'settings','id'=>$uplmode->id,'action'=>'put'));
  $uplentry = $uplmode->FirstChild('entries');
  
  // n1mode = upload max size in MB, default = 4
  $n1mode = $Setting->find_by(array('name'=>'config.env.max_upload_mb','profile_id'=>get_profile_id()));
  if (!$n1mode) {
    $n1mode = $Setting->base();
    $n1mode->set_value('profile_id',get_profile_id());
    $n1mode->set_value('person_id',get_person_id());
    $n1mode->set_value('name','config.env.max_upload_mb');
    $n1mode->set_value('value',4);
    $n1mode->save_changes();
    $n1mode->set_etag();
    $n1mode = $Setting->find($n1mode->id);
  }
  $n1url = $request->url_for(array('resource'=>'settings','id'=>$n1mode->id,'action'=>'put'));
  $n1entry = $n1mode->FirstChild('entries');

  global $timezone_offsets;
  $n2list = $timezone_offsets;

  // n2mode = upload max size in MB, default = 4
  $n2mode = $Setting->find_by(array('name'=>'config.env.timezone','profile_id'=>get_profile_id()));
  if (!$n2mode) {
    $n2mode = $Setting->base();
    $n2mode->set_value('profile_id',get_profile_id());
    $n2mode->set_value('person_id',get_person_id());
    $n2mode->set_value('name','config.env.timezone');
    $n2mode->set_value('value','-8');
    $n2mode->save_changes();
    $n2mode->set_etag();
    $n2mode = $Setting->find($n2mode->id);
  }
  $n2url = $request->url_for(array('resource'=>'settings','id'=>$n2mode->id,'action'=>'put'));
  $n2entry = $n2mode->FirstChild('entries');

  // n3mode = image upload thumbnail size 
  $n3mode = $Setting->find_by(array('name'=>'config.env.max_pixels','profile_id'=>get_profile_id()));
  if (!$n3mode) {
    $n3mode = $Setting->base();
    $n3mode->set_value('profile_id',get_profile_id());
    $n3mode->set_value('person_id',get_person_id());
    $n3mode->set_value('name','config.env.max_pixels');
    $n3mode->set_value('value',200);
    $n3mode->save_changes();
    $n3mode->set_etag();
    $n3mode = $Setting->find($n3mode->id);
  }
  $n3url = $request->url_for(array('resource'=>'settings','id'=>$n3mode->id,'action'=>'put'));
  $n3entry = $n3mode->FirstChild('entries');
  
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
      &$catentry,
      &$uplmode,
      &$uplurl,
      &$uplentry,
      &$n1mode,
      &$n1url,
      &$n1entry,
      &$n2mode,
      &$n2url,
      &$n2entry,
      &$n2list,
      &$n3mode,
      &$n3url,
      &$n3entry
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

function _sources( &$vars ) {
extract( $vars );
  
  if (!member_of('administrators'))
    trigger_error('sorry you must be an administrator to do that', E_USER_ERROR);
  
  $aktwitter_tw_text_options = array(
    '0'=>'false',
    '1'=>'true'
  );
  
  $Setting =& $db->model('Setting');
  
  $returnvars = array();
  
  $TwitterUser =& $db->model('TwitterUser');
  $TwitterUser->find_by( array('eq'=>'not like','oauth_key'=>''),1 );
  $i=1;
  while ($tu = $TwitterUser->MoveNext()) {
    
    $modevar = 'n'.$i.'mode';
    $urlvar = 'n'.$i.'url';
    $entryvar = 'n'.$i.'entry';
    $nickvar = 'n'.$i.'nick';
    $i++;
    
    $$nickvar = $tu->screen_name;
    $$modevar = $Setting->find_by('name','config.env.importtwitter_'.$tu->id);
    
    if (!$$modevar) {
      $$modevar = $Setting->base();
      $$modevar->set_value('profile_id',get_profile_id());
      $$modevar->set_value('person_id',get_person_id());
      $$modevar->set_value('name','config.env.importtwitter_'.$tu->id);
      $$modevar->set_value('value',0);
      $$modevar->save_changes();
      $$modevar->set_etag();
      $$modevar = $Setting->find($$modevar->id);
    }
    
    $$urlvar = $request->url_for(array('resource'=>'settings','id'=>$$modevar->id,'action'=>'put'));
    $$entryvar = $$modevar->FirstChild('entries');
  
    $returnvars[] = &$$modevar;
    $returnvars[] = &$$urlvar;
    $returnvars[] = &$$entryvar;
    $returnvars[] = &$$nickvar;

  }

  $returnvars[] = &$collection;
  $returnvars[] = &$profile;
  $returnvars[] = &$aktwitter_tw_text_options;
  
  $listvars = array(1=>'friends_timeline',0=>'disabled');
  $returnvars[] = &$listvars;
  
  $returnvars[] = &$i;

  return vars(
    $returnvars,
    get_defined_vars()
  );
  
}


