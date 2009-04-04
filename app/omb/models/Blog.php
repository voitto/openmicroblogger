<?php

class Blog extends Model {
  
  function Blog() {
    
    // fields
    
    $this->char_field( 'title' );
    $this->char_field( 'prefix', 2 );
    $this->char_field( 'nickname' );
    
    $this->time_field( 'created' );
    $this->time_field( 'modified' );
    
    $this->int_field( 'entry_id' );
    
    $this->auto_field( 'id' );
    
    // relationships
    
    $this->has_one( 'entry' );
    
    // permissions
    
    $this->let_access( 'all:administrators' );
    
    $this->validates_uniqueness_of( 'prefix' );
    $this->validates_uniqueness_of( 'nickname' );
    
  }
  
}

?>