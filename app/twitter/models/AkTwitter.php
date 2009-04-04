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
    $this->char_field( 'tw_reply_username' );
    $this->char_field( 'tw_reply_tweet' );
    $this->time_field( 'tw_created_at' );
    $this->time_field( 'modified' );
    $this->int_field( 'profile_id' );
    $this->int_field( 'entry_id' );
    
    $this->has_one('entry');
    
    $this->has_and_belongs_to_many('identities');
    
    // primary key
    $this->set_primary_key( 'id' );
    
    // permissions for this resource
    $this->let_read( 'all:everyone' );
    
  }

}


?>