<?php 



class Method extends Model {
  
  function Method() {
    
    $this->auto_field( 'id' );
    
    $this->int_field( 'entry_id' );
    $this->int_field( 'oauth' );
    $this->int_field( 'http' );
    $this->int_field( 'omb' );
    
    $this->bool_field( 'enabled' );
    
    $this->char_field( 'function' );
    $this->char_field( 'route' );
    $this->char_field( 'resource' );
    $this->char_field( 'permission' );
    
    $this->text_field( 'code' );
    
    $this->has_one( 'entry' );
    
    $this->let_access( 'all:administrators' );
    
  }
  
  function init() {
    
    $this->set_limit(100);
    $this->find();
    $methods = array();
    while ($m = $this->MoveNext())
	    $methods[] = $m->function;


    $m = $this->base();
    $m->set_value( 'code', '
    
// get some variables in scope
extract( $vars );
$tweets = array();

$callback = $_GET[\'callback\'];

// get the data model for the "posts" table
$Post =& $db->model( \'Post\' );

// search for the most recent 10 records
$Post->find();

// loop over each record
while ( $p = $Post->MoveNext() ) {

  $profile = owner_of( $p );

  $tweet = array();

  $user = array(
     \'screen_name\' => $profile->nickname,
     \'profile_background_image_url\' => $profile->avatar,
     \'url\' => $profile->profile_url
  );

  $tweet[\'text\'] = $p->title;
  $tweet[\'truncated\'] = \'false\';
  $tweet[\'created_at\'] = date( "D M d G:i:s O Y", strtotime( $p->created ));
  $tweet[\'in_reply_to_status_id\'] = null;
  $tweet[\'source\'] = null;
  $tweet[\'id\'] = $p->uri;
  $tweet[\'favorited\'] =\'false\';
  $tweet[\'user\'] = $user;

  $tweets[] = $tweet;

}

echo $callback."(";

if (!(class_exists(\'Services_JSON\')))
  lib_include(\'json\');
$json = new Services_JSON();

// create the JSON data
echo $json->encode( $tweets );

echo ");";

');
  
    $m->set_value( 'function', 'api_statuses_public_timeline' );
    $m->set_value( 'route', 'api/statuses/public_timeline.json' );
    $m->set_value( 'resource', 'posts' );
    $m->set_value( 'permission', 'read' );
    $m->set_value( 'enabled', true );
    $m->set_value( 'omb', 1 );
    $m->set_value( 'oauth', 1 );
    $m->set_value( 'http', 1 );

    if (!(in_array($m->attributes['function'],$methods))){
      $m->save_changes();
      $m->set_etag(1);
    }  

    $m = $this->base();
    $m->set_value( 'code', '
    
extract( $vars );

$request->set_param( array( \'post\', \'title\' ), $request->status );

$resource->insert_from_post( $request );

if ($request->client_wants == \'xml\')
  render_home_timeline(true,$request->id);

header( \'Status: 200 OK\' );

');
  
    $m->set_value( 'function', 'api_statuses_update' );
    $m->set_value( 'route', 'api/statuses/update' );
    $m->set_value( 'resource', 'posts' );
    $m->set_value( 'permission', 'write' );
    $m->set_value( 'enabled', true );
    $m->set_value( 'omb', 1 );
    $m->set_value( 'oauth', 1 );
    $m->set_value( 'http', 1 );

    if (!(in_array($m->attributes['function'],$methods))){
      $m->save_changes();
      $m->set_etag(1);
    }  

    $m = $this->base();
    $m->set_value( 'code', '

global $db,$request;

$Identity =& $db->get_table( \'identities\' );
$Person =& $db->get_table( \'people\' );
$i = $Identity->find_by(array(
  \'nickname\'=>$db->escape_string($_POST[\'username\']),
  \'password\'=>md5($db->escape_string($_POST[\'password\']))
),1);
$p = $Person->find( $i->person_id );
if (!(isset( $p->id ) && $p->id > 0))
  exit;

header(\'Content-Type: text/xml\');
echo \'<?xml version="1.0" encoding="UTF-8"?>
\';
if (isset($_FILES[\'media\'])) {
  handle_posted_file(\'jpg\',$_FILES[\'media\'][\'tmp_name\'],$i);
  $mediaid = \'_\'.$request->id;
  $mediaurl = $request->url_for(array(
\'resource\'=>\'__\'.$request->id.".jpg"));
  echo \'<rsp stat="ok">
 <mediaid>\'.$mediaid.\'</mediaid>
 <mediaurl>\'.$mediaurl.\'</mediaurl>
</rsp>
\';
} else {
  echo \'<rsp stat="fail">
    <err code="1001" msg="Invalid twitter username or password" />
</rsp>
\';
}
exit;

');

    $m->set_value( 'function', 'api_upload' );
    $m->set_value( 'route', 'api/upload' );
    $m->set_value( 'resource', 'posts' );
    $m->set_value( 'permission', 'read' );
    $m->set_value( 'enabled', true );
    $m->set_value( 'omb', 1 );
    $m->set_value( 'oauth', 1 );
    $m->set_value( 'http', 1 );

    if (!(in_array($m->attributes['function'],$methods))){
      $m->save_changes();
      $m->set_etag(1);
    }  
  

    $m = $this->base();
    $m->set_value( 'code', '

extract( $vars );


if (isset($request->client_wants)){
if ($request->client_wants == \'json\') {



$nick = substr( $request->params[\'nickname\'], 0, -5 );
$Identity =& $db->model( \'Identity\' );
$Identity->set_param(\'find_by\',array(
  \'nickname\' => $nick,
  \'eq\'=>\'IS\',
  \'post_notice\' => \'NULL\',
));

$Post =& $db->model( \'Post\' );
$Post->set_param( \'find_by\', array(
  \'entries.person_id\' => $profile->person_id
));

$Post->find();
$tweets = array();
while ($p = $Post->MoveNext()) {
  $tweet = array();
  $tweet[\'text\'] = $p->title;
  $tweet[\'truncated\'] = \'false\';
  $tweet[\'created_at\'] = date( "D M d G:i:s O Y", strtotime( $p->created ));
  $tweet[\'in_reply_to_status_id\'] = null;
  $tweet[\'source\'] = null;
  $tweet[\'id\'] = intval( $p->id );
  $tweet[\'favorited\'] =\'false\';
  $tweet[\'user\'] = $nick;
  $tweets[] = $tweet;
}

echo "twitterCallback2(";

$json = new Services_JSON();

echo $json->encode( $tweets );

echo \');\';





exit;
}


}



$parts = split(\'\.\',$request->params[\'byid\']);
$id = $parts[0];
$request->set_param(\'byid\',$id);
$request->set_param(\'order\',\'desc\');

    $where = array(
      \'profile_id\'=>$id,
      \'parent_id\'=>0
    );
$tweets = new Collection( \'posts\', $where );
$pro = get_profile($id);
echo \'<?xml version="1.0"?>
<!-- RSS generated by OpenMicroBlogger v0.5.0 on \'.date( "n/j/Y; g:i:s A e" ).\' -->
<rss version="2.0">
	<channel>
		<title>\'.environment(\'site_title\').\' / \'.$pro->nickname.\'</title>
		<link>\'.$pro->profile_url.\'</link>
		<description>\'.environment(\'site_title\').\' updates from \'.$pro->fullname.\' / @\'.$pro->nickname.\'</description>
		<language>en-us</language>
		<copyright></copyright>
		<pubDate>\'.date( "D, j M Y H:i:s T" ).\'</pubDate>
		<lastBuildDate>\'.date( "D, j M Y H:i:s T", strtotime( $tweets->updated )).\'</lastBuildDate>
		<generator>OpenMicroBlogger</generator>
    \';
    do_action(\'rss2_head\');
echo \'
\';

while ($p = $tweets->MoveNext()) {
$posturl = $request->url_for(array(\'resource\'=>\'posts\',\'id\'=>$p->id));
$comurl = $posturl;
echo \'		<item>
			<title>\'.$p->title.\'</title>
			<link>\'.$posturl.\'</link>
			<guid>\'.$posturl.\'</guid>
      <comments>\'.$comurl.\'</comments>
			<description>\'.$p->body.\'</description>
			<pubDate>\'.date( "D, j M Y H:i:s T", strtotime( $p->created )).\'</pubDate>
	  </item>
\';
}
echo \'	</channel>
</rss>
\';

');

		$m->set_value( 'function', 'api_statuses_user_timeline' );
		$m->set_value( 'route', 'api/statuses/user_timeline/:byid' );
		$m->set_value( 'resource', 'posts' );
		$m->set_value( 'permission', 'read' );
    $m->set_value( 'enabled', true );
    $m->set_value( 'omb', 1 );
    $m->set_value( 'oauth', 1 );
    $m->set_value( 'http', 1 );

    if (!(in_array($m->attributes['function'],$methods))){
      $m->save_changes();
      $m->set_etag(1);
    }  



    $m = $this->base();
    $m->set_value( 'code', '

    if (!(function_exists(\'rsscloud_schedule_post_notifications\'))) {
		function rsscloud_schedule_post_notifications() {
			// prevent Joseph Scott\'s plugin from loading its update feature
		}}
		
		global $blogdata;

		$blogdata[\'rss2_url\'] =$_POST[\'url1\'];

		add_include_path(library_path());

		include_once(\'rsscloud/rsscloud.php\');

		rsscloud_hub_process_notification_request();
		
		');

		$m->set_value( 'function', 'api_rsscloud_pleaseNotify' );
		$m->set_value( 'route', 'api/rsscloud/pleaseNotify' );
		$m->set_value( 'resource', 'posts' );
		$m->set_value( 'permission', 'read' );
    $m->set_value( 'enabled', true );
    $m->set_value( 'omb', 1 );
    $m->set_value( 'oauth', 1 );
    $m->set_value( 'http', 1 );

    if (!(in_array($m->attributes['function'],$methods))){
      $m->save_changes();
      $m->set_etag(1);
    }

    $m = $this->base();
    $m->set_value( 'code', '

		global $db,$request,$response;

		if (isset($_GET[\'challenge\'])) {

		  header( \'Status: 200 OK\' );
		  echo $_GET[\'challenge\'];
		  exit;

		}

		extract($vars);
		$Feed =& $db->model(\'Feed\');
		$f = $Feed->find_by(\'xref\',$_POST[\'url\']);
		if ($f->profile_id) {
		  $profile_id = $f->profile_id;
		  $url = $_POST[\'url\'];
		} else {
		  exit;
		}

		$items = array();
		$title = \'\';
		$link = \'\';
		$description = \'\';

		$buf = readURL($url);
		$xml = new SimpleXmlElement($buf);
		foreach($xml as $k1=>$v1){
		   foreach($v1 as $k2=>$v2){
			   $link = \'\';
		     if ($k2 == \'item\') {
				   foreach($v2 as $k3=>$v3){
		        if ($k3 == \'title\')
		          $title = (string)$v3;
		        if ($k3 == \'link\')
		          $link = (string)$v3;
		        if ($k3 == \'description\')
		          $description = (string)$v3;
		       }
		       if (!empty($link))
		         $items[] =array(
								\'title\'=>$title,
								\'link\'=>$link,
								\'description\'=>$description
		       		);
		     }
			}
		}

		$profile = get_profile($profile_id);

		$response->set_var(\'profile\',$profile);

		load_apps();
		$Post =& $db->model(\'Post\');
		foreach($items as $feeditem){
			$p = $Post->find_by(\'url\',$feeditem[\'link\']);
			if ($p)
			  continue;
			$table = \'posts\';
			$Post =& $db->model(\'Post\');
			$modelvar = \'Post\';
			$request->set_param(\'resource\',$table);
			trigger_before( \'insert_from_post\', $$modelvar, $request );
			$content_type = \'text/html\';
			$rec = $$modelvar->base();
			$rec->set_value(\'profile_id\',$profile_id);
			$rec->set_value( \'parent_id\', 0 );
			$rec->set_value( \'title\', $feeditem[\'title\'] );
			$rec->set_value( \'body\', $feeditem[\'description\'] );
			$rec->set_value( \'uri\', $feeditem[\'link\'] );
			$rec->set_value( \'url\', $feeditem[\'link\'] );
			$rec->save_changes();
			$atomentry = $$modelvar->set_metadata($rec,$content_type,$table,\'id\');
			trigger_after( \'insert_from_post\', $$modelvar, $rec );
		}
		header( \'Status: 200 OK\' );
		exit;
			
		');

		$m->set_value( 'function', 'api_rsscloud_callback' );
		$m->set_value( 'route', 'api/rsscloud/callback' );
		$m->set_value( 'resource', 'posts' );
		$m->set_value( 'permission', 'read' );
    $m->set_value( 'enabled', true );
    $m->set_value( 'omb', 1 );
    $m->set_value( 'oauth', 1 );
    $m->set_value( 'http', 1 );

    if (!(in_array($m->attributes['function'],$methods))){
      $m->save_changes();
      $m->set_etag(1);
    }  

    $m = $this->base();
    $m->set_value( 'code', '

    if (!(function_exists(\'rsscloud_schedule_post_notifications\'))) {
		function rsscloud_schedule_post_notifications() {
			// prevent Joseph Scott\'s plugin from loading its update feature
		}}

		global $blogdata;

		$blogdata[\'rss2_url\'] = $_POST[\'url\'];

		add_include_path(library_path());

		include_once(\'rsscloud/rsscloud.php\');

		$notify_rss = get_bloginfo( \'rss2_url\' );

			$listeners = rsscloud_get_hub_notifications();

			if (!is_array($listeners))
			  return;

			foreach ( $listeners[$notify_rss] as $notify_url => $n ) {
				if ( $n[\'status\'] == \'active\' ) {
					if ( $n[\'protocol\'] == \'http-post\' ) {
						$url = parse_url( $notify_url );
						$port = 80;
						if ( !empty( $url[\'port\'] ) )
							$port = $url[\'port\'];
						$notify_vars="url=" . $notify_rss;
						$ch = curl_init();
						curl_setopt ($ch, CURLOPT_URL, $notify_url);
						curl_setopt ($ch, CURLOPT_HEADER, 0); /// Header control
						curl_setopt ($ch, CURLOPT_PORT, $port);
						curl_setopt ($ch, CURLOPT_POST, true);  /// tell it to make a POST, not a GET
						curl_setopt ($ch, CURLOPT_POSTFIELDS, $notify_vars);
						curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
						$xml_response = curl_exec ($ch);
						curl_close ($ch);			
						$need_update = false;
						if ( $result[\'response\'][\'code\'] != 200 ) {
							$notify[$rss2_url][$notify_url][\'failure_count\']++;
							$need_update = true;
						} elseif ( $notify[$rss2_url][$notify_url][\'failure_count\'] > RSSCLOUD_MAX_FAILURES ) {
							$notify[$rss2_url][$notify_url][\'status\'] = \'suspended\';
							$need_update = true;
						}
					}
				}
			}

			if ( $need_update )
				rsscloud_update_hub_notifications( $notify );

		');

		$m->set_value( 'function', 'api_rsscloud_ping' );
		$m->set_value( 'route', 'api/rsscloud/ping' );
		$m->set_value( 'resource', 'posts' );
		$m->set_value( 'permission', 'read' );
    $m->set_value( 'enabled', true );
    $m->set_value( 'omb', 1 );
    $m->set_value( 'oauth', 1 );
    $m->set_value( 'http', 1 );

    if (!(in_array($m->attributes['function'],$methods))){
      $m->save_changes();
      $m->set_etag(1);
    }  

    $m = $this->base();
    $m->set_value( 'code', '
			extract( $vars );
			
      if (!function_exists(\'set_default_omb_cloud_options\'))
        include(app_path().\'rsscloud/rsscloud.php\');

		  if ( \'\' == get_option( \'cloud_domain\' ) )
			    set_default_omb_cloud_options();

		  add_action(\'rss2_head\',\'load_my_cloud_element\');

			lib_include( \'rsscloud_element\' );


			echo \'<?xml version="1.0"?>
			  <!-- RSS generated by OpenMicroBlogger on \'.date( "n/j/Y; g:i:s A e" ).\' -->
			  <rss version="2.0">
			    <channel>
			      <title>\'.htmlspecialchars(environment(\'site_title\')).\'</title>
			      <link>\'.$request->base.\'</link>
			      <description>\'.htmlspecialchars(environment(\'site_description\')).\'</description>
			      <language>en-us</language>
			      <copyright></copyright>
			      <pubDate>\'.date( "D, j M Y H:i:s T" ).\'</pubDate>
			      <lastBuildDate>\'.date( "D, j M Y H:i:s T", strtotime( $collection->updated )).\'</lastBuildDate>
			      <generator>OpenMicroBlogger</generator>
			      \';
			      do_action(\'rss2_head\');
			      echo \'
			      \';
			      while ($p = $collection->MoveNext()) {
			      echo \'<item>
			        <title>\'.htmlspecialchars($p->title).\'</title>
			        <link>\'.$p->url.\'</link>
			        <guid>\'.$p->url.\'</guid>
			        <comments>\'.$p->url.\'</comments>
			        <description>\'.htmlspecialchars($p->body).\'</description>
			        <pubDate>\'.date( "D, j M Y H:i:s T", strtotime( $p->created )).\'</pubDate>
			      </item>
			      \';
			      }
			  echo \'</channel>
			  </rss>
			\';
		');

		$m->set_value( 'function', 'api_statuses_public_timeline_rss' );
		$m->set_value( 'route', 'api/statuses/public_timeline' );
		$m->set_value( 'resource', 'posts' );
		$m->set_value( 'permission', 'read' );
    $m->set_value( 'enabled', true );
    $m->set_value( 'omb', 1 );
    $m->set_value( 'oauth', 1 );
    $m->set_value( 'http', 1 );

    if (!(in_array($m->attributes['function'],$methods))){
      $m->save_changes();
      $m->set_etag(1);
    }  

	  $m = $this->base();
    $m->set_value( 'code', '

			// get some variables in scope
			extract( $vars );

			if (isset($request->client_wants)){
			if ($request->client_wants == \'xml\'){

			render_home_timeline();

			}}


			$tweets = array();

			$callback = $_GET[\'callback\'];

			// get the data model for the "posts" table
			$Post =& $db->model( \'Post\' );

			// search for the most recent 10 records
			$Post->find();

			// loop over each record
			while ( $p = $Post->MoveNext() ) {

			  $profile = owner_of( $p );

			  $tweet = array();

			  $user = array(
			     \'screen_name\' => $profile->nickname,
			     \'profile_background_image_url\' => $profile->avatar,
			     \'url\' => $profile->profile_url
			  );

			  $tweet[\'text\'] = $p->title;
			  $tweet[\'truncated\'] = \'false\';
			  $tweet[\'created_at\'] = date( "D M d G:i:s O Y", strtotime( $p->created ));
			  $tweet[\'in_reply_to_status_id\'] = null;
			  $tweet[\'source\'] = null;
			  $tweet[\'id\'] = $p->uri;
			  $tweet[\'favorited\'] =\'false\';
			  $tweet[\'user\'] = $user;

			  $tweets[] = $tweet;

			}

			if ($callback) 
			echo $callback."(";
			if (!(class_exists(\'Services_JSON\')))
			  lib_include(\'json\');
			$json = new Services_JSON();

			// create the JSON data
			echo $json->encode( $tweets );

			if ($callback)
			echo ");";

      exit;


		');

		$m->set_value( 'function', 'api_statuses_home_timeline' );
		$m->set_value( 'route', 'api/statuses/home_timeline' );
		$m->set_value( 'resource', 'posts' );
		$m->set_value( 'permission', 'read' );
    $m->set_value( 'enabled', true );
    $m->set_value( 'omb', 1 );
    $m->set_value( 'oauth', 1 );
    $m->set_value( 'http', 1 );

    if (!(in_array($m->attributes['function'],$methods))){
      $m->save_changes();
      $m->set_etag(1);
    }



  }

}

