<?php

// do shortener redirect
// and set memcached 301

add_include_path(library_path().'urlshort/upload');
require_once 'includes/config.php'; // settings
require_once 'includes/gen.php'; // url generation and location
$perma = parse_url( $_SERVER['REQUEST_URI'] );
$_PERMA = explode( "/", $perma['path'] );
@array_shift( $_PERMA );
$url = new shorturl();
if ( isset($_PERMA[0]) )
	$id = mysql_escape_string($_PERMA[0]);
else
	$id = '';
if ( $id != '' && $id != basename($_SERVER['PHP_SELF']) ){
  echo $id; exit;
	$location = $url->get_url($id);
	if ( $location != -1 )	{
	  include 'db/library/pca/pca.class.php';
    $cache = PCA::get_best_backend();
    $timeout = 86400;
    $cache->add($_SERVER['REQUEST_URI'], $location, $timeout);
		header('Location: '.$location, TRUE, 301);
		exit;
	}
}

class Shortener extends Model {
  
  function Shortener() {
    
    $this->char_field( 'apikey' );
    $this->char_field( 'nickname' );
    $this->char_field( 'password' );
    $this->char_field( 'type' );
    $this->int_field( 'urlcount' );
    $this->int_field( 'hitcount' );
    $this->char_field( 'urlbase' );
    $this->char_field( 'endpoint' );
    
    $this->time_field( 'created' );
    $this->time_field( 'modified' );

    $this->int_field( 'profile_id' );
    
    $this->int_field( 'entry_id' );

    $this->auto_field( 'id' );
    
    // relationships
    
    $this->has_one( 'entry' );
    
    // permissions
    
    $this->let_access( 'all:administrators' );
    
  }
  
}


