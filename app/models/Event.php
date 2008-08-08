<?php

class Event extends Model {
  
  function Event() {
    
    // fields
    
    $this->char_field( 'category' );
    $this->char_field( 'class' );
    
    $this->text_field( 'description' );
    
    $this->time_field( 'dtend' );
    $this->time_field( 'dtstart' );
    
    $this->char_field( 'duration' );
    
    $this->char_field( 'location' );
    
    $this->char_field( 'street_address' );
    $this->char_field( 'locality' );
    $this->char_field( 'region' );
    $this->char_field( 'postal_code' );
    
    $this->char_field( 'status' );
    
    $this->text_field( 'summary' );
    
    $this->char_field( 'uid' );
    
    $this->char_field( 'url' );
    
    $this->int_field( 'entry_id' );
    
    $this->auto_field( 'id' );
    
    // relationships
    
    $this->has_one( 'entry' );
    
    // permissions
    
    $this->let_read( 'all:always' );
    $this->let_access( 'all:administrators' );
    
    $this->set_hidden();
    
  }
  
}

?>