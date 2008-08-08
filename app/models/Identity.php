<?php

class Identity extends Model {
  
  function Identity() {
    
    // identity is a Vcard/Hcard-compatible person profile
    
    // fields
    
    $this->char_field( 'label' );
    
    $this->char_field( 'url' );
    
    $this->char_field( 'post_notice' );
    $this->char_field( 'update_profile' );
    
    $this->char_field( 'license' );
    $this->char_field( 'bio' );
    $this->char_field( 'avatar' );
    $this->char_field( 'profile' );

    $this->char_field( 'fullname' );
    $this->char_field( 'family_name' );
    $this->char_field( 'given_name' );

    $this->char_field( 'nickname', 50 );
    $this->char_field( 'password', 100 );

    $this->file_field( 'photo' );
    
    $this->char_field( 'token', 20 );
    $this->char_field( 'email_value' );
    $this->char_field( 'locality', 100 );
    $this->char_field( 'region', 100 );
    $this->char_field( 'postal_code', 20 );
    $this->char_field( 'country_name', 2 );
    $this->char_field( 'latitude', 3 );
    $this->char_field( 'longitude', 3 );
    $this->char_field( 'tz', 3 );
    $this->char_field( 'dob', 8 );
    $this->char_field( 'gender', 1 );
    $this->char_field( 'language', 2 );
    
    $this->bool_field( 'is_primary', true );
    
    $this->int_field( 'entry_id' );
    $this->int_field( 'person_id' );
    
    $this->auto_field( 'id' );
    
    // relationships
    
    $this->has_one( 'entry' );
    $this->has_one( 'person' );
    
    // requirements
    
    $this->validates_presence_of( 'label' );
    
    $this->validates_uniqueness_of( 'url' );
    
    // permissions
    
    $this->let_read( 'all:entry' );
    $this->let_read( 'all:entry.jpg' );
    $this->let_read( 'all:entry.xrds' );
    // anyone can call up the edit form for any user -- hrm
    $this->let_read( 'all:edit' );
    // registered 'members' can modify their own records
    $this->let_modify( 'all:members' );
    // the first user is a member of 'administrators'
    $this->let_access( 'all:administrators' );
    
    $this->set_hidden();
    
    $this->set_blob('photo');
    
    $this->set_limit(500);
    
  }
  
}

?>