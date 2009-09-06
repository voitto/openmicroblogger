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
  global $db;
  $Translation =& $db->model('Translation');
  $lang = $Translation->find_by('code',$language_selected);
  if ($lang)
    $txt = mb_unserialize($lang->data);
  
}

if (!(count($txt) > 1))
  include 'wp-content/language/eng.php';


