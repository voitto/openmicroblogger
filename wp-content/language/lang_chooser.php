<?php

$txt = array();

if (setting('lang'))
  $language_selected = setting('lang');
else
  $language_selected = STANDARD_LANG;

$language_file = 'wp-content/language/'.$language_selected.'.php';

if ( file_exists( $language_file )) {
  
  include $language_file;
  
} else {
  
  if (signed_in()) {
    $Setting =& $db->model('Setting');
    $lang = $Setting->find_by(array('name'=>'lang','profile_id'=>get_profile_id()));
    if ($lang)
      $txt = unserialize($lang->data);
  }
  
}

if (!(count($txt) > 10))
  include 'wp-content/language/eng.php';


