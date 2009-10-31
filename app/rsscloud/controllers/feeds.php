<?php


function get( &$vars ) {
  extract( $vars );
  switch ( count( $collection->members )) {
    case ( 1 ) :
      if ($request->id && $request->entry_url())
        render( 'action', 'entry' );
    default :
      render( 'action', 'index' );
  }
}


function post( &$vars ) {
  ini_set('display_errors','1');
  ini_set('display_startup_errors','1');
  error_reporting (E_ALL & ~E_NOTICE );
  extract( $vars );
  $feeds = array();
  $readinglist_description = '';
  $readinglist_title = '';
  $list_id = 0;
  if (isset($_FILES['opmlfile']['tmp_name'])){
	  $buf = file_get_contents($_FILES['opmlfile']['tmp_name']);
	  $xml = new SimpleXmlElement($buf);
	  foreach($xml as $k=>$v){
		  foreach($v as $a=>$b){
				if ($a == 'title')
				  $readinglist_title = (string)$b;
				if ($a == 'outline'){
				  foreach($b as $b2){
					  $thisfeed = array(
							'xmlUrl'=>'',
							'htmlUrl'=>'',
							'text'=>'',
							'type'=>'',
					    'description'=>'',
					    'email'=>''
						);
		        foreach($b2->attributes() as $a3 => $b3) {
		          if ($a3 == 'xmlUrl')
		            $thisfeed['xmlUrl'] = (string)$b3;
		          if ($a3 == 'htmlUrl')
		            $thisfeed['htmlUrl'] = (string)$b3;
		          if ($a3 == 'text')
		            $thisfeed['text'] = (string)$b3;
		          if ($a3 == 'type')
		            $thisfeed['type'] = (string)$b3;
		      	}
		        if (!empty($thisfeed['xmlUrl']))
		  				$feeds[$thisfeed['xmlUrl']] = $thisfeed;
		        elseif (!empty($thisfeed['text']))
		          $readinglist_description .= " " . $thisfeed['text'];
					}
				}
			}
	  }
	  $ReadingList =& $db->model('ReadingList');
	  $list = $ReadingList->base();
	  $list->set_value('description',$readinglist_description);
	  $list->set_value('title',$readinglist_title);
	  $list->save();
  	$list_id = $list->id;
	} elseif (isset($_POST['rss_follow'])) {
		$thisfeed = array();
    $thisfeed['xmlUrl'] = $_POST['rss_follow'];
    $thisfeed['htmlUrl'] = $_POST['rss_link'];
    $thisfeed['text'] = $_POST['rss_title'];
    $thisfeed['type'] = 'rss';
  	$feeds[$thisfeed['xmlUrl']] = $thisfeed;
	} else{
		trigger_error('no feeds found', E_USER_ERROR);
	}
  $Feed =& $db->model('Feed');
  $Subscription =& $db->model('Subscription');
	foreach($feeds as $f){
		$fd = $Feed->find_by('xref',$f['xmlUrl']);
    if (!$fd){
	    $fd = $Feed->base();
	    $fd->set_value('xref',$f['xmlUrl']);
	    $fd->set_value('href',$f['htmlUrl']);
	    $fd->set_value('title',$f['text']);
	    $fd->set_value('type',$f['type']);
			$buf = readURL($f['xmlUrl']);
			$xml = new SimpleXmlElement($buf);
			foreach($xml as $k1=>$v1){
	      foreach($v1 as $k2=>$v2){
	        if ($k2 == 'description')
		        $f['description'] = (string)$v2;
	        if ($k2 == 'webMaster')
		        $f['email'] = (string)$v2;
	        if ($k2 == 'managingEditor')
		        $f['email'] = (string)$v2;
	        if ($k2 == 'cloud') {
		        foreach($v2->attributes() as $k4 => $v4) {
		          if ($k4 == 'domain')
		            $f['domain'] = (string)$v4;
		          if ($k4 == 'port')
		            $f['port'] = (string)$v4;
		          if ($k4 == 'path')
		            $f['path'] = (string)$v4;
		          if ($k4 == 'registerProcedure')
		            $f['registerProcedure'] = (string)$v4;
		          if ($k4 == 'protocol')
		            $f['protocol'] = (string)$v4;
	          }
	        }
				}
			}
  		if (isset($f['domain'])){
		    $fd->set_value('cloud_domain',$f['domain']);
		    $fd->set_value('cloud_port',$f['port']);
		    $fd->set_value('cloud_path',$f['path']);
		    $fd->set_value('cloud_function',$f['registerProcedure']);
		    $fd->set_value('cloud_protocol',$f['protocol']);
			}
	    $fd->set_value('description',$f['description']);
			$fd->set_value('email',$f['email']);
			$nickname = '';
			$letters = str_split(strtolower($f['text']));
			foreach ($letters as $letter)
		    if (ereg("([a-z])", $letter))
		      $nickname .= $letter;
			$default = "http://openmicroblogger.com/resource/feed16.png";
			$size = 40;
			$grav_url = "http://www.gravatar.com/avatar.php?
			gravatar_id=".md5( strtolower($f['email']) ).
			"&default=".urlencode($default).
			"&size=".$size;
		  $location = '';
			$i = make_identity(array(
				$nickname,
				$grav_url,
				$f['text'],
				$f['description'],
				$f['htmlUrl'],
				$location
			),true);
			if ($i){
				$fd->set_value('profile_id',$i->id);
				$fd->set_value('reading_list_id',$list_id);
	  		$fd->save_changes();
        $s = $Subscription->base();
        $s->set_value( 'subscriber', get_profile_id() );
        $s->set_value( 'subscribed', $i->id );
        $s->save_changes();
        $s->set_etag(get_person_id());
			}
		}
		if (isset($f['domain']) && !empty($_POST['rss_follow'])){
			$subscribe_url = "http://" . $f['domain'] . ":" . $f['port'] . "" . $f['path'] . "";
			$params = array(
				'notifyProcedure'=>get_option('cloud_function'),
				'port'=>get_option('cloud_port'),
				'path'=>'/api/rsscloud/callback',
				'protocol'=>get_option('cloud_protocol'),
				'url1'=>$f['xmlUrl'],
				'domain'=>get_option('cloud_domain')
			);
	    require_once(ABSPATH.WPINC.'/class-snoopy.php');
			$snoop = new Snoopy;
				$snoop->submit(
					$subscribe_url,
					$params
				);
			admin_alert("rssCloud follow: " . $f['title']);
		}
	}
  //$resource->insert_from_post( $request );
  header_status( '201 Created' );
  redirect_to( $request->resource );
}


