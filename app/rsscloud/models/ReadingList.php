<?php

class ReadingList extends Model {
  
  function ReadingList() {

    $this->text_field( 'title' );
    $this->text_field( 'description' );

    $this->int_field( 'profile_id' );

    $this->time_field( 'created' );
    $this->time_field( 'modified' );

    $this->int_field( 'entry_id' );
    $this->auto_field( 'id' );
    
    // relationships
    
    $this->has_one( 'entry' );
    
    // permissions
    
    $this->let_read( 'all:members' );
    $this->let_delete( 'all:members' );
    $this->let_access( 'all:administrators' );
    
  }
  
}


