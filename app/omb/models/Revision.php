<?php

class Revision extends Model {
  
  function Revision() {
    
    $this->text_field( 'data' );
    
    $this->time_field( 'created' );
    
    $this->time_field( 'modified' );

    $this->int_field( 'target_id' );
    
    $this->int_field( 'profile_id' );
    
    $this->int_field( 'entry_id' );
    
    $this->auto_field( 'id' );
    
    $this->has_one( 'entry' );

    $this->set_limit(10);
    
  }
  
}

