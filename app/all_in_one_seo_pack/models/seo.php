<?php

$installed = environment('installed');

if (in_array('all_in_one_seo_pack',$installed))
  before_filter('setup_seo_app', 'render');

function setup_seo_app() {
  add_action( 'wp_head', 'get_posts_init' );
  wp_plugin_include( 'all-in-one-seo-pack' );
  load_plugin_textdomain( 'all_in_one_seo_pack', 'wp-content/plugins/all-in-one-seo-pack' );
}