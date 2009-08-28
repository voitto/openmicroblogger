<?php

global $request,$omb_routes,$db,$ombversion;

// OpenMicroblogger 0.3.0
$ombversion = "0.3.0";

// Openmicroblogging 0.1
define( OMB_VERSION, 'http://openmicroblogging.org/protocol/0.1' );
define( OAUTH_VERSION, 'http://oauth.net/core/1.0' );

$omb_routes = array(
  'local_subscribe',
  'local_unsubscribe',
  'oauth_omb_post',
  'oauth_omb_update',
  'oauth_omb_subscribe',
  'oauth_omb_finish_subscribe',
  'access_token',
  'request_token',
  'oauth_authorize',
  'mobile_settings',
  'mobile_event',
  'migrate'
);

foreach ($omb_routes as $func)
  $request->connect( $func );

$request->connect(
  'email/:ident',
  array(
    'requirements' => array ( '[A-Za-z0-9]+' )
  )
);

$request->connect( 'groups', array(
    'resource'=>'groups'
));

$request->connect( 'oembed', array(
  'resource'=>'posts',
  'action'=>'oembed'
));

$request->connect(
  ':nickname',
  array(
    'resource'=>'identities',
    'action'=>'entry',
    'requirements' => array ( '[A-Za-z0-9_.]+' )
  )
);

$request->connect(
  ':nickname/replies',
  array(
    'resource'=>'posts',
    'action'=>'replies',
    'requirements' => array ( '[A-Za-z0-9_.]+' )
  )
);

$request->connect(
  ':nickname/settings',
  array(
    'resource'=>'identities',
    'action'=>'entry',
    'requirements' => array ( '[A-Za-z0-9_.]+' )
  )
);

$request->connect(
  ':nickname/subscriptions',
  array(
    'resource'=>'subscriptions',
    'action'=>'following',
    'requirements' => array ( '[A-Za-z0-9_.]+' )
  )
);

$request->connect(
  ':nickname/subscribers',
  array(
    'resource'=>'subscriptions',
    'action'=>'followers',
    'requirements' => array ( '[A-Za-z0-9_.]+' )
  )
);


$request->connect(
  ':nickname/subscriptions/:followingpage',
  array(
    'resource'=>'subscriptions',
    'action'=>'following',
    'requirements' => array ( '[A-Za-z0-9_.]+','[0-9]+' )
  )
);

$request->connect(
  ':nickname/subscribers/:followerspage',
  array(
    'resource'=>'subscriptions',
    'action'=>'followers',
    'requirements' => array ( '[A-Za-z0-9_.]+','[0-9]+' )
  )
);


$request->connect(
  ':resource/by/:byid/:page',
  array(
    'requirements' => array ( '[A-Za-z0-9_.]+', '[0-9]+', '[0-9]+' )
  )
);


$request->connect(
  ':resource/for/:forid/:page',
  array(
    'requirements' => array ( '[A-Za-z0-9_.]+', '[0-9]+', '[0-9]+' )
  )
);


before_filter( 'omb_filter_posts', 'get_query' );

// this sucks this should be in /controllers/posts.php

function omb_filter_posts( &$model, &$db ) {
  global $request;
  if ($model->table != 'posts')
    return;
  if (isset($_POST['s']) && !empty($_POST['s'])) {
    $model->set_limit(1000);
    $_SESSION['searchterm'] = $_POST['s'];
    $term = trim($db->escape_string($_POST['s']));
    $term = '%'.$term.'%';
    $where = array(
      'eq'=>'like',
      'title'=>$term,
      'op'=>'OR',
      'body'=>$term
    );
    $model->set_param( 'find_by', $where );
  } elseif (isset($request->params['nickname']) && isset($request->params['byid']) && $request->resource == 'posts' && $model->table == 'posts'){
    $where = array(
      'profile_id'=>$request->params['byid'],
      'parent_id'=>0
    );
    if ($request->action == 'replies')
      $where = array(
        'eq'=>'like',
        'title'=>'%@'.$request->params['nickname'].'%'
      );
    
    $model->set_param( 'find_by', $where );
  } elseif (isset($request->params['forid']) && $request->resource == 'posts' && $model->table == 'posts'){
    $model->has_many( 'profile_id:subscriptions.subscribed' );
    $model->set_groupby( 'id' );
    $where = array(
      'op'=>'OR',
      'profile_id'=>$request->params['forid'],
      'subscriptions.subscriber'=>$request->params['forid']
    );
    $model->set_param( 'find_by', $where );
  } elseif (isset($request->params['byid']) && $request->resource == 'posts' && $model->table == 'posts'){
    $where = array(
      'profile_id'=>$request->params['byid'],
      'parent_id'=>0
    );
    $model->set_param( 'find_by', $where );
  } elseif (environment('threaded') && in_array($request->action, array('index','get')) && $model->table == 'posts' && $request->resource == 'posts' && $request->id == 0) {
    $where = array(
      'parent_id'=>0
    );
    $model->set_param( 'find_by', $where );
  } elseif ($request->action == 'index' && $model->table == 'posts' && $request->resource == 'posts' && $request->id == 0) {
    $where = array(
      'local'=>1
    );
    //$model->set_param( 'find_by', $where );
  }
}


// normally a token/invite is for a private resource,
// which get redirected to _email template
// this is a hook to catch tokens in public resource URIs

before_filter('catch_invite_token','get');

function catch_invite_token(&$request,&$route) {
  if (isset($request->params['ident'])) {
    render( 'action', 'email' );
    exit;
  }
}


// fix this so it happens AFTER all filters XXX

// this is a filter to redirect to the post that was replied to

//after_filter( 'forward_after_reply', 'insert_from_post' );

//function forward_after_reply( &$model, &$rec ) {
  
//  global $request,$db;
  
//  if (!($model->table == 'posts'))
//    return;
  
//  if (isset($request->params['post']['parent_id']))
//    redirect_to(array('resource'=>'posts','id'=>$request->params['post']['parent_id']));
  
//}


// this is a filter to redirect to the reviewed resource

after_filter( 'forward_after_review', 'insert_from_post' );

