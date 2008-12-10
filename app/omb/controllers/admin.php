<?php



function index( &$vars ) {
  extract( $vars );
  return vars(
    array( 
      &$collection,
      &$profile
    ),
    get_defined_vars()
  );
}


function _index( &$vars ) {
  // index controller returns
  // a Collection of recent entries
  extract( $vars );
  return vars(
    array( 
      &$collection,
      &$profile
    ),
    get_defined_vars()
  );
}



