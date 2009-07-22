<?php

class Thumbnail extends Model {
  
  function Thumbnail() {
    
    // fields
    
    $this->file_field( 'attachment' );
    
    $this->time_field( 'created' );
    $this->time_field( 'modified' );
    
    $this->int_field( 'target_id' );
    
    $this->int_field( 'entry_id' );
    
    $this->auto_field( 'id' );
    
    // relationships
    
    $this->has_one( 'entry' );
    
    // permissions
    
    $this->let_read( 'all:everyone' );
    $this->let_access( 'all:administrators' );
    
    $this->set_hidden();
    
  }
  
}

?>