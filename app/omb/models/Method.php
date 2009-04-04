<?php 



class Method extends Model {
  
  function Method() {
    
    $this->auto_field( 'id' );
    
    $this->int_field( 'entry_id' );
    $this->int_field( 'oauth' );
    $this->int_field( 'http' );
    
    $this->char_field( 'function' );
    $this->char_field( 'route' );
    $this->char_field( 'resource' );
    $this->char_field( 'permission' );
    
    $this->text_field( 'code' );
    
    $this->has_one( 'entry' );
    
    $this->let_access( 'all:administrators' );
    
  }

}

