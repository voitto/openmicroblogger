<?php 

// data model for the ak_twitter table

class AkTwitter extends Model {

  function AkTwitter() {
    
    // required for non-plural tables
    $this->set_param('table','ak_twitter');
    
    // data dictionary
    $this->auto_field( 'id' );
    $this->char_field( 'tw_id' );
    $this->char_field( 'tw_text' );
    $this->time_field( 'tw_created_at' );
    $this->time_field( 'modified' );
    
    // primary key
    $this->set_primary_key( 'id' );
    
    // permissions for this resource
    $this->let_read( 'all:always' );
    
  }

}


?>
