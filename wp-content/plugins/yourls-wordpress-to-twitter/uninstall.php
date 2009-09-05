<?php
/*
Uninstall procedure (Removes the plugin cleanly in WP 2.7+)
*/

// Make sure that we are uninstalling
if ( !defined('WP_UNINSTALL_PLUGIN') ) {
    exit();
}

// Leave no trail
delete_option('ozh_yourls');

