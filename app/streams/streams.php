<?php

global $prefix;

if (empty($prefix)) {
 
  function streams_init() {
    // app_register_init( table, action, apptitle, appname, number )
    app_register_init( 'blogs', 'mystreams', 'Streams', 'streams', 2 );
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