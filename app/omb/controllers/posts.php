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


function handle_twitter_cmdline(&$request){
  $commands = array(
	  'follow ',
	  'unfollow ',
	  'addtolist '
	);
  $parts = explode(" ",$request->params['post']['title']);
  $c = $parts[0]." ";
  $result = false;
  if (in_array($c,$commands)){
	  $c = trim($c)."_cmdfunc";
	  if (function_exists($c))
 	    $result = $c($parts);
  }
  return $result;
}

class RSSActivityHandler {
	  var $data;
	  var $new_item;
	  var $current_tag;
	  var $parsing_item;
    function RSSActivityHandler(){
	    $this->parsing_item = false;
	    $this->data = array(
		    'items'     => array(),
		    'channel'   => array()
		  );
    }
    function openHandler(& $parser,$name,$attrs) {
	    $this->current_tag = $name;
	    if ($name == 'item'){
	      $this->new_item = array();
	      $this->parsing_item = true;
		  } elseif ($name == 'textInput'){
		      $this->new_item = array();
		      $this->parsing_item = true;
			} elseif ($this->parsing_item) {
				$this->new_item[$name] = $attrs;
			} else {
				$this->data['channel'][$name] = $attrs;
			}
    }
    function closeHandler(& $parser,$name) {
	    if ($name == 'item'){
	      $this->data['items'][] = $this->new_item;
	      $this->parsing_item = false;
	    }
	    if ($name == 'textInput'){
	      $this->data['channel']['textInput'] = $this->new_item;
	      $this->parsing_item = false;
	    }
    }
    function dataHandler(& $parser,$data) {
	    if ($this->parsing_item)
	      if (count($this->new_item[$this->current_tag]) > 0)
	        $this->new_item[$this->current_tag.'_data'] = $data;
	      else
	        $this->new_item[$this->current_tag] = $data;
	    else
	      if (count($this->data['channel'][$this->current_tag]) > 0)
	        $this->data['channel'][$this->current_tag.'_data'] = $data;
	      else
		      $this->data['channel'][$this->current_tag] = $data;
    }
    function escapeHandler(& $parser,$data) {
	    if ($this->parsing_item)
	      $this->new_item[$this->current_tag.'_escape'] = $data;
	    else
	      $this->data['channel'][$this->current_tag.'_escape'] = $data;
    }
    function piHandler(& $parser,$target,$data) {
	    if ($this->parsing_item)
	      $this->new_item[$this->current_tag.'_pi'] = $data;
	    else
	      $this->data['channel'][$this->current_tag.'_pi'] = $data;
    }
    function jaspHandler(& $parser,$data) {
	    if ($this->parsing_item)
	      $this->new_item[$this->current_tag.'_jasp'] = $data;
	    else
	      $this->data['channel'][$this->current_tag.'_jasp'] = $data;
    }
}


function discover_feeds($url){

	add_include_path(library_path());
	include 'Zend/Uri.php';

  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
  curl_setopt($curl, CURLOPT_HEADER, false);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  $result = curl_exec( $curl );

  if ($result) 
		$contents = $result;
	else
		$contents = '';

  @ini_set('track_errors', 1);
  $pattern = '~(<link[^>]+)/?>~i';
  $result = @preg_match_all($pattern, $contents, $matches);
  @ini_restore('track_errors');
  $feeds = array();
  if (isset($matches[1]) && count($matches[1]) > 0) {
      foreach ($matches[1] as $link) {
          if (!mb_check_encoding($link, 'UTF-8')) {
              $link = mb_convert_encoding($link, 'UTF-8');
          }
          $xml = @simplexml_load_string(rtrim($link, ' /') . ' />');
          if ($xml === false) {
              continue;
          }
          $attributes = $xml->attributes();
          if (!isset($attributes['rel']) || !@preg_match('~^(?:alternate|service\.feed)~i', $attributes['rel'])) {
              continue;
          }
          if (!isset($attributes['type']) ||
                  !@preg_match('~^application/(?:atom|rss|rdf)\+xml~', $attributes['type'])) {
              continue;
          }
          if (!isset($attributes['href'])) {
              continue;
          }
          try {
              // checks if we need to canonize the given uri
              try {
                  $uri = Zend_Uri::factory((string) $attributes['href']);
              } catch (Zend_Uri_Exception $e) {
                  // canonize the uri
                  $path = (string) $attributes['href'];
                  $query = $fragment = '';
                  if (substr($path, 0, 1) != '/') {
                      // add the current root path to this one
                      $path = rtrim($client->getUri()->getPath(), '/') . '/' . $path;
                  }
                  if (strpos($path, '?') !== false) {
                      list($path, $query) = explode('?', $path, 2);
                  }
                  if (strpos($query, '#') !== false) {
                      list($query, $fragment) = explode('#', $query, 2);
                  }
                  $uri = Zend_Uri::factory($client->getUri(true));
                  $uri->setPath($path);
                  $uri->setQuery($query);
                  $uri->setFragment($fragment);
              }

          } catch (Exception $e) {
              continue;
          }
          $feeds[] = $uri->getUri();
      }
  }
  return $feeds;
}

