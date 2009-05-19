<?php 

// data model for the translations table

class Translation extends Model {
  
  function Translation() {
    
    // key
    $this->auto_field( 'id' );
    
    // fields
    $this->char_field( 'name' );
    $this->char_field( 'code' );
    $this->text_field( 'data' );
    
    // metadata
    $this->int_field( 'entry_id' );
    $this->has_one( 'entry' );
    
    // administrators
    $this->let_access( 'all:administrators' );
    
    // users
    $this->let_read( 'all:everyone' );
    $this->let_create( 'all:members' );
    $this->let_modify( 'all:members' );
    $this->let_delete( 'all:members' );
    
  }

}

function translate_show() {
  // profile action
  //app_profile_show( 'auctions', 'list.html' );
}

function translate_init() {
  include 'wp-content/language/lang_chooser.php'; //Loads the language-file  
  app_register_init( 'translations', 'index.html', $txt['translation_translate'], 'translate', 2 );
}

?>
