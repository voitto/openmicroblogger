<?php



//    $path = "app" . DIRECTORY_SEPARATOR . "facebook" . DIRECTORY_SEPARATOR . "Services";
//    add_include_path( $path ); 
//    require_once "Services/Facebook.php";
//    Services_Facebook::$apiKey = '';
//    Services_Facebook::$secret = '';



function facebook_init() {
  include 'wp-content/language/lang_chooser.php'; //Loads the language-file  
  // set the resource, action, button label, app name, grouplevel-unimplemented
  app_register_init( 'facebook_users', 'edit.html', $txt['facebook_facebook'], 'facebook', 2 );
  
  //before_filter('download_tweets','get');
}


