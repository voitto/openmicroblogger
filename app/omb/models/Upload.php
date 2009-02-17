<?php

class Upload extends Model {
  
  function Upload() {
    
    $this->char_field( 'name' );
    
    $this->char_field( 'tmp_name' );

    $this->auto_field( 'id' );
    
  }
  
}

?>