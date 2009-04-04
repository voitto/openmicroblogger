<?php

class Review extends Model {
  
  function Review() {
    
    // fields
    
    $this->char_field( 'version' );
    $this->char_field( 'summary' );
    
    $this->char_field( 'item_type' );
    
    // item_type valuse -- product/business/event/person/place/website/url
    
    $this->char_field( 'item_info' );
    
    // item_info values -- fn_url/fn_photo/hCard/hCalendar
    
    $this->int_field( 'profile_id' );
    
    $this->char_field( 'reviewer' );
    $this->char_field( 'dtreviewed' );
    
    $this->int_field( 'rating' );
    
    $this->text_field( 'description' );
    
    $this->char_field( 'tags' );
    $this->char_field( 'permalink' );
    $this->char_field( 'license' );
    
    $this->time_field( 'created' );
    $this->time_field( 'modified' );
    
    $this->int_field( 'target_id' );
    
    $this->int_field( 'entry_id' );
    
    $this->auto_field( 'id' );
    
    // relationships
    
    $this->has_one( 'entry' );
    
    // permissions
    
    $this->let_read( 'all:everyone' );
    $this->let_create( 'all:members' );
    $this->let_access( 'all:administrators' );
    
    $this->set_hidden();
    
  }
  
}

?>