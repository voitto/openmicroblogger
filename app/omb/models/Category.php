<?php

class Category extends Model {
  
  function Category() {
    
    // fields
    
    $this->set_limit(100);
    
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
    
    $Cat = $this->base();
    $Cat->set_value( 'name', 'Art' );
    $Cat->set_value( 'term', 'art' );
    $Cat->save_changes();

    $Cat = $this->base();
    $Cat->set_value( 'name', 'Craft' );
    $Cat->set_value( 'term', 'craft' );
    $Cat->save_changes();

    $Cat = $this->base();
    $Cat->set_value( 'name', 'Food' );
    $Cat->set_value( 'term', 'food' );
    $Cat->save_changes();

    $Cat = $this->base();
    $Cat->set_value( 'name', 'Home' );
    $Cat->set_value( 'term', 'home' );
    $Cat->save_changes();

    $Cat = $this->base();
    $Cat->set_value( 'name', 'Life' );
    $Cat->set_value( 'term', 'life' );
    $Cat->save_changes();

    $Cat = $this->base();
    $Cat->set_value( 'name', 'Ride' );
    $Cat->set_value( 'term', 'ride' );
    $Cat->save_changes();

    $Cat = $this->base();
    $Cat->set_value( 'name', 'Tech' );
    $Cat->set_value( 'term', 'tech' );
    $Cat->save_changes();
    
  }
  
}

?>