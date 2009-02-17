<?php 

// data model for the auctions table

class Auction extends Model {
  
  function Auction() {
    
    $this->auto_field( 'id' );
    $this->int_field( 'entry_id' );
    $this->text_field( 'headline' );
    $this->text_field( 'body' );
    $this->text_field( 'close' );
    
    $this->char_field( 'href' );
    
    $this->set_primary_key( 'id' );
    
    $this->has_one( 'entry' );
    
    $this->has_many( 'auction_bullets.auction_id' );
    $this->has_many( 'auction_photos.auction_id' );
    
    $this->has_one('entry');
    
    $this->let_access( 'all:administrators' );
    
    $this->let_read( 'all:everyone' );
    $this->let_create( 'all:members' );
    $this->let_delete( 'all:members' );
    
  }

}

function auction_show() {
  // profile action
  app_profile_show( 'auctions', 'list.html' );
}

function auction_init() {
  // admin action
  app_register_init( 'auctions', 'index.html', 'Auctions', 'auction', 2 );
}

?>
