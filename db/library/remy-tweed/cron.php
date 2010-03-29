<?php





















// SHOW ERRORS
ini_set('display_errors','1');
ini_set('display_startup_errors','1');
error_reporting (E_ALL & ~E_NOTICE );




$path = pathinfo(__FILE__, PATHINFO_DIRNAME);

$ombroot = substr($path,0,-21);

chdir($ombroot);

include_once($path . '/lib/spyc.php');
include_once( $path . '/lib/xml2array.php');
if( !function_exists('json_decode') )
    include_once( $path . '/lib/JSON.php');












// dbscript booter

$version = '0.6.0';

global $views,$app,$config,$env,$exec_time,$version,$response;
global $variants,$request,$loader,$db,$logic;

$app = $ombroot.'db/';

if (file_exists($ombroot.'config/config.php'))
  require($ombroot.'config/config.php');
else
  require($ombroot.'config.php');


$GLOBALS['PATH'] = array();
$GLOBALS['PATH']['app'] = $app;
$GLOBALS['PATH']['library'] = $app . 'library' . DIRECTORY_SEPARATOR;
$GLOBALS['PATH']['controllers'] = $app . 'controllers' . DIRECTORY_SEPARATOR;
$GLOBALS['PATH']['models'] = $app . 'models' . DIRECTORY_SEPARATOR;
$GLOBALS['PATH']['plugins'] = $app . 'plugins' . DIRECTORY_SEPARATOR;
$GLOBALS['PATH']['dbscript'] = $GLOBALS['PATH']['library'] . 'dbscript' . DIRECTORY_SEPARATOR;
foreach( array(
    '_functions',
    'bootloader',
    'mapper',
    'route',
    'genericiterator',
    'collection',
    'view',
    'cookie'
  ) as $module ) {
  include $GLOBALS['PATH']['dbscript'] . $module . '.php';
}
lib_include( 'inflector' );
$request = new Mapper();
error_reporting( E_ALL & ~E_NOTICE & ~E_WARNING );
$dbscript_error_handler = set_error_handler( 'dbscript_error' );
include $GLOBALS['PATH']['library'] . 'yaml.php';
$loader = new Horde_Yaml();
if ( file_exists( $app . 'config.yml' ) ) {
  extract($loader->load(file_get_contents($app.'config.yml')));
  extract( $$env['enable_db'] );
} else {
  $env = array('app_folder'=>'app');
}
$env = array('app_folder'=>$ombroot.'app');
if (is_dir( $env['app_folder'] )) {
  $app = $env['app_folder'] . DIRECTORY_SEPARATOR;
  $appdir = $app;
  if ( file_exists( $app . 'config' . DIRECTORY_SEPARATOR . 'config.yml' ) ) {
    extract($loader->load(file_get_contents($app . 'config' . DIRECTORY_SEPARATOR .'config.yml')));
    extract( $$env['enable_db'] );
    if (isset($env['boot']))
      $appdir = $app.$env['boot'].DIRECTORY_SEPARATOR;
    else
      $appdir = $app.'omb'.DIRECTORY_SEPARATOR;
    $GLOBALS['PATH']['app'] = $app;
    $app = $appdir;
    $GLOBALS['PATH']['controllers'] = $appdir . 'controllers' . DIRECTORY_SEPARATOR;
    $GLOBALS['PATH']['models'] = $appdir . 'models' . DIRECTORY_SEPARATOR;
  }
  if (is_dir( $appdir . 'plugins' . DIRECTORY_SEPARATOR ))
    $GLOBALS['PATH']['plugins'] = $appdir . 'plugins' . DIRECTORY_SEPARATOR;
}
if ($env['debug_enabled']) {
  ini_set('display_errors','1');
  ini_set('display_startup_errors','1');
  error_reporting (E_ALL & ~E_NOTICE );
  global $exec_time;
  $exec_time = microtime_float();
}
db_include( array(
  'database',
  'model',
  'record',
  'recordset',
  'resultiterator',
  $adapter
));

if (defined('DB_NAME') && DB_NAME)
  $database = DB_NAME;

if (defined('DB_USER') && DB_USER)
  $username = DB_USER;

