<?php

class Group extends Model {
  
  function Group() {
    
    // fields
    
    $this->char_field( 'name' );
    
    $this->int_field( 'entry_id' );
    $this->int_field( 'person_id' );
        
    $this->auto_field( 'id' );
    
    // relationships
    
    $this->has_one( 'entry' );
    $this->has_one( 'person' );
    
    $this->has_many( 'memberships' );
    
    // permissions
    
    $this->let_access( 'all:administrators' );
    
    #$this->set_hidden();
    
  }
  
  function init() {
    
    $Grp = $this->base();
    $Grp->set_value( 'name', 'everyone' );
    $Grp->save_changes();
        
    $Grp = $this->base();
    $Grp->set_value( 'name', 'administrators' );
    $Grp->save_changes();
    
    $Grp = $this->base();
    $Grp->set_value( 'name', 'members' );
    $Grp->save_changes();
    
  }
  
}

?>