function forward_after_review( &$model, &$rec ) {
  
  global $db;
  
  if (!($model->table == 'reviews'))
    return;
  
  $Entry =& $db->model('Entry');
  
  $e = $Entry->find($rec->target_id);
  
  if ($e)
    redirect_to(array('resource'=>$e->resource,'id'=>$e->record_id));
  else
    trigger_error('Sorry, I was not able to save the review.', E_USER_ERROR );
  
}


// this is a filter to handle posts from the prologue theme

before_filter( 'wp_set_post_fields', 'insert_from_post' );

function wp_set_post_fields( &$model, &$rec ) {
  global $db,$request;

  if (isset($_POST['postfile'])) {
    $Upload =& $db->model('Upload');
    $u = $Upload->find_by('name',urldecode($_POST['postfile']));
    if ($u) {
      $_FILES = array(
        'post' => array( 
          'name' => array( 'attachment' => $u->name ),
          'tmp_name' => array( 'attachment' => $u->tmp_name )
      ));
      $db->delete_record( $u );
    }
  }
  
  if ( !(isset($_POST['posttext'])) || !(isset($_POST['tags'])) )
    return;
  
  $tinyurl = '';
  
  if (isset( $_POST['link']['href'] )) {
    $href = trim($_POST['link']['href']);
    
    $result = false;
    if (!empty($href)) {
      
      if (strpos($href, 'http') === false)
        $href = 'http://'.$href;
      
      if (strpos($href, 'tinyurl') === false) {

        $tinyapi = 'http://tinyurl.com/api-create.php?url=' . $href;
        //$ch = curl_init($tinyapi);
        //$result = curl_exec($ch);
        //curl_close($ch);
      
        //$tinyUrl = @file($tinyapi);
        //if (isset($tinyUrl[0]))
        //  $result = $tinyUrl[0];
        
        $curl = curl_init( $tinyapi );
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec( $curl );
        curl_close( $curl );
        
        if ($result)
          $tinyurl = ' '.trim($result);
        
        //$tinyHook = @fopen('http://tinyurl.com/api-create.php?url=$yourUrl,'r');
        //if ($tinyHook) {
        //    $tinyurl = fread($tinyHook, 1024);
        //    fclose($tinyHook);
        //}
        
      } else {
      
        $tinyurl = $href;
      
      }
      
    }
  }
  
  if ( isset( $_POST['posttext'] ))
    $request->set_param( array( 'post', 'title' ), $_POST['posttext'].$tinyurl );
  
  if ( isset( $_POST['profile_id'] ))
    $request->set_param( array( 'post', 'profile_id' ), $_POST['profile_id'] );
  
  $request->set_param( array( 'post', 'parent_id' ), 0 );
  
  $Category =& $db->model('Category');
  $Category->set_limit(100);
  $Category->find();
  
  include 'wp-content/language/lang_chooser.php'; //Loads the language-file
  
  $t1 = html_entity_decode($txt['postform_tagit']);
  $t2 = html_entity_decode($_POST['tags']);
  
  if (ereg_replace("[^A-Za-z0-9]","",$t1) == ereg_replace("[^A-Za-z0-9]","",$t2))
    return;
  
  if (strstr( $_POST['tags'], "," ))
    $tags = split( ',', $_POST['tags'] );
  else
    $tags = split( ' ', $_POST['tags'] );
  
  $cats = array();
  
  while ( $c = $Category->MoveNext() )
    $cats[strtolower($c->name)] = $c->id;
  
  $newcount = count($cats);
  
  foreach ( $tags as $t ) {
    $t = trim($t);
    $tl = strtolower( $t );
    if (array_key_exists( $tl, $cats )) {
      $request->set_param( "category".$cats[$tl], $tl );
    } else {
      $request->set_param( "category".$newcount, $t );
      $newcount++;
    }
  }
  
}


after_filter( 'wp_set_post_fields_after', 'insert_from_post' );

function wp_set_post_fields_after( &$model, &$rec ) {
  global $request;
  if ($request->action == 'oauth_omb_post')
    return;
  if ($model->table == 'posts') {
    $rec->set_value( 'uri', $request->url_for( array(
      'resource'=>'__'.$rec->id,
    )));
    if (isset($_POST['parent_id']) && is_numeric($_POST['parent_id']) && is_ajax()) {
      $rec->set_value( 'parent_id',$_POST['parent_id']);
      $rec->set_value( 'local',1);
    }
    $rec->save_changes();
  }
}


after_filter('do_ajaxy_fileupload','routematch');

function do_ajaxy_fileupload(&$request,&$route) {
  
  global $db;
  
  if (!isset($_FILES['Filedata']['name']))
    return;
  
  if (!is_writable('cache'))
    exit;
  
  $result = $db->get_result("DELETE FROM ".$db->prefix."uploads WHERE name = '".$db->escape_string(urldecode($_FILES['Filedata']['name']))."'");
  $tmp = 'cache'.DIRECTORY_SEPARATOR.make_token();
  $tmp .= ".". extension_for(type_of($_FILES['Filedata']['name']));
  
  $Upload =& $db->model('Upload');
  $u = $Upload->base();
  $u->set_value('name', urldecode($_FILES['Filedata']['name']));
  $u->set_value('tmp_name', $tmp);
  $u->save_changes();
  
  move_uploaded_file($_FILES['Filedata']['tmp_name'], $tmp);
  echo "200 OK";
  exit;
  
}


after_filter('set_identity_from_nick','routematch');

