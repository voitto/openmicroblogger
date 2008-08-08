<?php

class Subscription extends Model {
  
  function Subscription() {
    
    $this->time_field( 'created' );
    $this->time_field( 'modified' );
    
    $this->int_field( 'subscriber' );
    $this->int_field( 'subscribed' );
    
    $this->char_field( 'token' );
    $this->char_field( 'secret' );
    
    $this->int_field( 'entry_id' );
    $this->int_field( 'person_id' );
        
    $this->auto_field( 'id' );
    
    $this->set_hidden();
    
  }
  
}

?>