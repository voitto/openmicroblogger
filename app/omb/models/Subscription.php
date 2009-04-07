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
    
    $this->bool_field( 'sms' );
    $this->bool_field( 'email' );
    $this->bool_field( 'omb' );
    $this->bool_field( 'twitter' );
    
    $this->has_one( 'entry' );
    $this->has_one( 'person' );
    
    $this->let_read( 'all:remove' );
    $this->let_modify( 'all:members' );
    $this->let_delete( 'all:members' );
    
    $this->set_limit(100);
    
    $this->set_hidden();
    
  }
  
}

?>