function set_identity_from_nick(&$request,&$route) {
  
global $db;

  if (!(isset($request->params['nickname'])))
    return;

  $nick = $db->escape_string(urldecode($request->params['nickname']));
  $nick = split( '\.', $nick );
  if (is_array($nick)) {
    if (isset($nick[1]))
      $request->set('client_wants',$nick[1]);
    $nick = trim($nick[0]);
  } else {
    $nick = trim($nick);
  }

  if (isset($db->tables[$nick])) {
    $request->set_param('resource',$nick);
    if (!(isset($_POST['method'])))
      $request->set_param('action','index');
    return;
  }
  
  if ($db->has_table($nick)) {
    $request->set_param('resource',$nick);
    if (!$request->id && $request->action == 'entry')
      $request->set_param('action','index');
    return;
  }

  if ($request->route_exists($request->params['nickname']))
    return;
  
  
  if (substr($nick,0,2) == '__') {
    $request->set_param('id',substr($nick,2));
    $request->set_param('resource','posts');
    $request->set_param('action','entry');
    return;
  }
  
  $Identity =& $db->model('Identity');
  
  $id = false;
  
  if (substr($nick,0,1) == '_') {
    $Member = $Identity->find(substr($nick,1));
    $id = $Member->id;
   } else {
    $sql = "SELECT id FROM ".$db->prefix."identities WHERE nickname LIKE '".$db->escape_string($nick)."' AND (post_notice = '' OR post_notice IS NULL)";
    $result = $db->get_result( $sql );
    if ($db->num_rows($result) == 1)
      $id = $db->result_value($result,0,"id");
  }

  if (!$id) {
    // check for the nickname in a previous identity
    $Revision =& $db->model('Revision');
    $Revision->set_limit(1000);
    $Revision->unset_relation('entries');
    $Revision->has_one('target_id:entries.id');
    $where = array(
      'entries.resource'=>'identities'
    );
    $Revision->set_param( 'find_by', $where );
    $Revision->find();
    while ($r = $Revision->MoveNext()) {
      $i = unserialize($r->data);
      if (is_object($i) && $nick == $i->nickname)
        $id = $i->id;
    }
  }
  
  if (substr($nick,0,1) == '_' && $id) {
    $request->set_param('resource','identities');
    $request->set_param('id',$id);
  } elseif ($id) {
    if (empty($request->client_wants)) {
      if (count($request->activeroute->patterns) == 1 ) {
        $request->set_param('resource','posts');
        if ($request->action == 'entry')
          $request->set_param('action','index');
        $request->set_param('byid',$id);
      } elseif ($request->resource == 'identities') {
        $request->set_param('id',$id);
      } else {
        $request->set_param('byid',$id);
      }
      if (!(isset($request->page)))
        $request->set_param('page',1);
    } else {
      $request->set_param('id',$id);
    }
  } else {
    
    // the nickname did not match a local user
    
    // check for the nickname at twitter.com
    $url = "http://twitter.com/".$nick;
    require_once(ABSPATH.WPINC.'/class-snoopy.php');
    $snoop = new Snoopy;
    $snoop->agent = 'OpenMicroBlogger http://openmicroblogger.org';
    $snoop->submit($url);
    if (strpos($snoop->response_code, '200')) {
      redirect_to($url);
    } else {
      trigger_error('sorry the nickname was not found on this site, nor at Twitter.com', E_USER_ERROR );
    }
  
  }

}


before_filter( 'omb_request_munger', 'routematch' );

function omb_request_munger( &$request, &$route ) {
  
  global $omb_routes;
  
  // look for a dbscript omb Route in the POST/GET params
  $params = array_merge($_GET,$_POST);
  foreach($omb_routes as $func) {
    if (array_key_exists($func,$params)) {
        // if found, lie to the mapper about the URI
        if (pretty_urls())
          $request->set('uri',$request->base."".$func);
        else
          $request->set('uri',$request->base."?".$func);
        $request->set('params', array($func));
    }
  }
}


global $omb_services;

$omb_services = array(
  'http://oauth.net/discovery/1.0',
  OMB_VERSION,
  OAUTH_VERSION . '/endpoint/request',
  OAUTH_VERSION . '/endpoint/authorize',
  OAUTH_VERSION . '/endpoint/access',
  OMB_VERSION   . '/postNotice',
  OMB_VERSION   . '/updateProfile'
);


function get_remote_xrds($at_url) {
  global $request;
  $wp_plugins = "wp-plugins" . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR . "enabled";
  $path = plugin_path() . $wp_plugins . DIRECTORY_SEPARATOR . 'wp-openid' . DIRECTORY_SEPARATOR;
  add_include_path( $path ); 
  require_once "Auth/Yadis/Yadis.php";
  
  $fetcher = Auth_Yadis_Yadis::getHTTPFetcher();
  $yadis = Auth_Yadis_Yadis::discover($at_url, $fetcher);
  
  if (!$yadis || $yadis->failed)
    trigger_error("Sorry but the Yadis doc was not found at the profile URL", E_USER_ERROR);
  
  $xrds =& Auth_Yadis_XRDS::parseXRDS($yadis->response_text);
  
  if (!$xrds)
    trigger_error("Sorry but the XRDS data was not found in the Yadis doc", E_USER_ERROR);
  
  $yadis_services = $xrds->services(array('filter_MatchesAnyOMBType'));
  
  foreach ($yadis_services as $service) {
    $type_uris = $service->getTypes();
    $uris = $service->getURIs();
    if ($type_uris && $uris) {
      foreach ($uris as $uri) {
        $xrd = xrdends($uri,$xrds);
        $ends = $xrd->services(array('filter_MatchesAnyOMBType'));
        foreach($ends as $serv) {
          $typ = $serv->getTypes();
          global $omb_services;
          $end = "";
          foreach($typ as $t) {
            if (in_array($t,$omb_services))
              $end = $t;
            if ($t == OAUTH_VERSION . '/endpoint/request') {
              $data = $serv->getElements('xrd:LocalID');
              $localid = $serv->parser->content($data[0]);
            }
          }
          $req = $serv->getURIs();
          $endpoints[$end] = $req[0];
        }  
      }
    }
  }
  
  return array($localid,$endpoints);
  
}


function filter_MatchesAnyOMBType(&$service)
{
  global $omb_services;

  $uris = $service->getTypes();
  
  foreach ($uris as $uri) {
      if (in_array($uri, $omb_services)) {
          return true;
      }
  }

  return false;
}


// subscribe step 1 (remote service)

// a form on this site, submitted by a non-authenticated visitor

