<?php 

// data model for the auction_bullets table

class AuctionBullet extends Model {

  function AuctionBullet() {

    $this->auto_field( 'id' );
    $this->int_field( 'entry_id' );
    $this->int_field( 'auction_id' );
    $this->text_field( 'bullet' );

    $this->set_primary_key( 'id' );

    $this->has_one( 'entry' );

    $this->let_access( 'all:administrators' );

    $this->let_read( 'all:everyone' );
    $this->let_create( 'all:members' );
    $this->let_delete( 'all:members' );

  }

}



?>
