<?php
 

function password_init() {
  include 'wp-content/language/lang_chooser.php'; //Loads the language-file
  app_register_init( 'identities', 'pass', $txt['password_password'], 'password', 2 );
}

function password_show() {
}

function password_head() {
}

function password_menu() {
}

function password_post() {
}