function oauth_omb_subscribe( &$vars ) {
  
  extract($vars);
  
  if (!(environment('openid_version') > 1)
   || (!$db->has_table('oauth_consumers')
   || (!$db->has_table('oauth_tokens')
  )))
  $db->create_openid_tables();
  
  wp_plugin_include(array(
    'wp-oauth'
  ));

  $key = $request->base;
  $secret = '';
  
  $xrds = get_remote_xrds($request->listener_url);
  
  if (is_array($xrds)) {
    $localid = $xrds[0];
    $endpoints = $xrds[1];
  } else {
    trigger_error('unable to fetch remote XRDS document', E_USER_ERROR );
  }
  
  $_SESSION['subscriber_request_token_url'] = $endpoints[OAUTH_VERSION . '/endpoint/request'];
  $_SESSION['subscriber_access_token_url'] = $endpoints[OAUTH_VERSION . '/endpoint/access'];
  $_SESSION['subscriber_authorize_url'] = $endpoints[OAUTH_VERSION . '/endpoint/authorize'];
  $_SESSION['subscriber_notice_url'] = $endpoints[OMB_VERSION . '/postNotice'];
  $_SESSION['subscriber_update_url'] = $endpoints[OMB_VERSION . '/updateProfile'];
  
  if (empty($localid))
    trigger_error('sorry, the localid was not found in the XRDS document', E_USER_ERROR );

  $listener_url = trim($request->listener_url);

  $listener_uri = trim($localid);
  
  $_SESSION['listenee_id'] = trim($request->listenee_id);
  $_SESSION['listener_url'] = $listener_url;
  $_SESSION['listener_uri'] = $listener_uri;
  
  $sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
  $consumer = new OAuthConsumer($key, $secret, NULL);
  
  $url = $_SESSION['subscriber_request_token_url'];
  
  $parsed = parse_url($url);
  $params = array();
  
  parse_str($parsed['query'], $params);
  
  $req = OAuthRequest::from_consumer_and_token( $consumer, NULL, "POST", $url, $params );
  $req->set_parameter( 'omb_listener', $_SESSION['listener_uri'] );
  $req->set_parameter( 'omb_version', OMB_VERSION );
  $req->sign_request( $sha1_method, $consumer, NULL );
  
  $post_to = $req->get_normalized_http_url();
  $post_data = $req->to_postdata();
  
  //echo $post_to."<br />".$post_data."<br />"; exit;
  
  $client = Auth_Yadis_Yadis::getHTTPFetcher();
  
  //for ($i=0; $i<5; $i++ ) {
    $result = $client->post( $post_to, $post_data );
  //  if (strpos($result->body, 'oauth_token') === false) {
  //    sleep(2);
  //  } else {
  //    break;
  //  }
  //}
  
  parse_str( $result->body, $return );
  
  if (is_array($return) && count($return) > 0) {
    $rtoken_secret = $return['oauth_token_secret'];
    $rtoken = $return['oauth_token'];
  } else {
    echo "sorry, you will have to go back and submit the form again. the server \"".$post_to."\" said \"".$result->body."\" when I posted this data: <br /><br /> ".$post_data; exit;
  }
  $_SESSION['rtoken_secret'] = $rtoken_secret;
  $_SESSION['rtoken'] = $rtoken;
  
  // finish_subscribe saves the profile
  $callback_url = $request->url_for( 'oauth_omb_finish_subscribe' );
  
  $consumer = new OAuthConsumer($key, $secret, NULL);
  $token = new OAuthToken($rtoken, $rtoken_secret);
  
  $url = $_SESSION['subscriber_authorize_url'];
  $parsed = parse_url($url);
  $params = array();
  parse_str($parsed['query'], $params);
  
  $req = OAuthRequest::from_consumer_and_token($consumer, $token, 'GET', $url, $params);

  $omb_subscribe = array();
  
  $Identity =& $db->get_table( 'identities' );
  
  $i = $Identity->find( $_SESSION['listenee_id'] );
  
  if ($i) {
    
    if (!(isset($i->nickname)))
      trigger_error('the identity does not have a nickname', E_USER_ERROR);
    
    if (!empty($i->profile_url))
      $profile_url = $i->profile_url;
    else
      $profile_url = $i->profile;
    
    $omb_subscribe = array(
      'omb_version'           => OMB_VERSION,
      'omb_listener'          => $_SESSION['listener_uri'],
      'omb_listenee'          => $i->profile,
      'omb_listenee_profile'  => $profile_url,
      'omb_listenee_nickname' => $i->nickname,
      'omb_listenee_license'  => $i->license,
      'omb_listenee_avatar'   => $i->avatar
    );
  } else {
    trigger_error('Unable to find the listenee, sorry', E_USER_ERROR);
  }
  
  foreach($omb_subscribe as $k=>$v)
    $req->set_parameter($k, $v);

  $req->set_parameter('oauth_callback', $callback_url);

  $req->sign_request($sha1_method, $consumer, $token);
//echo $req->to_url(); exit;
  header('Location: '.$req->to_url(),true,303);
  exit;  
}

// subscribe step 2 (local service)

// a form was submitted at another site
// and it has bounced a request on behalf of
// an authenticated user of this site

