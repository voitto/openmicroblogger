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
    
    $this->set_limit(100);
    $this->find();
    $found = array();
    while ($rec = $this->MoveNext())
	    $found[] = $rec->name;

    $Grp = $this->base();
    $Grp->set_value( 'name', 'everyone' );
    if (!(in_array($Grp->attributes['name'],$found)))
      $Grp->save_changes();
        
    $Grp = $this->base();
    $Grp->set_value( 'name', 'administrators' );
    if (!(in_array($Grp->attributes['name'],$found)))
      $Grp->save_changes();
    
    $Grp = $this->base();
    $Grp->set_value( 'name', 'members' );
    if (!(in_array($Grp->attributes['name'],$found)))
      $Grp->save_changes();
    
  }
  
}

?>