<?php

class Wiki extends Model {
  
  function Wiki() {
    
    // fields
    
    $this->char_field( 'title' );
    $this->char_field( 'prefix', 2 );
    $this->char_field( 'nickname' );
    
    $this->time_field( 'created' );
    $this->time_field( 'modified' );
    
    $this->int_field( 'entry_id' );
    
    $this->auto_field( 'id' );
    
    // relationships
    
    $this->has_one( 'entry' );
    
    // permissions
    $this->let_read(    'all:everyone' );
    
    $this->let_create(  'all:members' );
    $this->let_write(   'all:members' );
    $this->let_delete(  'all:members' );
    
    $this->let_access( 'all:administrators' );
    
    $this->validates_uniqueness_of( 'prefix' );
    $this->validates_uniqueness_of( 'nickname' );
    
  }
  
}



before_filter('wiki_urls','load_plugin');

function wiki_urls(){
	global $wiki_urls_loaded;
	if ($wiki_urls_loaded == 'done')
	  return;
	global $request;
	global $db;
	$Wiki =& $db->model('Wiki');
	$Wiki->set_limit(100);
	$Wiki->find();
	while ($w = $Wiki->MoveNext()){
		$request->connect( str_replace(' ','',$w->title), array(
		  'resource'=>'wikis',
		  'id'=>$w->id
		));
	}
	$WikiPage =& $db->model('WikiPage');
	$WikiPage->set_limit(100);
	$WikiPage->find();
	while ($w = $WikiPage->MoveNext()){
		$request->connect( str_replace(' ','',$w->title), array(
		  'resource'=>'wiki_pages',
		  'id'=>$w->id
		));
	}
	$wiki_urls_loaded = 'done';
}


