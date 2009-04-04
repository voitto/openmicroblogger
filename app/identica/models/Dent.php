<?php 

// data model for the dents table

class Dent extends Model {

  function Dent() {
    
    $this->set_param('table','dents');
    
    // data dictionary
    $this->auto_field( 'id' );
    $this->char_field( 'tw_id' );
    $this->char_field( 'tw_text' );
    $this->time_field( 'tw_created_at' );
    $this->time_field( 'modified' );
    
    // primary key
    $this->set_primary_key( 'id' );
    
    // permissions for this resource
    $this->let_read( 'all:everyone' );
    
  }

}


?>
