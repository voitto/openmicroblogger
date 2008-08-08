<?php

class Person extends Model {
  
  function Person() {
    
    // fields
    
    $this->time_field( 'created' );
    $this->time_field( 'modified' );
    
    $this->auto_field( 'id' );
    
    // relationships
    
    $this->has_many( 'identities' );
    $this->has_many( 'memberships' );
    
    // permissions
    
    $this->set_hidden();
    
  }
  
}

?>