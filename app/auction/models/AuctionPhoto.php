<?php 

// data model for the photos table

class AuctionPhoto extends Model {

  function AuctionPhoto() {
    
    $this->auto_field( 'id' );
    
    $this->int_field( 'entry_id' );
    
    $this->int_field( 'auction_id' );
    
    $this->file_field( 'photo' );
    
    $this->set_primary_key( 'id' );
    
    $this->has_one( 'entry' );
    
    $this->let_access( 'all:administrators' );
    
    $this->let_read( 'all:everyone' );
    $this->let_create( 'all:members' );
    $this->let_delete( 'all:members' );
    
  }

}



?>
