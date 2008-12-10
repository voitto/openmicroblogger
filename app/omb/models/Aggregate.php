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
    
    $this->let_read( 'all:always' );
    
    $this->let_access( 'all:administrators' );
    
  }
  
  function init() {
    
    $Cat = $this->base();
    $Cat->set_value( 'name', 'Posts' );
    $Cat->set_value( 'term', 'posts' );
    $Cat->set_value( 'scheme', 'minifeed' );
    $Cat->save_changes();

    $Cat = $this->base();
    $Cat->set_value( 'name', 'Tweets' );
    $Cat->set_value( 'term', 'tweets' );
    $Cat->set_value( 'scheme', 'minifeed' );
    $Cat->save_changes();
    
  }
  
}