function discover_textInput($url){

  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
  curl_setopt($curl, CURLOPT_HEADER, false);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  $result = curl_exec( $curl );

  if ($result) 
		$body = $result;
	else
		$body = false;

  if ($body) {
	  
	  lib_include('parser');

		$parser =& new HtmlParser();
		$handler=& new RSSActivityHandler();

		$parser->set_object($handler);

		$parser->set_option('trimDataNodes', TRUE);

		$parser->set_element_handler('openHandler','closeHandler');
		$parser->set_data_handler('dataHandler');
		$parser->set_escape_handler('escapeHandler');
		$parser->set_pi_handler('piHandler');
		$parser->set_jasp_handler('jaspHandler');

		$parser->parse($body);
 
    return $handler->data['channel']['textInput'];

  }

  return false;

}


function discover_twitter_person( $nickname ) {
	$url = false;
	$parts = str_split($nickname);
	if ($parts[0] == '@')
	  array_shift($parts);
  $endpoint = 'http://api.twitter.com/1/users/show/'.implode($parts).'.json';
  $ch = curl_init();
  if (defined("CURL_CA_BUNDLE_PATH")) curl_setopt($ch, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);
  curl_setopt($ch, CURLOPT_URL, $endpoint);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $response = curl_exec($ch);
	if (!function_exists('json_encode'))
	  lib_include('json');
  $j = new Services_JSON();	
	$twuser = (array)$j->decode($response);
	return $twuser;
}

function get_my_latest_object() {
  global $db;
  $Post =& $db->model('Post');
	$where = array(
    'profile_id'=>get_profile_id(),
    'local'=>1
  );
  $Post->find_by($where);
  while ($p = $Post->MoveNext()){
    $e = $p->FirstChild('entries');
    if ($e->content_type == 'image/jpeg')
      return $p;
  }
  return false;
}

function annotate(&$request){
  $commands = array(
	  'tag '
	);
  $parts = explode(" ",$request->params['post']['title']);
  $c = $parts[0]." ";
  $result = false;
  if (in_array($c,$commands)){
	  $c = trim($c)."_cmdfunc";
	  if (function_exists($c))
 	    $result = $c($parts);
  }
  return $result;
}
	