if (defined('DB_PASSWORD') && DB_PASSWORD)
  $password = DB_PASSWORD;

if (defined('DB_HOST') && DB_HOST)
  $host = DB_HOST;

$db = new $adapter(
  $host,
  $database,
  $username,
  $password
);
$loader = new BootLoader();
$loader->start();
$Setting =& $db->model('Setting');
$Setting->set_limit(1000);
$Setting->find_by(array(
  'eq'    => 'like',
  'name'  => 'config%'
));
$envowner = array();
while ($s = $Setting->MoveNext()) {
  $set = split('\.',$s->name);
  if (is_array($set) && $set[0] == 'config') {
    if ($set[1] == 'env') {
      $env[$set[2]] = $s->value;
      if ($s->person_id)
        $envowner[$set[2]] = $s->person_id;
    } elseif ($set[1] == 'perms') {
      $tab =& $db->models[$set[2]];
      if ($tab)
        $tab->permission_mask( $set[3],$s->value,$set[4] );
    }
  }
}
if ( isset( $env ))
  while ( list( $key, $plugin ) = each( $env['plugins'] ) )
    load_plugin( $plugin );



// end dbscript booter


after_filter( 'send_ping', 'insert_tweets_via_cron' );

$follow = array();
foreach ($env as $key=>$val) {
  if (substr($key,0,13) == 'importtwitter' && $val == 1) {
    $optname = 'conf_for_'.$key;
    $options = get_option($optname);
    if (!$options) {
      $options = array();
      $options['busy'] = 0;
      $options['last_id'] = 0;
      $options['latest_id'] = 0;
      add_option($optname,$options);
    }
    $options['busy'] = 0;
    if (!$options['busy']) {
      $options['busy'] = 1;
      $options['person_id'] = $envowner[$key];
      $tu = split("_",$key);
      if ($tu[1]){
        update_option($optname,$options);
        $follow[$tu[1]] = array($optname,$options);
      }
    }
  }

  if (substr($key,0,14) == 'importfacebook' && $val == 1) {
    $optname = 'conf_for_'.$key;
    $options = get_option($optname);
    if (!$options) {
      $options = array();
      $options['busy'] = 0;
      $options['last_id'] = 0;
      $options['latest_id'] = 0;
      add_option($optname,$options);
    }
    $options['busy'] = 0;
    if (!$options['busy']) {
      $options['busy'] = 1;
      $options['person_id'] = $envowner[$key];
      $u = split("_",$key);
      if ($u[1]){
	      update_option($optname,$options);
	      $follow[$u[1]] = array($optname,$options);
	    }
    }
  }

  if (substr($key,0,12) == 'importgoogle' && $val == 1) {
    $optname = 'conf_for_'.$key;
    $options = get_option($optname);
    if (!$options) {
      $options = array();
      $options['busy'] = 0;
      $options['last_id'] = 0;
      $options['latest_id'] = 0;
      add_option($optname,$options);
    }
    $options['busy'] = 0;
    if (!$options['busy']) {
      $options['busy'] = 1;
      $options['person_id'] = $envowner[$key];
      $u = split("_",$key);
      if ($u[1]){
        update_option($optname,$options);
        $follow[$u[1]] = array($optname,$options);
      }
    }
  }

}


 global $db,$prefix;

if (environment('facebookSession')){

		$app_id = environment('facebookAppId');
	  $consumer_key = environment('facebookKey');
	  $consumer_secret = environment('facebookSecret');
	  $agent = environment('facebookAppName')." (curl)";
	  add_include_path(library_path());
	  add_include_path(library_path().'facebook-platform/php');
	  add_include_path(library_path().'facebook_stream');
	  require_once "FacebookStream.php";
	  require_once "Services/Facebook.php";
	  require_once "facebook.php";

   	$sesskey = environment('facebookSession');

		$fb = new Facebook($consumer_key, $consumer_secret, true);
	  $fs = new FacebookStream($consumer_key,$consumer_secret,$agent);

		//	  $fs = new FacebookStream($consumer_key,$consumer_secret,$agent);

		$fb->api_client->session_key = $sesskey;

}

