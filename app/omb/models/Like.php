<?php

class Like extends Model {
  
  function Like() {

    $this->char_field( 'fb_post_id' );
    $this->char_field( 'tw_post_id' );
    $this->char_field( 'post_id' );

    $this->int_field( 'entry_id' );
    
    $this->auto_field( 'id' );
    
  }
  
}


