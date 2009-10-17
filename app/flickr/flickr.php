<?php





function flickr_init() {
  include 'wp-content/language/lang_chooser.php'; //Loads the language-file  

  app_register_init( 'flickr_users', 'edit.html', 'Connect to Flickr', 'flickr', 2 );
  
}

function flickr_status_disabled() {
	
}

function flickr_status_enabled() {

	global $db,$request;
	
	$Setting =& $db->model('Setting');
	
	$stat = $Setting->find_by(array('name'=>'flickr_frob','profile_id'=>get_profile_id()));
	
  if (!$stat)
    echo "<script type=\"text/javascript\">\ntop.location.href = \"".$request->url_for('flickr_login')."\";\n</script>";

}