<?php

class Entry extends Model {
  
  function Entry() {
    
    // fields
    
    $this->char_field( 'resource' );
    $this->int_field( 'record_id' );
    
    $this->char_field( 'etag' );
    $this->char_field( 'content_type' );
    $this->time_field( 'expires' );
    
    $this->time_field( 'last_modified' );
    $this->time_field( 'issued' );
    
    $this->int_field( 'person_id' );
    
    $this->auto_field( 'id' );
    
    // relationships
    
    $this->has_and_belongs_to_many( 'categories' );
    
    $this->set_hidden();
    
  }
  
}

?>