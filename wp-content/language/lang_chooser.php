<?php

$txt = array();

if (setting('lang'))
  $language_selected = setting('lang');
else
  $language_selected = STANDARD_LANG;

$language_file = 'wp-content/language/'.$language_selected.'.php';

if ( file_exists( $language_file )) {
  
  include $language_file;

} elseif ( file_exists( 'wp-content/language/import/'.$language_selected.'.php' )) {
  
  $data = split("\n", file_get_contents('wp-content/language/import/'.$language_selected.'.php'));
  foreach($data as $val)
    if (substr(trim($val),-1) == '}')
      $data = trim($val);
  $txt = mb_unserialize($data);
  
} else {
  
  global $db;
  $Translation =& $db->model('Translation');
  $lang = $Translation->find_by('code',$language_selected);
  if ($lang)
    $txt = mb_unserialize($lang->data);
  
}

if (!(count($txt) > 1))
  include 'wp-content/language/eng.php';


