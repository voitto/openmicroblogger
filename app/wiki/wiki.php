<?php

global $prefix;

 function wiki_init() {
   include 'wp-content/language/lang_chooser.php'; //Loads the language-file
   // app_register_init( table, action, apptitle, appname, number )
     app_register_init( 'wikis', 'index', 'Wiki', 'wiki', 2 );
 }

 function wiki_show() {
 }

 function wiki_head() {
 }

 function wiki_menu() {
 }

 function wiki_post() {
 }