function oauth_authorize( &$vars ) {
  
  extract($vars);
  
  if (!(environment('openid_version') > 1)
   || (!$db->has_table('oauth_consumers')
   || (!$db->has_table('oauth_tokens')
  )))
  $db->create_openid_tables();

  wp_plugin_include(array(
    'wp-oauth'
  ));

  global $wpdb;
  global $userdata;
  
  if(!$_GET['oauth_token'] && !$_POST['authorize'])
  
    trigger_error('Sorry, the remote service did not send a subscription token. The error has been recorded, you may go back and try the subscription again.', E_USER_ERROR);
  
  $NO_oauth = true;
  //require_once dirname(__FILE__).'/common.inc.php';
  $store = new OAuthWordpressStore();
  
  if(!$_POST['authorize']) {
    $token = $wpdb->escape($_GET['oauth_token']);
    $consumer_key = $store->lookup_token('','request',$token);//verify token
    if(!$consumer_key) die('Invalid token passed');
  }//end if ! POST authorize

  get_currentuserinfo();
  
  if(!$userdata->ID) {
    redirect_to($request->url_for('openid_login'));
  }//end if ! userdata->ID
  
  $xrds = get_remote_xrds(trim(urldecode($_GET['omb_listenee_profile'])));
  
  if (is_array($xrds)) {
    $localid = $xrds[0];
    $endpoints = $xrds[1];
  } else {
    trigger_error('unable to fetch remote XRDS document', E_USER_ERROR );
  }
  
  $postNotice     = $endpoints[ OMB_VERSION . '/postNotice'    ];
  $updateProfile  = $endpoints[ OMB_VERSION . '/updateProfile' ];
  
  $listenee_params = array(
    'omb_listenee_fullname'  => 'fullname',
    'omb_listenee_profile'   => 'profile_url',
    'omb_listenee_nickname'  => 'nickname',
    'omb_listenee_license'   => 'license',
    'omb_listenee'           => 'url',
    'omb_listenee_homepage'  => 'homepage',
    'omb_listenee_bio'       => 'bio',
    'omb_listenee_location'  => 'locality',
    'omb_listenee_avatar'    => 'avatar'
  );
  
  $Identity =& $db->get_table( 'identities' );
  $Person =& $db->get_table( 'people' );
  $Subscription =& $db->model('Subscription');
  
  $prof = urldecode($_GET['omb_listenee']);
  
  $i = $Identity->find_by( 'profile', $prof );
  
  if (!$i) {
    // need to create the identity (and person?) because it was not found
    $p = $Person->base();
    $p->save();
    
    // CREATE USER
    
    $i = $Identity->base();
    $i->set_value( 'profile', $prof );
    $i->set_value( 'label', 'profile 1' );
    $i->set_value( 'person_id', $p->id );
    
    foreach($listenee_params as $k=>$v ) {
      if (isset($_GET[$k])) {
        $i->set_value( $v, urldecode($_GET[$k]) );
      }
    }
    
    if ("/" == substr($i->attributes['url'],-1))
      $i->attributes['url'] = substr($i->attributes['url'],0,-1);

    if (empty($i->attributes['url']) || !($Identity->is_unique_value( $i->attributes['url'], 'url' )))
      $i->set_value( 'url', $i->attributes['profile_url'] );
    
    $i->set_value( 'update_profile', $updateProfile );
    $i->set_value( 'post_notice', $postNotice );
    
    $i->save_changes();
    $i->set_etag($p->id);
    
  }
  
  $_SESSION['listenee_id'] = $i->id;
  
  if($_POST['authorize']) {
    session_start();
    $_GET['oauth_callback'] = $_SESSION['oauth_callback']; unset($_SESSION['oauth_callback']);
    $token = $_SESSION['oauth_token']; unset($_SESSION['oauth_token']);
    $consumer_key = $_SESSION['oauth_consumer_key']; unset($_SESSION['oauth_consumer_key']);
    if($_POST['authorize'] != 'Ok') {
      if($_GET['oauth_callback']) {
        header('Location: '.urldecode($_GET['oauth_callback']),true,303);
      } else {
        //get_header();
        echo '<h2 class="omb-center">You chose to cancel authorization.  You may now close this window.</h2>';
        //get_footer();
      }//end if-else callback
      exit;
    }//cancel authorize
    $consumers = $userdata->oauth_consumers ? $userdata->oauth_consumers : array();
    $services = get_option('oauth_services');
    $yeservices = array();
    foreach($services as $k => $v)
      if(in_array($k, array_keys($_GET['services'])))
        $yeservices[$k] = $v;
    $consumers[$consumer_key] = array_merge(array('authorized' => true), $yeservices);//it's an array so that more granular data about permissions could go in here
    $userdata->oauth_consumers = $consumers;
    update_usermeta($userdata->ID, 'oauth_consumers', $consumers);
  }//end if authorize
  
  if($userdata->oauth_consumers && in_array($consumer_key,array_keys($userdata->oauth_consumers))) {
    $store->authorize_request_token($consumer_key, $token, $userdata->ID);
    if($_GET['oauth_callback']) {
      
      $Subscription =& $db->model('Subscription');
      
      $sub = $Subscription->find_by( array(
        'subscribed'=>$_SESSION['listenee_id'],
        'subscriber'=>get_profile_id()
      ));
      
      if (!$sub) {
        $s = $Subscription->base();
        $s->set_value( 'subscriber', get_profile_id() );
        $s->set_value( 'subscribed', $_SESSION['listenee_id'] );
        $s->save_changes();
        $s->set_etag(get_person_id());
      }
      
      // response to omb remote service
      
      $i = get_profile();
      
      if (!empty($i->profile_url))
        $profile_url = $i->profile_url;
      else
        $profile_url = $i->profile;
      
      $omb_subscriber = array(
        'omb_version'           => OMB_VERSION,
        'omb_listener_profile'  => $profile_url,
        'omb_listener_nickname' => $i->nickname,
        'omb_listener_license'  => $i->license,
        'omb_listener_fullname' => $i->fullname,
        'omb_listener_homepage' => $i->homepage,
        'omb_listener_bio'      => $i->bio,
        'omb_listener_location' => $i->locality,
        'omb_listener_avatar'   => $i->avatar
      );
      
      if (!(strpos($_GET['oauth_callback'], '?') === false))
        $profileparams = "?";
      else
        $profileparams = "&";
  
      foreach($omb_subscriber as $key=>$item)
        $profileparams .= $key."=".urlencode($item).'&';
      
      $profileparams .= "oauth_token=".$token;
      
      header('Location: '.urldecode($_GET['oauth_callback']).$profileparams,true,303);
    } else {
      //get_header();
      echo '<h2 class="omb-center">Authorized!  You may now close this window.</h2>';
      //get_footer();
    }//end if-else callback
    exit;
  } else {
    session_start();//use a session to prevent the consumer from tricking the user into posting the Yes answer
    $_SESSION['oauth_token'] = $token;
    $_SESSION['oauth_callback'] = $_GET['oauth_callback'];
    $_SESSION['oauth_consumer_key'] = $consumer_key;
    //get_header();
    $description = $store->lookup_consumer_description($consumer_key);
    if($description) $description = 'Allow '.$description.' to post notices to your account?';
      else $description = 'Click &quot;allow&quot; to authorize messages from the remote site.';
    ?>
    <div class="omb-center">
      <h2><?php echo $description; ?></h2>
      <form method="post" action=""><div>
        <div id="omb-desc">
          <ul class="omb-ul">
        <?php
          $services = get_option('oauth_services');
          //foreach($services as $k => $v)
          //  echo '<li><input type="checkbox" checked="checked" name="services['.htmlentities($k).']" /> '.$k.'</li>';
        ?>
          </ul>
          <br />
          <input type="submit" name="authorize" value="Cancel" />&nbsp;&nbsp;&nbsp;&nbsp;
          <input type="submit" name="authorize" value="Ok" />
        </div>
      </div></form>
    </div>
    <?php
    //get_footer();
    exit;
  }//end if user has authorized this consumer
  
}