$FacebookUser =& $db->model('FacebookUser');
$Post =& $db->model('Post');
$Identity =& $db->model('Identity');




		foreach ($follow as $uid=>$options) {

			if (!$options[1]['person_id'])
			  continue;

			$microblog = $Setting->find_by(array(
				'person_id'=>$options[1]['person_id'],
			  'name'  => 'tweetiepic_stream'
			));
			if (!$microblog)
				continue;
			if (substr($options[0],9,14) == 'importfacebook' && environment('facebookSession')) {

        // do facebook

				$sql = "SELECT DISTINCT profile_id FROM ".$prefix."facebook_users WHERE facebook_id = ".$uid;

				$result = $db->get_result( $sql );
        $profile_id = $db->result_value($result,0,'profile_id');

				if ($profile_id){
					$fb->api_client->user = $uid;
					if ($options[1]['last_id'])
  					$posts = $fb->api_client->stream_get(null,
				                              null,
				                              $options[1]['last_id'],
				                               0,
				                              30,
				                              '');
				  else
				    $posts = $fb->api_client->stream_get();
				  $profiles = $posts['profiles'];
				  $posts = $posts['posts'];
				  $albums = $posts['albums'];
					
					foreach($profiles as $k=>$p)
	          $profiles[$p['id']] = $p;

	    foreach($posts as $p){
		        if (!empty($p['message'])){
			         if (!isset($updated_time) || ($updated_time < $p['updated_time']))
  			          $updated_time = $p['updated_time'];
			
						  $faceuser = $FacebookUser->find_by( 'facebook_id',$p['actor_id'] );
						  if ($faceuser) {

						    if (!$faceuser->profile_id) {
						        trigger_error('sorry the facebook user was created without a profile', E_USER_ERROR);
						    } else {
						      // b
						      $i = $Identity->find($faceuser->profile_id);
						      if (!$i)
						        trigger_error('sorry I was unable to find the identity', E_USER_ERROR);
						    }
						  } else {
						    // c
							  $fieldlist = array(
							    'last_name',
							    'first_name',
							    'pic_small',
							    'profile_blurb',
							    'profile_url',
							    'locale',
							    'name',
							    'proxied_email'
							  );
							  $fields = implode(',',$fieldlist);

							  $user = $fs->GetInfo( $app_id, environment('facebookSession'), $p['actor_id'], $fields );
							  $values = array();
							  $values[] = str_replace(' ','',strtolower((string)$user->user->name));
							  $values[] = (string)$user->user->pic_small;
							  $values[] = (string)$user->user->name;
							  $values[] = (string)$user->user->profile_blurb;
							  $values[] = (string)$user->user->profile_url;
							  $values[] = (string)$user->user->locale;
						    $i = make_identity($values);
						    if (!$i)
						      trigger_error('sorry I was unable to create an identity', E_USER_ERROR);
						    $faceuser = make_fb_user($user,$i->id);
						    if (!$faceuser)
						      trigger_error('sorry I was unable to create a facebook user', E_USER_ERROR);
						  }

              $title = $p['message'];
              $tweeturl = $p['permalink'];
              $request->set_param(array('post','parent_id'),0);
              $request->set_param(array('post','uri'),$tweeturl);
              $request->set_param(array('post','url'),$tweeturl);
              $request->set_param(array('post','title'),$title);
              $request->set_param(array('post','profile_id'),$i->id);
 
              $table = 'posts';
              $content_type = 'text/html';

						  global $prefix;
						  $prefix = $microblog->value."_";
						  $db->prefix = $prefix;

              $rec = $Post->base();
              $fields = $Post->fields_from_request($request);
              $fieldlist = $fields['posts'];
              foreach ( $fieldlist as $field=>$type )
                $rec->set_value( $field, $request->params[strtolower(classify($table))][$field] );

$exists = $Post->find_by(array('url'=>$tweeturl));
if (!$exists){

              $rec->save_changes();
              $rec->set_etag($i->person_id);
              trigger_after( 'insert_tweets_via_cron', $Post, $rec );

}

global $prefix;
$prefix = "";
$db->prefix = $prefix;

			        //echo ."\n";
			        //echo $profiles[$p['actor_id']]['name']."\n"."\n";
							//echo "\n\n";

		        } else {
//			        echo "skipped\n";
		        }

         	}
				}
				  if (isset($updated_time))
				    $options[1]['last_id'] = $updated_time+1;
				  $options[1]['busy'] = 0;
				  update_option($options[0],$options[1]);
				} elseif (substr($options[0],9,12) == 'importgoogle') {
					
			    $optfind = "config.env.".substr($options[0],9);
				  $Setting =& $db->model('Setting');
					$s1 = $Setting->find_by(array('name'=>$optfind));
				  $scope = 'https://mail.google.com/mail/feed/atom/';
				  $base_url = $request->base;
					$endpoint = $scope;
					$parsed = parse_url($endpoint);
					$params = array();
					parse_str($parsed['query'], $params);
				  lib_include('twitteroauth');
				  $key = environment( 'googleKey' );
				  $secret = environment( 'googleSecret' );
					$consumer = new OAuthConsumer($key, $secret, NULL);
				  $hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
				 $token = get_oauth_token(get_option('google_key',$s1->profile_id), get_option('google_secret',$s1->profile_id));
					$oauth_req = OAuthRequest::from_consumer_and_token($consumer, $token, "GET", $endpoint, $params);
					$oauth_req->sign_request($hmac_method, $consumer, $token);
					$responseString = send_signed_request($oauth_req->get_normalized_http_method(),
					                                      $endpoint, $oauth_req->to_header(), NULL, false);
	$data = $responseString;
$xml = new SimpleXmlElement($data);
foreach($xml as $k1=>$v1){
  $values = array();
  foreach($v1 as $k2=>$v2){
		if ($k2 == 'title'){
			$values['title'] = (string)$v2;
		}
		elseif ($k2 == 'link'){
			$values['url'] = (string)$v2['href'];
			$parsed = parse_url($values['url']);
			$params = array();
			parse_str($parsed['query'], $params);
			$values['acct'] = $params['account_id'];
		}
		elseif ($k2 == 'author'){
			foreach($v2 as $k=>$v){
				if ($k == 'name')
	        $values['name'] = (string)$v;
				if ($k == 'email')
	        $values['email'] = (string)$v;
			}
	  }
   }
  if (count($values)){
	  $sp = split("@",$values['email']);
	  $nickname = $sp[0];
	  $Identity =& $db->model('Identity');
	  $Person =& $db->model('Person');
	  $guser = $Identity->find_by( 'email_value', $values['email'] );
	  if ($guser && (get_class($guser) == 'Record')) {
		  $i = $guser;
	  } else {
			$default = base_path(true).'resource/favicon.png';
			$size = 40;
			$grav_url = "http://www.gravatar.com/avatar.php?
			gravatar_id=".md5( strtolower($values['email']) ).
			"&default=".urlencode($default).
			"&size=".$size;
	  	$i = make_identity(array(
	      $nickname,
	      $grav_url,
	      $values['name'],
	      '',
	      '',
	      ''
	    ),true);
	    if (!$i)
	      trigger_error('sorry I was unable to create an identity', E_USER_ERROR);
	    $i->set_value('email_value',$values['email']);
	    $i->save_changes();
	  }
    $title = $values['title'];
    $tweeturl = $values['url'];
    $request->set_param(array('post','parent_id'),0);
    $request->set_param(array('post','uri'),$tweeturl);
    $request->set_param(array('post','url'),$tweeturl);
    $request->set_param(array('post','title'),$title);
    $request->set_param(array('post','profile_id'),$i->id);
    $table = 'posts';
    $content_type = 'text/html';

	  global $prefix;
	  $prefix = $microblog->value."_";
	  $db->prefix = $prefix;

    $rec = $Post->base();
    $fields = $Post->fields_from_request($request);
    $fieldlist = $fields['posts'];
    foreach ( $fieldlist as $field=>$type )
      $rec->set_value( $field, $request->params[strtolower(classify($table))][$field] );

		$exists = $Post->find_by(array('url'=>$tweeturl));
		if (!$exists){

	    $rec->save_changes();
	    $rec->set_etag($i->person_id);
	    trigger_after( 'insert_tweets_via_cron', $Post, $rec );
    }

		global $prefix;
		$prefix = "";
		$db->prefix = $prefix;

  }
}

$options[1]['busy'] = 0;
update_option($options[0],$options[1]);

				} elseif (substr($options[0],9,13) == 'importtwitter') {
					// do twitter
			  // http://abrah.am
			  lib_include('twitteroauth');
  			// look for a twitter user  
			  global $db;
			  $TwitterUser =& $db->model('TwitterUser');
			  $tu = $TwitterUser->find_by(array('twitter_id'=>$uid));
			  if ($tu) {
			    $latest = false;
			    $key = $tu->oauth_key;
			    $secret = $tu->oauth_secret;
			    $consumer_key = environment( 'twitterKey' );
			    $consumer_secret = environment( 'twitterSecret' );    
			    $to = new TwitterOAuth(
			      $consumer_key, 
			      $consumer_secret, 
			      $tu->oauth_key, 
			      $tu->oauth_secret
			    );
			    $timelineurl = 'https://twitter.com/statuses/friends_timeline.atom';
			    if ($options[1]['last_id']) {
			      $timelineurl .= '?since_id='.$options[1]['last_id'].'&count=200';
			      admin_alert('starting from '.$options[1]['last_id']. ' for '.$tu->screen_name);
			    }
			    $data = $to->OAuthRequest($timelineurl, array(), 'GET');
			    $xmlarray = array();
			    $xmlarray = xml2array($data, $get_attributes = 1, $priority = 'value');	
			    if (count($xmlarray)) {
			      $sincespl = split(":",$xmlarray['feed']['entry'][0]['id']['value']);
			      $sincespl2 = split("/",$sincespl[3]);
			      if (isset($sincespl2[5]) && ($sincespl2[5] >0)) {
			        $latest = $sincespl2[5];
			        if (isset($xmlarray['feed']['entry'])) {
			          foreach($xmlarray['feed']['entry'] as $entry) {
			            global $request,$db;
			            $Post =& $db->model('Post');
			            if (isset($entry['title']['value'])) {
			              $u = add_tweet_user( $entry );
			              $title = $entry['title']['value'];
			              $tweeturl = $entry['link'][0]['attr']['href'];
			              $request->set_param(array('post','parent_id'),0);
			              $request->set_param(array('post','uri'),$tweeturl);
			              $request->set_param(array('post','url'),$tweeturl);
			              $request->set_param(array('post','title'),$title);
			              $request->set_param(array('post','profile_id'),$u->profile_id);
			              $table = 'posts';
			              $content_type = 'text/html';

									  global $prefix;
									  $prefix = $microblog->value."_";
									  $db->prefix = $prefix;

			              $rec = $Post->base();
			              $fields = $Post->fields_from_request($request);
			              $fieldlist = $fields['posts'];
			              foreach ( $fieldlist as $field=>$type )
			                $rec->set_value( $field, $request->params[strtolower(classify($table))][$field] );
			              $Identity =& $db->model('Identity');
			              $id = $Identity->find($u->profile_id);
			              $rec->save_changes();
			              $rec->set_etag($id->person_id);
			              trigger_after( 'insert_tweets_via_cron', $Post, $rec );
			            }
			
									global $prefix;
									$prefix = "";
									$db->prefix = $prefix;
			
			          }
			        }
			      }
			    }
			  }
			  if ($latest)
			    $options[1]['last_id'] = $latest;
			  $options[1]['busy'] = 0;
			  update_option($options[0],$options[1]);
}
			}
