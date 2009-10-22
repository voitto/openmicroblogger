<?php
if ( !function_exists( 'rsscloud_get_hub_notifications' ) ) {
	function rsscloud_get_hub_notifications( ) {
		return get_option( 'rsscloud_hub_notifications' );
	}
}

if ( !function_exists( 'rsscloud_update_hub_notifications' ) ) {
	function rsscloud_update_hub_notifications( $notifications ) {
		return update_option( 'rsscloud_hub_notifications', (array) $notifications );
	}
}
