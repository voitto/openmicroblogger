<?php

global $prefix;

if (empty($prefix)) {
 
  function pages_init() {
include 'wp-content/language/lang_chooser.php'; //Loads the language-file
    // app_register_init( table, action, apptitle, appname, number )
    if (member_of('administrators'))
      app_register_init( 'pages', 'pagelist', 'Pages', 'pages', 2 );
  }

  function pages_show() {
  }

  function pages_head() {
  }

  function pages_menu() {
  }

  function pages_post() {
  }

}