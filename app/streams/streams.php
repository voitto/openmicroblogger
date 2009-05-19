<?php

global $prefix;

if (empty($prefix)) {
 
  function streams_init() {
include 'wp-content/language/lang_chooser.php'; //Loads the language-file
    // app_register_init( table, action, apptitle, appname, number )
    app_register_init( 'blogs', 'mystreams', $txt['streams_streams'], 'streams', 2 );
  }

  function streams_show() {
  }

  function streams_head() {
  }

  function streams_menu() {
  }

  function streams_post() {
  }

}