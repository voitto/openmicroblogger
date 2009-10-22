<?php

class Feed extends Model {
  
  function Feed() {

    $this->text_field( 'xref' );
    $this->text_field( 'href' );
    $this->text_field( 'title' );
    $this->text_field( 'type' );

    $this->text_field( 'description' );
    $this->text_field( 'email' );

    $this->int_field( 'reading_list_id' );
    $this->int_field( 'day_of_sub' );

    $this->bool_field( 'cloud' );

    $this->text_field( 'cloud_domain' );
    $this->text_field( 'cloud_port' );
    $this->text_field( 'cloud_path' );
    $this->text_field( 'cloud_function' );
    $this->text_field( 'cloud_protocol' );

    $this->time_field( 'lastBuildDate' );
    $this->time_field( 'pubDate' );

    $this->int_field( 'profile_id' );

    $this->time_field( 'created' );
    $this->time_field( 'modified' );
    $this->int_field( 'entry_id' );
    $this->auto_field( 'id' );
    
    // relationships
    
    $this->has_one( 'entry' );
    
    // permissions
    
    $this->let_read( 'all:members' );
    $this->let_access( 'all:administrators' );
    
  }
  
}


