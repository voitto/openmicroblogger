<?php

$language_selected = STANDARD_LANG;

$language_file = 'wp-content/language/'.$language_selected.'.php';

if ( file_exists( $language_file ))
  include $language_file;
else
  include 'wp-content/language/eng.php';