exit;




$Feed =& $db->model('Feed');
$Feed->set_limit(1000);
$Feed->find();

if ( '' == get_option( 'cloud_domain' ) )
  set_default_omb_cloud_options();

while ($f = $Feed->MoveNext()) {
	$dow = date("w",time());
	if ($dow != $f->day_of_sub && !empty($f->cloud_domain)){
		$f->set_value('day_of_sub',$dow);
		$f->save_changes();
    $subscribe_url = "http://" . $f->cloud_domain . ":" . $f->cloud_port . "" . $f->cloud_path . "";
		$params = array(
			'notifyProcedure'=>get_option('cloud_function'),
			'port'=>get_option('cloud_port'),
			'path'=>'/api/rsscloud/callback',
			'protocol'=>get_option('cloud_protocol'),
			'url1'=>$f->xref,
			'domain'=>get_option('cloud_domain')
		);
    require_once(ABSPATH.WPINC.'/class-snoopy.php');
		$snoop = new Snoopy;
			$snoop->submit(
				$subscribe_url,
				$params
			);
		admin_alert("rssCloud renew: " . $f->title);
	}
}

exit;



//[id] => 544591116
//   [url] => http://www.facebook.com/Timoreilly
//   [name] => Tim O'Reilly
//   [pic_square] => http://profile.ak.fbcdn.net/v52/52/103/q544591116_1716.jpg
//   [type] => user

