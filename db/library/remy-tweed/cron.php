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
if (DB_NAME)
  $database = DB_NAME;
if (DB_USER)
  $username = DB_USER;
if (DB_PASSWORD)
  $password = DB_PASSWORD;
if (DB_HOST)
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
$Setting->find_by(array(
  'eq'    => 'like',
  'name'  => 'config%'
));
while ($s = $Setting->MoveNext()) {
  $set = split('\.',$s->name);
  if (is_array($set) && $set[0] == 'config') {
    if ($set[1] == 'env') {
      $env[$set[2]] = $s->value;
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
    $options = unserialize(get_option($optname));
    if (!$options) {
      $options = array();
      $options['busy'] = 0;
      $options['last_id'] = 0;
      $options['latest_id'] = 0;
      add_option($optname,$options);
    }
    if (!$options['busy']) {
      $options['busy'] = 1;
      //update_option($optname,$options);
      $tu = split("_",$key);
      $follow[$tu[1]] = array($optname,$options);
    }
  }
}

foreach ($follow as $tuid=>$options) {
  
  // http://abrah.am
  lib_include('twitteroauth');
  
  global $db;
  $TwitterUser =& $db->model('TwitterUser');
  $tu = $TwitterUser->find($tuid);
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




