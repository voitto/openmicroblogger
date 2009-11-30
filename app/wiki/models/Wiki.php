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
		  'id'=>$w->id,
		  'action'=>'entry'
		));
		if (!empty($w->prefix)){
    $result = $db->get_result( "SELECT title,id FROM ".$w->prefix."_wiki_pages WHERE parent_id = ".$w->id );
   if ($result)
	  while ( $row = $db->fetch_array( $result ) ) {
			$request->connect( str_replace(' ','',$row['title']), array(
			  'resource'=>'wikis',
			  'id'=>$w->id,
			  'action'=>'entry',
			  'wikipage_id'=>$row['id']
			));
	    //$row['id']
	  }
    }
	}
	$WikiPage =& $db->model('WikiPage');
	$WikiPage->set_limit(100);
	$WikiPage->find();
	while ($w = $WikiPage->MoveNext()){
		$request->connect( str_replace(' ','',$w->title), array(
		  'resource'=>'wiki_pages',
		  'id'=>$w->id,
		  'action'=>'entry',
		));
	}
	$wiki_urls_loaded = 'done';
}


after_filter('get_blog_for_wiki','routematch');

function get_blog_for_wiki(&$request,&$route) {
  
  if (!in_array($request->resource,array('wiki_pages','wikis')))
    return;

  if (!$request->id)
    return;
 
  global $prefix;
  if (!empty($prefix))
    return;
  
  $wiki_prefix = false;
 
  global $db;

	$Blog =& $db->model('Blog');

	if ($request->resource == 'wikis'){
		$Wiki =& $db->model('Wiki');
		$w = $Wiki->find($request->id);
		if ($w->prefix)
		  $wiki_prefix = $w->prefix."_";
	}

	if ($wiki_prefix){
		$id = false;
//		$result = $db->get_result( "SELECT id FROM ".$w->prefix."_wiki_pages WHERE title like '".$request->params[0] ."'");
   if (isset($request->params['wikipage_id'])) {
  $prefix = $wiki_prefix;
  $db->prefix = $prefix;
  $request->set_param( 'resource', 'wiki_pages' );
  $request->set_param( 'id', $request->params['wikipage_id'] );
}
}
    
	
}


after_filter( 'do_realtime_revision', 'save_record' );

function do_realtime_revision( &$rec, &$db ) {
  if (!($rec->table == 'wiki_pages'))
    return;
  $owner = get_profile();
  //$owner = owner_of($rec);
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