function add_facebook_user($data) {
  global $db;
  $Identity =& $db->model('Identity');
  $Person =& $db->model('Person');
  $FacebookUser =& $db->model('FacebookUser');
  $faceuser = $FacebookUser->find_by( 'facebook_id',$_SESSION['fb_userid'] );
  if ($faceuser) {
    if (!$faceuser->profile_id) {
      $i = make_identity($values);
      if (!$i)
        trigger_error('sorry I was unable to create an identity', E_USER_ERROR);
      $faceuser->set_value('profile_id',$i->id);
      $faceuser->save_changes();
      if (!$faceuser)
        trigger_error('sorry I was unable to create a facebook user', E_USER_ERROR);
    } else {
      $i = $Identity->find($faceuser->profile_id);
      if (!$i)
        trigger_error('sorry I was unable to find the identity', E_USER_ERROR);
    }
  } else {
    $i = make_identity($values);
    if (!$i)
      trigger_error('sorry I was unable to create an identity', E_USER_ERROR);
    $faceuser = make_fb_user($user,$i->id);
  }
}

function add_tweet_user($data) {
  global $db;
  $Identity =& $db->model('Identity');
  $Person =& $db->model('Person');
  $sincespl = split(":",$data['id']['value']);
  $sincespl2 = split("/",$sincespl[3]);
  $nickname = strtolower($sincespl2[3]);
  $inick = $nickname;
  $name = $data['author']['name']['value'];
  $TwitterUser =& $db->model('TwitterUser');
  $twuser = $TwitterUser->find_by( 'screen_name',$nickname );
  if ($twuser)
    return $twuser;
  global $db,$prefix,$request;
  $p = $Person->base();
  $p->save();
  $i = $Identity->base();
  for ( $j=1; $j<50; $j++ ) {
    $sql = "SELECT nickname FROM ".$prefix."identities WHERE nickname LIKE '".$inick."' AND (post_notice = '' OR post_notice IS NULL)";
    $result = $db->get_result( $sql );
    if ($db->num_rows($result) > 0) {
      $inick = strtolower($sincespl2[3]).$j;
    } else {
      break;
    }
  }
  
  $i->set_value( 'nickname', $inick );
  $i->set_value( 'avatar', $data['link'][1]['attr']['href'] ); 
  $i->set_value( 'fullname', $name );
  $i->set_value( 'homepage', $data['author']['uri']['value'] );
  $i->set_value( 'label', 'profile 1' );
  $i->set_value( 'person_id', $p->id );
  $i->save_changes();
  $i->set_etag($p->id);
  $i->set_value( 'profile', "http://twitter.com/".$nickname );
  $i->set_value( 'profile_url', "http://twitter.com/".$nickname );
  $i->set_value( 'post_notice', 'http://twitter.com' );
  $i->save_changes();
  
  $twuser = $TwitterUser->base();
  $twuser->set_value('screen_name',strtolower($nickname));
  $twuser->set_value('url',$data['author']['uri']['value']);
  $twuser->set_value('name',$name);
  $twuser->set_value('profile_id',$i->id);
  $twuser->set_value('profile_image_url',$data['link'][1]['attr']['href']);
  $twuser->save_changes();
  
  return $twuser;
  
}







