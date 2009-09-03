<?php

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


