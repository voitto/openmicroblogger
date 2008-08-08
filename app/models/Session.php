<?php

class Session extends Model {
  
  function Session() {
    
    $this->char_field( 'id' );
    
    $this->int_field( 'access' );
    $this->text_field( 'data' );
    
    $this->int_field( 'person_id' );
    
    $this->set_primary_key( 'id' );
    
    $this->set_hidden();
    
  }
  
}

?>