// subscribe step 3 (remote service)

// we have returned from the visitors home site
// the visitor would like to receive some of our notices

function oauth_omb_finish_subscribe( &$vars ) {

  extract($vars);
  
  wp_plugin_include(array(
    'wp-oauth'
  ));
  
  $req = OAuthRequest::from_request();

  $token = $req->get_parameter('oauth_token');

  if ($token != $_SESSION['rtoken'])
    trigger_error('Sorry the subscription failed', E_USER_ERROR);
  
  $listener_params = array(
    'omb_listener_profile'   => 'profile_url',
    'omb_listener_fullname'  => 'fullname',
    'omb_listener_license'   => 'license',
    'omb_listener_nickname'  => 'nickname',
    'omb_listener_homepage'  => 'homepage',
    'omb_listener_bio'       => 'bio',
    'omb_listener_location'  => 'locality',
    'omb_listener_avatar'    => 'avatar'
  );
  
  $Identity =& $db->get_table( 'identities' );
  $Person =& $db->get_table( 'people' );

  $i = $Identity->find_by( 'profile', $_SESSION['listener_uri'] );
  
  if (!$i) {
    // need to create the identity (and person?) because it was not found
    $p = $Person->base();
    $p->save();
    
    // CREATE USER
    
    $i = $Identity->base();
    $i->set_value( 'url', $_SESSION['listener_uri'] );
    $i->set_value( 'profile', $_SESSION['listener_uri'] );
    $i->set_value( 'label', 'profile 1' );
    $i->set_value( 'person_id', $p->id );
    
    foreach($listener_params as $k=>$v ) {
      if (isset($_GET[$k])) {
        $i->set_value( $v, urldecode($_GET[$k]) );
      }
    }
    
    if ("/" == substr($i->attributes['url'],-1))
     $i->attributes['url'] = substr($i->attributes['url'],0,-1);
        
    if (empty($i->attributes['url']) || !($Identity->is_unique_value( $i->attributes['url'], 'url' )))
      $i->set_value( 'url', $i->attributes['profile_url'] );
    
    $i->save_changes();
    $i->set_etag($p->id);
  }

  $i->set_value( 'update_profile', $_SESSION['subscriber_update_url'] );
  $i->set_value( 'post_notice', $_SESSION['subscriber_notice_url'] );
  $i->save_changes();
  
  $url = $_SESSION['subscriber_access_token_url'];
  $parsed = parse_url($url);
  $params = array();
  parse_str($parsed['query'], $params);
  $sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
  $consumer = new OAuthConsumer($request->base, '', NULL);
  $token = new OAuthToken($_SESSION['rtoken'], $_SESSION['rtoken_secret']);
  $req = OAuthRequest::from_consumer_and_token($consumer, $token, "POST", $url, $params);
  $req->set_parameter('omb_version', OMB_VERSION);

  $req->sign_request($sha1_method, $consumer, $token);

  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($curl, CURLOPT_HEADER, false);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $req->to_postdata());
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  $atoken = curl_exec($curl);
  curl_close($curl);
  
  
  parse_str($atoken, $result);
  
  if (!(isset($result['oauth_token']) && isset($result['oauth_token_secret'])))
    trigger_error( 'could not find the access token!',E_USER_ERROR);
  
  $Subscription =& $db->model( 'Subscription' );
  
  $sub = $Subscription->find_by( array(
    'subscribed'=>$_SESSION['listenee_id'],
    'subscriber'=>$i->id
  ));
  
  if (!$sub) { 
    
    $sub = $Subscription->base();
    $sub->set_value( 'subscriber', $i->id );
    $sub->set_value( 'subscribed', $_SESSION['listenee_id'] );
    $sub->save_changes();
    $p = $i->FirstChild('people');
    $sub->set_etag($p->id);
  
  }
  
  $sub->set_value( 'token', $result['oauth_token'] );
  $sub->set_value( 'secret', $result['oauth_token_secret'] );
  
  $sub->save_changes();
  
  redirect_to(array(
    'resource' => '_'.$_SESSION['listenee_id']
  ));
  
}


// subscribe step 4 (local service)

// a remote site has been authorized to
// connect and it wants its credential

function access_token( &$vars ) {
  
  extract($vars);

  wp_plugin_include(array(
    'wp-oauth'
  ));
  
  $store = new OAuthWordpressStore();
  $server = new OAuthServer($store);
  $sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
  $plaintext_method = new OAuthSignatureMethod_PLAINTEXT();
  $server->add_signature_method($sha1_method);
  $server->add_signature_method($plaintext_method);
  
  $req = OAuthRequest::from_request();
  $token = $server->fetch_access_token($req);
  
  header( 'Status: 200 OK' );
  print $token->to_string().'&xoauth_token_expires='.urlencode($store->token_expires($token));
  exit;
  
}


function request_token( &$vars ) {
  
  extract($vars);
  
  if (!(environment('openid_version') > 1)
   || (!$db->has_table('oauth_consumers')
   || (!$db->has_table('oauth_tokens')
  )))
  $db->create_openid_tables();
  
  wp_plugin_include(array(
    'wp-oauth'
  ));
  
  $consumerkey = $db->escape_string(urldecode($_POST['oauth_consumer_key']));
  
  $consumer_result = $db->get_result("SELECT consumer_key FROM oauth_consumers WHERE consumer_key = '$consumerkey'");
  
  if (!$db->num_rows($consumer_result)>0)
    $result = $db->get_result("INSERT INTO oauth_consumers (consumer_key, secret, description) VALUES ('$consumerkey', '', 'Unidentified Consumer')");
  
  $store = new OAuthWordpressStore();
  $server = new OAuthServer($store);
  $sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
  $plaintext_method = new OAuthSignatureMethod_PLAINTEXT();
  $server->add_signature_method($sha1_method);
  $server->add_signature_method($plaintext_method);
  $params = array();
  foreach($_POST as $key=>$val) {
    if (!($key == 'request_token'))
      $params[$key] = $val;
  }
  $req = OAuthRequest::from_request();
  $token = $server->fetch_request_token($req);
  header( 'Status: 200 OK' );
  print $token->to_string().'&xoauth_token_expires='.urlencode($store->token_expires($token));
  exit;
}


