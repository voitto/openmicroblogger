<?php

class Aggregate extends Model {
  
  function Aggregate() {
    
    // fields
    
    $this->char_field( 'name' );
    $this->char_field( 'term' );
    $this->char_field( 'scheme' );
    
    $this->auto_field( 'id' );
    
    // relationships
    
    $this->has_and_belongs_to_many( 'entries' );
    
    // permissions
    
    $this->let_read( 'all:everyone' );
    
    $this->let_access( 'all:administrators' );
    
  }
  
  function init() {
    
  }
  
}


