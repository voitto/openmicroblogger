<?php

class Link extends Model {
  
  function Link() {
    
    // fields
    
    $this->char_field( 'title' );
    
    $this->text_field( 'description' );
    
    $this->char_field( 'type' ); // feed or page
    $this->char_field( 'href' ); // html link
    $this->char_field( 'xref' ); // xml link
    $this->char_field( 'rel' );
    $this->char_field( 'version' );
    
    $this->time_field( 'created' );
    $this->time_field( 'modified' );
    
    $this->int_field( 'entry_id' );
    
    $this->auto_field( 'id' );
    
    // relationships
    
    $this->has_one( 'entry' );
    
    // permissions
    
    $this->let_access( 'all:administrators' );
    
  }
  
}

?>