function oauth_omb_post( &$vars ) {
  
  extract($vars);
  
  wp_plugin_include(array(
    'wp-oauth'
  ));
  
  $store = new OAuthWordpressStore();
  $server = new OAuthServer($store);
  $sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
  $plaintext_method = new OAuthSignatureMethod_PLAINTEXT();
  $server->add_signature_method($sha1_method);
  $server->add_signature_method($plaintext_method);
  $req = OAuthRequest::from_request();
  //$token = $server->fetch_access_token($req);
  list($consumer, $token) = $server->verify_request($req);

  $version = $req->get_parameter('omb_version');

  if ($version != OMB_VERSION)
    trigger_error('invalid omb version', E_USER_ERROR);

  $listenee = $req->get_parameter('omb_listenee');

  $Identity =& $db->model('Identity');
  
  $sender = $Identity->find_by('profile',$listenee);
  
  if (!($sender)) {
    header('HTTP/1.1 403 Forbidden');
    exit;
  }
  
  $Subscription =& $db->model('Subscription');
  
  $sub = $Subscription->find_by( array(
    'subscribed'=>$sender->id
  ));
  
  if (!($sub)) {
    header('HTTP/1.1 403 Forbidden');
    exit;
  }
  
  $content = $req->get_parameter( 'omb_notice_content' );
  
  $notice_uri = $req->get_parameter( 'omb_notice' );
  
  $notice_url = $req->get_parameter( 'omb_notice_url' );
  
  $Post =& $db->model( 'Post' );
  
  $p = $Post->find_by( 'uri', $notice_uri );
  
  if (!$p) {
    $p = $Post->base();
    $p->set_value( 'profile_id', $sender->id );
    $p->set_value( 'parent_id', 0 );
    $p->set_value( 'uri', $notice_uri );
    $p->set_value( 'url', $notice_url );
    $p->set_value( 'title', $content );
    $p->save_changes();
    $p->set_etag($sender->person_id);
    trigger_after( 'insert_from_post', $Post, $p );
  }
  
  print "omb_version=".OMB_VERSION;
  exit;
  
}


function oauth_post_content_type() {
  return "application/x-www-form-urlencoded";
  //application/atom+xml;type=entry
}


function local_subscribe( &$vars ) {
  
  extract($vars);
  
  $Subscription =& $db->model('Subscription');
  
  $sub = $Subscription->find_by( array(
    'subscribed'=>$request->listenee_id,
    'subscriber'=>get_profile_id()
  ));
  
  if (!$sub) {
    $sub = $Subscription->base();
    $sub->set_value('subscribed',$request->listenee_id);
    $sub->set_value('subscriber',get_profile_id());
    $sub->save_changes();
    
    $pro = get_profile($request->listenee_id);
    $subber = get_profile();
    
    if (is_email($pro->email_value))
      send_email( $pro->email_value, $subber->nickname . " is now following you on ".$request->domain, "\nhere's a link to the profile: \n\n    ".$subber->profile."\n\n", environment('email_from'), environment('email_name'), false );
    
  }
  
  redirect_to( array(
    'resource'=>$request->listenee_nick
  ));
  
}


function local_unsubscribe( &$vars ) {
  
  extract($vars);
  
  $Subscription =& $db->model('Subscription');
  
  $sub = $Subscription->find_by( array(
    'subscribed'=>$request->listenee_id,
    'subscriber'=>get_profile_id()
  ));
  
  if ( $sub )
    $db->delete_record( $sub );
  
  redirect_to( array(
    'resource'=>$request->listenee_nick
  ));
  
}


function xrdends( $uri, $xrds ) {
  
  if (!(substr($uri,0,1)) == '#')
    return;
  
  $xmlid = substr( $uri, 1 );
  
  $n = $xrds->allXrdNodes;
  
  $p = $xrds->parser;
  
  foreach ( $n as $nd ) {
    
    $a = $p->attributes( $nd );
    
    if ( isset($a['xml:id']) && $a['xml:id'] == $xmlid ) {
      $skip = array( $nd );
      return new Auth_Yadis_XRDS( $p, $skip );

    }
  }
}


function oauth_omb_update( &$vars ) {
  
  extract($vars);
  
  wp_plugin_include(array(
    'wp-oauth'
  ));
  
  $store = new OAuthWordpressStore();
  $server = new OAuthServer($store);
  $sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
  $plaintext_method = new OAuthSignatureMethod_PLAINTEXT();
  $server->add_signature_method($sha1_method);
  $server->add_signature_method($plaintext_method);
  $req = OAuthRequest::from_request();
  
  list($consumer, $token) = $server->verify_request($req);
  
  $version = $req->get_parameter('omb_version');
  
  if ($version != OMB_VERSION)
    trigger_error('invalid omb version', E_USER_ERROR);
  
  $listenee = $req->get_parameter('omb_listenee');
  
  $Identity =& $db->model('Identity');
  
  $sender = $Identity->find_by('profile',$listenee);
  
  if (!($sender)) {
    
    header('HTTP/1.1 403 Forbidden');
    exit;
  }
  
  $listenee_params = array(
    'omb_listenee_profile'   => 'profile_url',
    'omb_listenee_nickname'  => 'nickname',
    'omb_listenee_license'   => 'license',
    'omb_listenee_fullname'  => 'fullname',
    'omb_listenee_homepage'  => 'homepage',
    'omb_listenee_bio'       => 'bio',
    'omb_listenee_location'  => 'locality',
    'omb_listenee_avatar'    => 'avatar'
  );
  
  foreach($listenee_params as $k=>$v ) {
    if (isset($_POST[$k])) {
      $sender->set_value( $v, $_POST[$k] );
    }
  }
  
  $sender->save_changes();
  
  print "omb_version=".OMB_VERSION;
  exit;
  
}