function put( &$vars ) {
  extract( $vars );
  $resource->update_from_post( $request );
  header_status( '200 OK' );
  redirect_to( $request->resource );
}


function delete( &$vars ) {
  extract( $vars );
  $resource->delete_from_post( $request );
  header_status( '200 OK' );
  redirect_to( $request->resource );
}


function _doctype( &$vars ) {
  // doctype controller
}



function index( &$vars ) {
  extract( $vars );
  $theme = environment('theme');
  $blocks = environment('blocks');
  $atomfeed = $request->feed_url();
  return vars(
    array(
      &$blocks,
      &$profile,
      &$collection,
      &$atomfeed,
      &$theme
    ),
    get_defined_vars()
  );
}


function _index( &$vars ) {
  // index controller returns
  // a Collection of recent entries
  extract( $vars );
  return vars(
    array( &$collection, &$profile ),
    get_defined_vars()
  );
}


function _entry( &$vars ) {
  // entry controller returns
  // a Collection w/ 1 member entry
  extract( $vars );
  $Member = $collection->MoveNext();
  $Entry = $Member->FirstChild( 'entries' );
  return vars(
    array( &$collection, &$Member, &$Entry, &$profile ),
    get_defined_vars()
  );
}


function _new( &$vars ) {
  extract( $vars );
  $model =& $db->get_table( $request->resource );
  $Member = $model->base();
  return vars(
    array( &$Member, &$profile ),
    get_defined_vars()
  );
}

function _import( &$vars ) {
  extract( $vars );
  $model =& $db->get_table( $request->resource );
  $Member = $model->base();
  return vars(
    array( &$Member, &$profile ),
    get_defined_vars()
  );
}


function _edit( &$vars ) {
  extract( $vars );
  $Member = $collection->MoveFirst();
  $Entry = $Member->FirstChild( 'entries' );
  return vars(
    array( &$Member, &$Entry, &$profile ),
    get_defined_vars()
  );
}


function _remove( &$vars ) {
  extract( $vars );
  $Member = $collection->MoveFirst();
  $Entry = $Member->FirstChild( 'entries' );
  return vars(
    array( &$Member, &$Entry, &$profile ),
    get_defined_vars()
  );
}


function _block( &$vars ) {

  extract( $vars );
  return vars(
    array(
      &$Entry,
      &$collection
    ),
    get_defined_vars()
  );

}