function tag_cmdfunc($parts){
	// tag tantek.com in url
	// create a new post, and annotate it with acivity strea.ms
	// do feed discovery on tagged person's homepage url
	if (isset($parts[1])){


    if (strpos($parts[1], '.') !== false)
      $object = array('url'=>'http://'.$parts[1],'screen_name'=>$parts[2]);
    else
	    $object = discover_twitter_person( $parts[1] );
	
    global $db,$request;

    $request->set_param( array( 'post', 'title' ), 'tagged '. $parts[1] . ' in a photo' );

	  $Annotation =& $db->model('Annotation');
	  if (!$db->table_exists('annotations'))
	    $Annotation->save();
	
	  $post = get_my_latest_object();
	
		$arr = add_thumbs_if_blob($post->url);

		if ($arr[0]){
			$preview = $arr[0];
		}

		if ($arr[5]){
			$preview = $arr[5];
		}

	  $tagverb = 'http://activitystrea.ms/schema/1.0/tag';
	
		$ann = '[
		    {
		        "annotations": {
		            "activity": {
		                "verb": "'.$tagverb.'",
			              "target": "'.$post->url.'",
		                "target-type": "photo",
		                "target-link-preview": "'.$preview.'",
		                "target-title": "'.$post->title.'",
		                "target-id": "'.$post->url.'",
			              "object": "'.$object['url'].'",
		                "object-type": "person",
		                "object-title": "'.$object['screen_name'].'",
		                "object-id": "'.$object['url'].'"
		            } 
		        } 
		    }
		]';

 	  //object: person
    //target: photo
    preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $ann);

		if (!function_exists('json_encode'))
		  lib_include('json');
	  $j = new Services_JSON();	

	  $a = $Annotation->base();
	  $a->set_value('json',$ann);
	  $a->save();
	
	  // feed discovery to notify the tagged person
    $feeds = discover_feeds( $object['url'] );

    foreach($feeds as $f){

	    $input = discover_textInput($f);

	    if (is_array($input)) {

		    if (isset($input['link'])){
			    $reply_to = $input['link'];
			    $parts = split('mailto:',$reply_to);
			    $recipient = $parts[1];

				  global $request;

				  $subject = 'You were tagged in a photo on '.$request->base;

				  $email = "Hi, you were tagged in this photo:\n\n".$post->url."\n\n";

				  $html = false;

				  send_email( $recipient, $subject, $email, environment('email_from'), environment('email_name'), $html );

		    }
	    }
    }
	
    if ($a->id > 0)
			return $a;
	
	}
	return false;
}

function follow_cmdfunc($parts){
	if (isset($parts[1])){
	  $to = get_twitter_oauth();
    if ($to){
		  $content = $to->OAuthRequest('https://twitter.com/friendships/create/'.$parts[1].'.xml', array(), 'POST');
	    return true;
    }
	}
	return false;
}

function unfollow_cmdfunc($parts){
	if (isset($parts[1])){
	  $to = get_twitter_oauth();
    if ($to){
		  $content = $to->OAuthRequest('https://twitter.com/friendships/destroy/'.$parts[1].'.xml', array(), 'POST');
	    return true;
    }
	}
	return false;
}

function addtolist_cmdfunc($parts){
	if (isset($parts[1])&&isset($parts[2])){
	  $to = get_twitter_oauth();
    if ($to){
		  $content = $to->OAuthRequest('https://twitter.com/users/show/'.$parts[1].'.json', array(), 'GET');
		  if (!(class_exists('Services_JSON')))
		    lib_include('json');
			$json = new Services_JSON();
			$data = $json->decode($content);
			if ($content && $data && $json)
  		  $content = $to->OAuthRequest('https://api.twitter.com/1/'.get_twitter_screen_name().'/'.$parts[2].'/members.xml', array('id'=>$data->id), 'POST');
      if (isset($parts[3]))
	      if ($parts[3] == '-u')
				  $content = $to->OAuthRequest('https://twitter.com/friendships/destroy/'.$parts[1].'.xml', array(), 'POST');
	    return true;
    }
	}
	return false;
}

