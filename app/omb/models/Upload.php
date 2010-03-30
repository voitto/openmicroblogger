<?php

class Upload extends Model {
  
  function Upload() {
    
    $this->char_field( 'name' );
    
    $this->char_field( 'tmp_name' );

    $this->auto_field( 'id' );
    
    $this->file_field( 'attachment' );

    $this->int_field( 'parent_id' );

    $this->int_field( 'profile_id' );

    $this->char_field( 'title' );

    $this->int_field( 'target_id' );

  }
  
}

?>