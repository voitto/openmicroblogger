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
      &$n1entry
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



function setting_widget_text_helper($nam,$nammode,$namurl,$namentry) {
    echo '
      var submit_to = "'. url_for(array(
        'resource'=>'settings',
        'id'=>$nammode->id,
        'action'=>'put'
      )).'";

      var submit_to = "'. $namurl.'";

      $(".jeditable_'.$nam.'").mouseover(function() {
          $(this).highlightFade({end:\'#def\'});
      });
      $(".jeditable_'.$nam.'").mouseout(function() {
          $(this).highlightFade({end:\'#fff\', speed:200});
      });
      $(".jeditable_'.$nam.'").editable(submit_to, {
          indicator   : "<img src=\''. base_path().'resource/jeditable/indicator.gif\'>",
          submitdata  : function() {
            return {"entry[etag]" : "'.$namentry->etag.'"};
          },
          name        : "setting[value]",
          type        : "textarea",
          noappend    : "true",
          submit      : "OK",
          tooltip     : "Click to edit...",
          cancel      : "Cancel",
          callback    : function(value, settings) {
            return(value);
          }
      });  ';

  
};

function setting_widget_helper($nam,$nammode,$namurl,$namentry) {
  
  echo '
      var submit_to = "'. url_for(array(
        'resource'=>'settings',
        'id'=>$nammode->id,
        'action'=>'put'
      )).'";

      var submit_to = "'. $namurl.'";

      $(".editable_select_'.$nam.'_text").mouseover(function() {
          $(this).highlightFade({end:\'#def\'});
      });
      $(".editable_select_'.$nam.'_text").mouseout(function() {
          $(this).highlightFade({end:\'#fff\', speed:200});
      });
      $(".editable_select_'.$nam.'_text").editable(submit_to, {
          indicator   : "<img src=\''. base_path().'resource/jeditable/indicator.gif\'>",
             data     : \'';
     if (!class_exists("Services_JSON")) lib_include("json"); $json = new Services_JSON(); echo $json->encode( $aktwitter_tw_text_options ); 
     echo '\',
          submitdata  : function() {
            return {"entry[etag]" : "'.$namentry->etag.'"};
          },
          name        : "setting[value]",
          type        : "select",
          placeholder : "'.$nammode->value.'",
          noappend    : "true",
          submit      : "OK",
          tooltip     : "Click to edit...",
          cancel      : "Cancel",
          callback    : function(value, settings) {
            $(this).html(settings[\'jsonarr\'][value-0]);
            return(value);
          }
      });  ';
  
}