function post( &$vars ) {
  extract( $vars );
  global $request;

  $twittercmd = handle_twitter_cmdline($request);
  $annotation = annotate($request);

  if ($twittercmd)
    redirect_to($request->base);

  $modelvar = classify($request->resource);
  trigger_before( 'insert_from_post', $$modelvar, $request );
  $table = $request->resource;
  $content_type = 'text/html';
  $rec = $$modelvar->base();
  if (!($$modelvar->can_create( $table )))
    trigger_error( "Sorry, you do not have permission to " . $request->action . " " . $table, E_USER_ERROR );
  $fields = $$modelvar->fields_from_request($request);
  $fieldlist = $fields[$table];
  foreach ( $fieldlist as $field=>$type ) {
    if ($$modelvar->has_metadata && is_blob($table.'.'.$field)) {
      if (isset($_FILES[strtolower(classify($table))]['name'][$field]))
        $content_type = type_of( $_FILES[strtolower(classify($table))]['name'][$field] );
    }
    $rec->set_value( $field, $request->params[strtolower(classify($table))][$field] );
  }
  $rec->set_value('profile_id',get_profile_id());
  $result = $rec->save_changes();
  if ( !$result )
    trigger_error( "The record could not be saved into the database.", E_USER_ERROR );
  if ($annotation) {
    $annotation->set_value('target_id',$rec->id);
    $annotation->save_changes();
  }
  $atomentry = $$modelvar->set_metadata($rec,$content_type,$table,'id');
  $$modelvar->set_categories($rec,$request,$atomentry);
  if ((is_upload($table,'attachment'))) {
    
    $upload_types = environment('upload_types');
    
    if (!$upload_types)
      $upload_types = array('jpg','jpeg','png','gif');
    
    $ext = extension_for( type_of($_FILES[strtolower(classify($table))]['name']['attachment']));
    
    if (!(in_array($ext,$upload_types)))
      trigger_error('Sorry, this site only allows the following file types: '.implode(',',$upload_types), E_USER_ERROR);
    
    $url = $request->url_for(array(
      'resource'=>$table,
      'id'=>$rec->id
    ));
    $title = substr($rec->title,0,140);
    $over = ((strlen($title) + strlen($url) + 1) - 140);
    if ($over > 0)
      $rec->set_value('title',substr($title,0,-$over)." ".$url);
    else
      $rec->set_value('title',$title." ".$url);
    $rec->save_changes();
    

    $tmp = $_FILES[strtolower(classify($table))]['tmp_name']['attachment'];
    
    if (is_jpg($tmp)) {
      $thumbsize = environment('max_pixels');
      $Thumbnail =& $db->model('Thumbnail');
      $t = $Thumbnail->base();
      $newthumb = tempnam( "/tmp", "new".$rec->id.".jpg" );
      resize_jpeg($tmp,$newthumb,$thumbsize);
      $t->set_value('target_id',$atomentry->id);
      $t->save_changes();
      update_uploadsfile( 'thumbnails', $t->id, $newthumb );
      $t->set_etag();
    }
    
  }
  
  trigger_after( 'insert_from_post', $$modelvar, $rec );
  header_status( '201 Created' );
  redirect_to( $request->base );
  
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


  if ($request->client_wants == 'rss'){
	  $request->set_param('action','api_statuses_public_timeline_rss');
	  $response->render($request);
	  exit;
  }


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

function _profile( &$vars ) {
  // entry controller returns
  // a Collection w/ 1 member entry
  extract( $vars );
  $Identity =& $db->model('Identity');
  if (!isset($request->byid))
    $request->set_param('byid',get_profile_id());
  $Member = $Identity->find($request->byid);
  $Entry = $Member->FirstChild( 'entries' );
  $installed_apps = array();
  $Subscription->set_limit(10);
  return vars(
    array( &$collection, &$Member, &$Entry, &$profile, &$Identity, &$Subscription, &$installed_apps ),
    get_defined_vars()
  );
}


function _replies( &$vars ) {
  // index controller returns
  // a Collection of recent entries
  extract( $vars );
  return vars(
    array( &$collection, &$profile ),
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


function _widget( &$vars ) {
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


function _upload( &$vars ) {
  extract( $vars );
  $model =& $db->get_table( $request->resource );
  $Member = $model->base();
  
  $Post->find();
  $p = $Post->MoveFirst();
  if (!$p) $p = 0;
  $url = $request->url_for(array(
    'resource'=>'posts',
    'id'=>$p->id
  ));
  $url_length = strlen($url);
  
  return vars(
    array( &$Member, &$profile, &$url_length ),
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

function _oembed( &$vars ) {
  
  extract( $vars );
  
  $width = $_GET['maxWidth'];
  $height = $_GET['maxHeight'];
  
  $id = array_pop(split("\/",$_GET['url']));

  $version = '1.0';
  
  $p = $Post->find($id);
  $e = $p->FirstChild('entries');
  $title = $p->title;
  
  $o = owner_of($p);
  
  if (extension_for($e->content_type) == 'mp3') {
    $type = 'rich'; // photo video link rich
    $url = $request->url_for(array(
      'resource'=>'posts',
      'id'=>$id,
      'action'=>'attachment.mp3'
    ));
  } elseif (extension_for($e->content_type) == 'jpg') {
    $type = 'photo';
    $url = $request->url_for(array(
      'resource'=>'posts',
      'id'=>$id,
      'action'=>'preview'
    ));
  } elseif (extension_for($e->content_type) == 'mov') {
    $type = 'video';
    $url = $request->url_for(array(
      'resource'=>'posts',
      'id'=>$id,
      'action'=>'attachment.mov'
    ));
  } elseif (extension_for($e->content_type) == 'avi') {
    $type = 'video';
    $url = $request->url_for(array(
      'resource'=>'posts',
      'id'=>$id,
      'action'=>'attachment.avi'
    ));
  } else {
    exit;
  }
  
  
  $author_name = $o->nickname;
  $author_url = $o->profile;
  $cache_age = 3600;
  $provider_name = "myphotos";
  $provider_url = $request->base;

  $thumbnail_url = 0;
  $thumbnail_width = 0;
  $thumbnail_height = 0;
  

  
  return vars(
    array(
      &$version,
      &$type,
      &$title,
      &$author_name,
      &$author_url,
      &$cache_age,
      &$provider_name,
      &$provider_url,
      &$width,
      &$height,
      &$thumbnail_url,
      &$thumbnail_width,
      &$thumbnail_height,
      &$url
    ),
    get_defined_vars()
  );
  
}



function _apps( &$vars ) {
  extract($vars);
  $Identity =& $db->model('Identity');
  global $submenu,$current_user;
  trigger_before( 'admin_menu', $current_user, $current_user );
  $menuitems = array();
  $apps_list = array();
  global $env;
  if (is_array($env['apps']))
    $apps_list = $env['apps'];
  $i = $Identity->find(get_profile_id());
  while ($s = $i->NextChild('settings')){
    $s = $Setting->find($s->id);
    $e = $s->FirstChild('entries');
    $apps_list[] = $s->value;
  }
  $menuitems[$request->url_for(array(
    'resource'=>'identities',
    'id'=>get_profile_id(),
    'action'=>'edit'
    )).'/partial'] = 'Settings';
  $menuitems[$request->url_for(array(
    'resource'=>'identities',
    'id'=>get_profile_id(),
    'action'=>'subs'
    )).'/partial'] = 'Friends';
  //$menuitems[$request->url_for(array(
  //  'resource'=>'identities',
  //  'id'=>get_profile_id(),
  //  'action'=>'apps'
  //  )).'/partial'] = 'Apps';
  foreach ($submenu as $arr) {
    if (in_array($arr[0][0],$apps_list))
      $menuitems[$arr[0][4]] = $arr[0][3];
  }
  return vars(
    array(&$menuitems),
    get_defined_vars()
  );
}


function preview( &$vars ) {
  extract($vars);
  $model =& $db->get_table( $request->resource );
  $Entry =& $db->model('Entry');
  $p = $model->find($request->id);
  $e = $Entry->find($p->entry_id);
  $t = $Thumbnail->find_by('target_id',$e->id);
  if ($t) {
    $request->set_param('resource','thumbnails');
    $request->set_param('id',$t->id);
    render_blob($t->attachment,extension_for($e->content_type));
  } else {
    render_blob($p->attachment,extension_for($e->content_type));
  }
}


function _pagelist( &$vars ) {
  extract( $vars );
  return vars(
    array( &$collection, &$profile ),
    get_defined_vars()
  );
}

function _pagenew( &$vars ) {
  extract( $vars );
  $model =& $db->get_table( $request->resource );
  $Member = $model->base();
  return vars(
    array( &$Member, &$profile ),
    get_defined_vars()
  );
}

function _pagespan( &$vars ) {
  extract( $vars );
  return vars(
    array( &$collection, &$profile ),
    get_defined_vars()
  );
}