function broadcast_omb_profile_update() {
  
  global $request, $db;
  
  wp_plugin_include(array(
    'wp-oauth'
  ));
  
  $i = get_profile();
  
  $listenee_uri = $i->profile;
  
  $license = $i->license;
  
  $sent_to = array();
  
  $Subscription = $db->model('Subscription');
  
  $Subscription->has_one( 'subscriber:identity' );
  
  $where = array(
    'subscriptions.subscribed'=>$i->id,
  );
  
  $Subscription->set_param( 'find_by', $where );
  
  $Subscription->find();
  
  while ($sub = $Subscription->MoveNext()) {
    $sub_token = trim($sub->token);
    $sub_secret = trim($sub->secret);
    $sid = $sub->FirstChild('identities');
    $url = $sid->update_profile;
    if (!in_array($url,$sent_to) && !empty($url) && !(strstr( $url, $request->base ))) {
      $sent_to[] = $url;
      $sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
      $wp_plugins = "wp-plugins" . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR . "enabled";
      $path = plugin_path() . $wp_plugins . DIRECTORY_SEPARATOR . 'wp-openid' . DIRECTORY_SEPARATOR;
      add_include_path( $path ); 
      require_once "Auth/Yadis/Yadis.php";
      $fetcher = Auth_Yadis_Yadis::getHTTPFetcher();
      $consumer = new OAuthConsumer($request->base, '');
      $token = new OAuthToken($sub_token, $sub_secret);
      $parsed = parse_url($url);
      $params = array();
      parse_str($parsed['query'], $params);
      $req = OAuthRequest::from_consumer_and_token($consumer, $token, "POST", $url, $params );
      
      $req->set_parameter('omb_version', OMB_VERSION );
      $req->set_parameter('omb_listenee', $listenee_uri );
      
      $listenee_params = array(
        'omb_listenee_profile'   => $i->profile,
        'omb_listenee_nickname'  => $i->nickname,
        'omb_listenee_license'   => $i->license,
        'omb_listenee_fullname'  => $i->fullname,
        'omb_listenee_homepage'  => $i->homepage,
        'omb_listenee_bio'       => $i->bio,
        'omb_listenee_location'  => $i->locality,
        'omb_listenee_avatar'    => $i->avatar
      );
      
      foreach($listenee_params as $k=>$v )
        $req->set_parameter( $k, $v );
      
      $req->sign_request($sha1_method, $consumer, $token);
      $result = $fetcher->post($req->get_normalized_http_url(),$req->to_postdata());
      
      if ( $result->status == 403 ) {
        // not so much
      } else {
        parse_str( $result->body, $return );
        if ( is_array($return) && $return['omb_version'] == OMB_VERSION ) {
          // nice
        } else {
          // could be better
        }
      }
      
    }
    
  }
  
}


function oauth_omb_register_services() {
  
  global $request;
  global $db;
  $Identity =& $db->model('Identity');
  $i = $Identity->find($request->id);
  
  //register_xrd_service('main', 'OAuth Dummy Service', array(
  //  'Type' => array( array('content' => 'http://oauth.net/discovery/1.0') ),
  //  'URI' => array( array('content' => '#oauth' ) ),
  //) );
  
  //register_xrd_service('main', 'OMB Dummy Service', array(
  //  'Type' => array( array('content' => 'http://openmicroblogging.org/protocol/0.1') ),
  //  'URI' => array( array('content' => '#omb' ) ),
  //) );
  
  register_xrd('oauth');
  
  register_xrd('omb');
  
  if (empty($i->profile)) {
    $i->set_value( 'profile', $request->url_for(array('resource'=>"_".$i->id)) );
    $i->set_value( 'profile_url', $request->url_for(array('resource'=>$i->nickname)) );
    $i->save_changes();
  }
  
  register_xrd_service( 'omb', 'OMB Post Notice', array(
    'Type' => array( 
      array('content' => OMB_VERSION . '/postNotice')
    ),
    'URI' => array( array('content' => $request->url_for( 'oauth_omb_post' ) ) ),
  ) );
  
  register_xrd_service( 'omb', 'OMB Update Profile', array(
    'Type' => array( 
      array('content' => OMB_VERSION . '/updateProfile')
    ),
    'URI' => array( array('content' => $request->url_for( 'oauth_omb_update' ) ) ),
  ) );
  
  register_xrd_service('oauth', 'OAuth Request Token', array(
    'Type' => array( 
    
      array('content' => OAUTH_VERSION . '/endpoint/request'),
      array('content' => OAUTH_VERSION . '/parameters/auth-header'),
      array('content' => OAUTH_VERSION . '/parameters/post-body'),
      array('content' => OAUTH_VERSION . '/signature/HMAC-SHA1'),
    ),
    'URI' => array( array('content' => $request->url_for( 'request_token' ) ) ),
    'LocalID' => array('content' => $i->profile )
  ) );
  
  register_xrd_service('oauth', 'OAuth Authorize Token', array(
    'Type' => array( 
      array('content' => OAUTH_VERSION . '/endpoint/authorize'),
      array('content' => OAUTH_VERSION . '/parameters/auth-header'),
      array('content' => OAUTH_VERSION . '/parameters/post-body'),
      array('content' => OAUTH_VERSION . '/signature/HMAC-SHA1'),
    ),
    'URI' => array( array('content' => $request->url_for( 'oauth_authorize' ) ) ),
  ) );
  
  register_xrd_service('oauth', 'OAuth Access Token', array(
    'Type' => array( 
      array('content' => OAUTH_VERSION . '/endpoint/access'),
      array('content' => OAUTH_VERSION . '/parameters/auth-header'),
      array('content' => OAUTH_VERSION . '/parameters/post-body'),
      array('content' => OAUTH_VERSION . '/signature/HMAC-SHA1'),
    ),
    'URI' => array( array('content' => $request->url_for( 'access_token' ) ) ),
  ) );
  
  register_xrd_service('oauth', 'OAuth Resources', array(
    'Type' => array( 
      array('content' => OAUTH_VERSION . '/endpoint/resource'),
      array('content' => OAUTH_VERSION . '/parameters/auth-header'),
      array('content' => OAUTH_VERSION . '/parameters/post-body'),
      array('content' => OAUTH_VERSION . '/signature/HMAC-SHA1'),
    ),
  ) );
  
  //register_xrd_service('oauth', 'OAuth Static Token', array(
  //  'Type' => array( 
  //    array('content' => 'http://oauth.net/discovery/1.0/consumer-identity/static'),
  //  ),
  //  'LocalID' => array( array('content' => $request->url_for(array('resource'=>'identities','id'=>$request->id )))),
  //) );
  
  
  
}










