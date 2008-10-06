<?php

class Setting extends Model {
  
  function Setting() {
    
    $this->time_field( 'created' );
    $this->time_field( 'modified' );
    
    $this->char_field( 'name' );
    $this->char_field( 'value' );
    
    $this->int_field( 'entry_id' );
    $this->int_field( 'person_id' );
    $this->int_field( 'profile_id' );
    
    $this->auto_field( 'id' );
    
    $this->has_one( 'entry' );
    
    $this->let_access( 'all:everyone' );
    
    $this->set_hidden();
    
  }
  
}

?>