<?php

class Url extends Model {
  
  function Url() {

    $this->char_field( 'id', 255 ); // << shorturl code
    $this->validates_uniqueness_of('id');
    
    $this->text_field( 'url' );
    $this->time_field( 'date' );
    $this->int_field( 'text' );
    
    // trimclone
    $this->char_field( 'trimurl', 255 );
    $this->char_field( 'trimpath', 255 );
    $this->char_field( 'trimref', 255 );
    $this->char_field( 'trimmed', 255 );
    $this->char_field( 'trimvisits', 255 );
    $this->char_field( 'trimtime', 255 );
    
    $this->char_field( 'title' );
    
    $this->text_field( 'description' );
    
    $this->char_field( 'type' ); // feed or page
    $this->char_field( 'href' ); // html link
    $this->char_field( 'xref' ); // xml link
    $this->char_field( 'rel' );
    $this->char_field( 'version' );
    
    $this->time_field( 'created' );
    $this->time_field( 'modified' );
    
    $this->int_field( 'entry_id' );

    $this->auto_field( 'recid' );
    
    $this->set_primary_key('recid');
    
    // relationships
    
    $this->has_one( 'entry' );
    
    // permissions
    
    $this->let_access( 'all:administrators' );
    
  }
  
}


