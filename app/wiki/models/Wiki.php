<?php

class Wiki extends Model {
  
  function Wiki() {
    
    // fields
    
    $this->char_field( 'title' );
    $this->char_field( 'prefix', 2 );
    $this->char_field( 'nickname' );

    $this->char_field( 'password' );
    
    $this->time_field( 'created' );
    $this->time_field( 'modified' );
    
    $this->int_field( 'profile_id' );
    $this->int_field( 'blog_id' );

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


after_filter( 'do_realtime_revision', 'save_record' );

function do_realtime_revision( &$rec, &$db ) {
  if (!($rec->table == 'wiki_pages'))
    return;
  $owner = owner_of($rec);
	$html = '<li><img width="20" height="20" src="'.$owner->avatar.'"><span>&nbsp;<a href="'.$owner->profile_url.'">'.$owner->fullname.'</a> </span>
	<a style="font-size:85%" href=""></a></li>';
	realtime(
	  'page_update',
	  array(
	    'div'=>'revisions',
	    'html'=>$html
	  ), 
	  lookup_wiki_prefix($rec->parent_id)
	);
}


after_filter( 'do_realtime_page_update', 'update_from_post' );

function do_realtime_page_update( &$model, &$rec ) {

  global $request,$db;

  if (!($rec->table == 'wiki_pages'))
    return;

  $blogprefix = lookup_wiki_prefix($rec->parent_id);

  $changed = false;

	if (isset($request->params['wikipage']['header']))
	  $changed = 'header';

	if (isset($request->params['wikipage']['body']))
	  $changed = 'body';

	if (isset($request->params['wikipage']['footer']))
	  $changed = 'footer';
	
	if ($changed)
    realtime(
	    'page_update',
      array(
	      'div'=>$changed,
	      'html'=>$rec->$changed
  	  ), 
      $blogprefix
    );
   
}



