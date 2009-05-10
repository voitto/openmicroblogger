<?php

$txt = array();

if (environment('lang'))
  $language_selected = environment('lang');
else
  $language_selected = STANDARD_LANG;

$language_file = 'wp-content/language/'.$language_selected.'.php';

if ( file_exists( $language_file )) {
  include $language_file;
} else {
  
  if (signed_in()) {
    $Setting =& $db->model('Setting');
    $lang = $Setting->find_by(array('name'=>'config.env.lang','profile_id'=>get_profile_id()));
    
    $txt = unserialize($lang->data);
  }

}

if (!(count($txt) > 0))
  include 'wp-content/language/eng.php';


