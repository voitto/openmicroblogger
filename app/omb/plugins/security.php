<?php

  /** 
   * dbscript -- restful openid framework
   * @version 0.6.0 -- 22-October-2008
   * @author Brian Hendrickson <brian@dbscript.net>
   * @link http://dbscript.net/
   * @copyright Copyright 2009 Brian Hendrickson
   * @license http://www.opensource.org/licenses/mit-license.php MIT License
   * @package dbscript
   */
   
  /**
   * Model Security
   * 
   * filter to check permissions in $model->access_list,
   * which can be set in the data model via:
   * $model->let_read/let_write/let_access( 'group:callback' )
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param Mapper $req
   * @param Database $db
   * @return boolean
   * @todo modify to handle a partial set of fields
   */

function model_security( &$request, &$db ) {
  $action = $request->action;
  
  if ( isset( $request->resource ) )
    $model =& $db->get_table( $request->resource );
  else
    return true; // request is not for a resource

  if (public_resource())
    return true;

  if (virtual_resource())
    return true;
  
  if ( !( in_array( $action, $model->allowed_methods, true )))
    $action = 'get';
  
  $failed = false;
  
  authenticate_with_openid();
  
  // this switch is now repeated in $model->can($action)
  
  switch( $action ) {
    case 'get':
      if (!($model && $model->can_read_fields( $model->field_array )))
        $failed = true;
      break;
    case 'put':
      $submitted = $model->fields_from_request( $request );
      foreach ( $submitted as $table=>$fieldlist ) {
        $model =& $db->get_table($table);
        if (!($model && $model->can_write_fields( $fieldlist )))
          $failed = true;
      }
      break;
    case 'post':
      $submitted = $model->fields_from_request( $request );
      foreach ( $submitted as $table=>$fieldlist ) {
        $model =& $db->get_table($table);
        if (!($model && $model->can_create( $table )))
          $failed = true;
      }
      break;
    case 'delete':
      if (!($model && $model->can_delete( $request->resource )))
        $failed = true;
      break;
    default:
      $failed = true;
  }
  
  if (!$failed)
    return true;
  
  authenticate_with_openid();
  
  trigger_error( "Sorry, you do not have permission to $action ".$request->resource, E_USER_ERROR );
  
}

function authenticate_with_openid() {
  
  global $request;
  
  if ( !$request->openid_complete )
    begin_openid_authentication( $request );
  else
    complete_openid_authentication( $request );
  
}


function begin_openid_authentication( &$request ) {
  
  if ( !isset( $request->openid_url ) || empty( $request->openid_url )) {
    $_SESSION['requested_url'] = $request->uri;
    render( 'action', 'email' );
    return;
  }
  
  unset_cookie();
  
  $_SESSION['openid_url'] = $request->openid_url;
  
  if (class_exists('MySQL') && environment('openid_version') > 1 && !isset($_SESSION['openid_degrade']) )
    start_wp_openid();
  else
    start_simple_openid();
}


function start_wp_openid() {
  
  global $request;
  
  wp_plugin_include(array(
    'wp-openid'
  ));

  $logic = new WordPressOpenID_Logic(null);

  $logic->activate_plugin();
  
  if( !WordPressOpenID_Logic::late_bind() )
    return;

  $redirect_to = '';

  if( !empty( $_SESSION['requested_url'] ) )
    $redirect_to = $_SESSION['requested_url'];

  $claimed_url = $request->openid_url;

  $consumer = WordPressOpenID_Logic::getConsumer();

  $auth_request = $consumer->begin( $claimed_url );

  if ( null === $auth_request )
    trigger_error('OpenID server not found at '. htmlentities( $claimed_url ), E_USER_ERROR);

  $return_to = $request->url_for( 'openid_continue' );

  $store =& WordPressOpenID_Logic::getStore();

  $sreg_request = Auth_OpenID_SRegRequest::build(array(),array(
    'nickname',
    'email',
    'fullname'
  ));
  
  $auth_request->addExtension($sreg_request);
  
  $_SESSION['oid_return_to'] = $return_to;

  WordPressOpenID_Logic::doRedirect($auth_request, $request->protected_url, $return_to);
  exit(0);
  
}


function start_simple_openid() {
  
  global $request;
  
  include_once $GLOBALS['PATH']['library'] . 'openid.php';
  
  $openid = new SimpleOpenID;

  $openid->SetIdentity( $request->openid_url );

  $openid->SetApprovedURL( $request->url_for( 'openid_continue' )); // y'all come back now

  $openid->SetTrustRoot( $request->protected_url ); // protected site

  $openid->SetOptionalFields(array(
    'nickname',
    'email',
    'fullname'
  )); 
  
  $openid->SetRequiredFields(array());
  $server_url = $openid->GetOpenIDServer();

  $_SESSION['openid_server_url'] = $server_url;
  #echo $server_url; exit;
  $openid->SetOpenIDServer( $server_url );
  
  if ($openid->IsError())
    trigger_error( 'sorry there was an openid error: '.$openid->GetError(), E_USER_ERROR);
  $url = trim($server_url);
  if (empty($url))
    trigger_error( 'sorry there was an openid error: the server url is not set '.serialize($_SESSION), E_USER_ERROR);
  
  redirect_to( $openid->GetRedirectURL() );
  
}


function complete_openid_authentication( &$request ) {
  
  if (!(check_cookie())) {
    
    // cookie not set, DO IT
    
    $openid_to_identity = array(
      'email'=>'email_value',
      'dob'=>'dob',
      'postcode'=>'postal_code',
      'country'=>'country_name',
      'gender'=>'gender',
      'language'=>'language',
      'timezone'=>'tz'
    );
    
    if ( isset( $_SESSION['openid_url'] )) {
      
      global $db;
      
      $Identity =& $db->get_table( 'identities' );
      $Person =& $db->get_table( 'people' );
      
      $openid = $_SESSION['openid_url'];
      
      if (!strstr($openid,'http'))
        $openid = 'http://' . $openid;

      $i = $Identity->find_by( 'url', $openid );
      
      // OpenID auth complete, URL not exists
      // e-mail could be set though
      if (!$i && isset($_SESSION['openid_email']))
        $i = $Identity->find_by( 'email_value', $_SESSION['openid_email'] );
      
      //if (isset($_GET['openid_sreg_email']))
      //  $i = $Identity->find_by( 'email_value', $_GET['openid_sreg_email'] );
      
      //if (!$i && isset($_GET['openid_sreg_nickname']))
      //  $i = $Identity->find_by( 'nickname', $_GET['openid_sreg_nickname'] );

      if ($i) {
        $p = $Person->find( $i->person_id );
      } else {
        $p = $Person->base();
        $p->save();
        $i = $Identity->base();
        $i->set_value( 'person_id', $p->id );
        $i->set_value( 'label', 'profile 1' );
        if (isset($_SESSION['openid_email']))
          $i->set_value( 'email_value', $_SESSION['openid_email'] );
          
      }
      
      if (empty($i->url) || strstr( $i->url, "@" )) {
        
        $i->set_value( 'url', $openid );
        
        if (isset($_GET['openid_sreg_nickname']) && empty($i->nickname) ) {
          $nick = strtolower(urldecode($_GET['openid_sreg_nickname']));
          // set the nickname if it isn't alraedy taken and if it looks like a valid username
          if ($Identity->is_unique_value( $nick, 'nickname' ) && ereg("^([a-zA-Z0-9]+)$", $nick))
            $i->set_value( 'nickname', $nick );
        }
        
        // put SREG data in empty identity fields
        foreach($openid_to_identity as $k=>$v )
          if (!in_array($k,array('openid_sreg_nickname')) && isset($_GET['openid_sreg_'.$k]))
            if (empty($i->$v))
              $i->set_value( $v, urldecode($_GET['openid_sreg_'.$k]) );
        
        // split the SREG full name into first, last for VCARD, hCard, etc
        if (isset($_GET['openid_sreg_fullname']) && empty($i->given_name)) {
          $names = explode(' ',$_GET['openid_sreg_fullname']);
          if (strlen($names[0]) > 0 && empty($i->given_name))
            $i->set_value( 'given_name', $names[0] );
            
          if (isset($names[2]) && empty($i->family_name)) {
            $i->set_value( 'family_name', $names[2] );
          } elseif (isset($names[1]) && empty($i->family_name)) {
            $i->set_value( 'family_name', $names[1] );
          }
          
          $i->set_value( 'fullname', $_GET['openid_sreg_fullname']);
        
        }
        
        $i->save_changes();
        
        $i->set_etag( $p->id );
        
      }
      
    }
    if ( isset( $p->id ) && $p->id != 0) {
      // person id is valid
      // login complete
      set_cookie( $p->id );
      
      if (!(empty($_SESSION['requested_url'])))
        redirect_to( $_SESSION['requested_url'] );
      else
        redirect_to( $request->base );
        
    } else {
      
      // no person defined yet
      if ( isset($_SESSION['oauth_person_id'])
      && $_SESSION['oauth_person_id'] > 0 ) {
        // try to set the cookie
        // set_cookie( $_SESSION['oauth_person_id'] );
      } else {
        trigger_error( "unable to find the Person, sorry", E_USER_ERROR );
      }
      
    }
    
  } else {
    
    // cookie OK
    

  }
  
}



function ldap_login( &$vars ) {
  extract( $vars );
  $_SESSION['requested_url'] = $request->base;
  render( 'action', 'ldap' );
}

function _ldap( &$vars ) {
  extract( $vars );
}

function ldap_submit( &$vars ) {
  extract($vars);
  global $request;
}



function _email( &$vars ) {
  
  extract( $vars );
  
  $submit_url = $request->url_for( environment('authentication').'_submit' );
  
  $return_url = $request->url_for( 'openid_continue' );
  if (isset($_SESSION['requested_url']))
    $return_to = $_SESSION['requested_url'];
  else
    $return_to = $request->base;
  $protected_url = base_url(true);
  $Identity =& $db->model('Identity');
  if (isset($request->params['ident'])) {
    $ident = $Identity->find_by('token',$request->params['ident']);
    if ($ident) {
      $email = $ident->email_value;
      $_SESSION['openid_email'] = $email;
      $ident->set_value('token','');
      $ident->save_changes();
    } else {
      $email = false;
    }
  } else {
    $email = false;
  }
  return vars(
    array(
      
      &$email,
      &$protected_url,
      &$return_url,
      &$submit_url,
      &$return_to
      
    ),
    get_defined_vars()
  );
  
}


function _register( &$vars ) {

  extract( $vars );
  
  $submit_url = $request->url_for( environment('authentication').'_submit' );
  
  $return_url = $request->url_for( 'openid_continue' );
  if (isset($_SESSION['requested_url']))
    $return_to = $_SESSION['requested_url'];
  else
    $return_to = $request->base;
  $protected_url = base_url(true);
  if (isset($request->params['ident'])) {
    $ident = $Identity->find_by('token',$request->params['ident']);
    if ($ident) {
      $email = $ident->email_value;
      $_SESSION['openid_email'] = $email;
      $ident->set_value('token','');
      $ident->save_changes();
    } else {
      $email = false;
    }
  } else {
    $email = false;
  }
  return vars(
    array(
      
      &$email,
      &$protected_url,
      &$return_url,
      &$submit_url,
      &$return_to
      
    ),
    get_defined_vars()
  );
  
}

function _login( &$vars ) {
  extract( $vars );
  $submit_url = $request->url_for( 'openid_submit' );
  $return_url = $request->url_for( 'openid_continue' );
  if (isset($_SESSION['requested_url']))
    $return_to = $_SESSION['requested_url'];
  else
    $return_to = $request->base;
  $protected_url = base_url(true);
  if (isset($_SESSION['openid_url']))
    $openid_url = $_SESSION['openid_url'];
  else
    $openid_url = "";
  if (strstr($openid_url,'https://'))
    $openid_url = substr($openid_url,8);
  if (strstr($openid_url,'http://'))
    $openid_url = substr($openid_url,7);
  
  return vars(
    array(
      
      &$protected_url,
      &$return_url,
      &$submit_url,
      &$return_to,
      &$openid_url
      
    ),
    get_defined_vars()
  );
  
}

function normalize_url() {
  //
}

function password_register( &$vars ) {
  
  extract( $vars );

  $Identity =& $db->get_table( 'identities' );
  $Person =& $db->get_table( 'people' );
  
  if (!($request->password == $request->password2))
    trigger_error( "sorry the passwords do not match", E_USER_ERROR );
  
  $i = $Identity->find_by(array(
    'nickname'=>$request->nickname
  ),1);
  
  $p = $Person->find( $i->person_id );
  
  if ( isset( $p->id ) && $p->id != 0) {
    
    trigger_error( "sorry that username is already taken", E_USER_ERROR );
    
  } else {
    
    // create new user and log them in
    $p = $Person->base();
    $p->save();
    $i = $Identity->base();
    
    $i->set_value( 'person_id', $p->id );
    $i->set_value( 'label', 'profile 1' );
    $i->set_value( 'nickname', $request->nickname );
    $i->set_value( 'url', $request->base."".$request->nickname );
    $i->set_value( 'password', md5($request->password) );
    $i->save_changes();
    $i->set_etag( $p->id );
    
    $_SESSION['openid_complete'] = true;
    set_cookie( $p->id );
    
    if (!(empty($_SESSION['requested_url'])))
      redirect_to( $_SESSION['requested_url'] );
    else
      redirect_to( $request->base );
    
  }
}

function password_submit( &$vars ) {
  extract($vars);
  global $request;
  $Identity =& $db->get_table( 'identities' );
  $Person =& $db->get_table( 'people' );
  $i = $Identity->find_by(array(
    'nickname'=>$request->nickname,
    'password'=>md5($request->password)
  ),1);
  if (!$i)
    trigger_error( "username or password incorrect, sorry", E_USER_ERROR );
  $p = $Person->find( $i->person_id );
  if ( isset( $p->id ) && $p->id != 0) {
    $_SESSION['openid_complete'] = true;
    set_cookie( $p->id );
    if (!(empty($_SESSION['requested_url'])))
      redirect_to( $_SESSION['requested_url'] );
    else
      redirect_to( $request->base );
  } else {
    trigger_error( "unable to find the Person, sorry", E_USER_ERROR );
  }
}

function openid_submit( &$vars ) {
  
  unset_cookie();
  unset($_SESSION['openid_complete']);
  unset($_SESSION['openid_url']);
  unset($_SESSION['openid_email']);
  authenticate_with_openid();

}

function email_submit( &$vars ) {
  extract($vars);
  global $request;
  
  unset_cookie();
  unset($_SESSION['openid_complete']);
  unset($_SESSION['openid_url']);
  unset($_SESSION['openid_email']);
  
  $Identity =& $db->get_table( 'identities' );
  
  $i = $Identity->find_by( 'email_value', $request->email );
  
  $_SESSION['openid_email'] = $request->email;
  if ( $i && !(strstr( $i->url, "@" )) && !empty($i->url)) {
    $request->openid_url = $i->url;
    authenticate_with_openid();
  } else {
    $url = environment('openid_server')."/?action=seek&email=".$request->email;
    $curl = curl_init($url);
    $method = "GET";
    $params = array();
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_HTTPGET, ($method == "GET"));
    curl_setopt($curl, CURLOPT_POST, ($method == "POST"));
    if ($method == "POST") curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    
    if ( curl_errno($curl) == 0 ) {
      
      if (strstr( $response, "http" )) {
        
        // found a url, need to put it in the openid form
        
        $request->set_param('openid_url',trim($response));
        
        authenticate_with_openid();
        
      } else {
        
        // meh
        
      }
    }
    
    $_SESSION['requested_url'] = $request->base;
    redirect_to(environment('openid_server')."/?action=register&return=".urlencode($request->base)."&email=".urlencode($request->email));
  }
  
  if (!(empty($_SESSION['requested_url'])))
    redirect_to( $_SESSION['requested_url'] );
  else
    redirect_to( $request->base );
  
}

function openid_logout( &$vars ) {
  unset_cookie();
  extract( $vars );
  $_SESSION['openid_complete'] = false;
  //unset($_SESSION['openid_email']);
  //unset($_SESSION['openid_url']);
  $_SESSION['oauth_person_id']=0;
  unset($_SESSION['oauth_access_token']);
  unset($_SESSION['oauth_access_token_secret']);
  unset($_SESSION['oauth_request_token']);
  unset($_SESSION['oauth_request_token_secret']);  
  unset($_SESSION['oauth_state']);
  unset($_SESSION['oauth_twitter']);
  unset($_SESSION['oauth_person_id']);
  unset($_SESSION['requested_url']);
  unset($_SESSION['openid_complete']);
  unset($_SESSION['oid_return_to']);
  if (environment('authentication') == 'password') 
    redirect_to( $request->base );
  else
    redirect_to( environment('openid_server')."/?action=logout&return=".urlencode($request->base) );
}

function email_login( &$vars ) {
  extract( $vars );
  $_SESSION['requested_url'] = $request->base;
  render( 'action', 'email' );
}

function email_register( &$vars ) {
  extract( $vars );
  $_SESSION['requested_url'] = $request->base;
  render( 'action', 'register' );
}

function authenticate_with_oauth() {
  //
}

function oauth_login( &$vars ) {
  render( 'action', 'oauth' );
}

function _oauth( &$vars ) {
  
  // top stream, re-connect to subtwitter-db
  
  extract( $vars );
  global $prefix;
  $Blog =& $db->model('Blog');
  
  if (empty($db->prefix)) {
    if (isset($_REQUEST['oauth_token'])) {
      $tabresult = $db->get_result("SHOW tables");
      $tables = array();
      $tablist = array();
      for($i=0;$tables[$i]=mysql_fetch_assoc($tabresult);$i++)
        foreach($tables[$i] as $k=>$v) $tablist[] = $v;
      while ($b = $Blog->MoveNext()) {
        if (!empty($b->prefix) && in_array($b->prefix."_db_sessions",$tablist)) {
          $sql = "SELECT data FROM ".$b->prefix."_db_sessions WHERE data LIKE '%".$db->escape_string($_REQUEST['oauth_token'])."%'";
          $result = $db->get_result( $sql );
          if ($db->num_rows($result) == 1) {
            $auth_url = $request->base."?twitter/".$b->nickname."/oauth_login&oauth_token=".$_REQUEST['oauth_token'];
            $content = '<script type="text/javascript">'."\n";
            $content .= '  // <![CDATA['."\n";
            $content .= "  location.replace('".$auth_url."');"."\n";
            $content .= '  // ]]>'."\n";
            $content .= '</script>'."\n";
            return vars(
              array(&$content),
              get_defined_vars()
            );
          }
        }
      }
    }
  }
  
  // http://abrah.am
  lib_include('twitteroauth');
  
  /* Sessions are used to keep track of tokens while user authenticates with twitter */
  /* Consumer key from twitter */
  $consumer_key = environment( 'twitterKey' );
  /* Consumer Secret from twitter */
  $consumer_secret = environment( 'twitterSecret' );
  /* Set up placeholder */
  $content = NULL;
  /* Set state if previous session */
  $state = $_SESSION['oauth_state'];
  /* Checks if oauth_token is set from returning from twitter */
  $session_token = $_SESSION['oauth_request_token'];
  /* Checks if oauth_token is set from returning from twitter */
  $oauth_token = $_REQUEST['oauth_token'];
  /* Set section var */
  $section = $_REQUEST['section'];

  /* If oauth_token is missing get it */
  if ($_REQUEST['oauth_token'] != NULL && $_SESSION['oauth_state'] === 'start') {/*{{{*/
    $_SESSION['oauth_state'] = $state = 'returned';
  }/*}}}*/

  /*
   * 'default': Get a request token from twitter for new user
   * 'returned': The user has authorize the app on twitter
   */
  switch ($state) {/*{{{*/
    default:
      /* Create TwitterOAuth object with app key/secret */
      $to = new TwitterOAuth($consumer_key, $consumer_secret);
      /* Request tokens from twitter */
      $tok = $to->getRequestToken();
      /* Save tokens for later */
      
      $Blog =& $db->model('Blog');
      if (!empty($db->prefix) && isset($_REQUEST['oauth_token'])) {
        $tabresult = $db->get_result("SHOW tables");
        $tables = array();
        $tablist = array();
        for($i=0;$tables[$i]=mysql_fetch_assoc($tabresult);$i++)
          foreach($tables[$i] as $k=>$v) $tablist[] = $v;
        while ($b = $Blog->MoveNext()) {
          if (!empty($b->prefix) && in_array($b->prefix."_db_sessions",$tablist)) {
            $sql = "SELECT id FROM ".$b->prefix."_db_sessions WHERE data LIKE '%".$db->escape_string($_REQUEST['oauth_token'])."%'";
            $result = $db->get_result( $sql );
            if ($db->num_rows($result) == 1) {
              $sess = $db->result_value( $result, 0, "id" );
              $del = $db->get_result( "DELETE FROM ".$b->prefix."_db_sessions WHERE id = '$sess'" );
            }
          }
        }
      }
      
      $_SESSION['oauth_request_token'] = $token = $tok['oauth_token'];
      $_SESSION['oauth_request_token_secret'] = $tok['oauth_token_secret'];
      $_SESSION['oauth_state'] = "start";
      
      if (isset($_GET['forward']) && !empty($_SERVER['HTTP_REFERER']))
        $_SESSION['oauth_twitter'] = $_SERVER['HTTP_REFERER'];
      else
        $_SESSION['oauth_twitter'] = $request->base;
      
      /* Build the authorization URL */
      $auth_url = $to->getAuthorizeURL($token);
      if (empty($auth_url)) {
        $content = 'Request token not found, <a href="'.$request->url_for('oauth_login').'">click here to try again...</a>';
      } else {
        $content = '<script type="text/javascript">'."\n";
        $content .= '  // <![CDATA['."\n";
        $content .= "  location.replace('".$auth_url."');"."\n";
        $content .= '  // ]]>'."\n";
        $content .= '</script>'."\n";
      }
      break;
      
    case 'returned':
      if (isset($_SESSION['oauth_twitter']))
        $redirect_to = $_SESSION['oauth_twitter'];
      else
        $redirect_to = $request->base;
      /* If the access tokens are already set skip to the API call */

      if ($_SESSION['oauth_access_token'] === NULL && $_SESSION['oauth_access_token_secret'] === NULL) {
        /* Create TwitterOAuth object with app key/secret and token key/secret from default phase */
        $to = new TwitterOAuth($consumer_key, $consumer_secret, $_SESSION['oauth_request_token'], $_SESSION['oauth_request_token_secret']);
        /* Request access tokens from twitter */
        $tok = $to->getAccessToken();
        /* Save the access tokens. Normally these would be saved in a database for future use. */
        
        $_SESSION['oauth_access_token'] = $tok['oauth_token'];
        $_SESSION['oauth_access_token_secret'] = $tok['oauth_token_secret'];
        
        if (!($_SESSION['oauth_access_token'] === NULL && $_SESSION['oauth_access_token_secret'] === NULL)) {
          unset( $_SESSION['oauth_request_token'] );
          unset( $_SESSION['oauth_request_token_secret'] );
        }
        
      }
      
      $to = new TwitterOAuth(
        $consumer_key, 
        $consumer_secret, 
        $_SESSION['oauth_access_token'], 
        $_SESSION['oauth_access_token_secret']
      );
      
      $session_oauth_token = $_SESSION['oauth_access_token'];
      $session_oauth_secret = $_SESSION['oauth_access_token_secret'];
      

      $content = $to->OAuthRequest('https://twitter.com/account/verify_credentials.json', array(), 'GET');

      
      if (!(class_exists('Services_JSON')))
        lib_include( 'json' );
      $json = new Services_JSON();
      $user = $json->decode($content);

      if (empty($user)) 
        trigger_error('The server said: '.$content, E_USER_ERROR );
      
      if (empty($prefix) && in_array('invites',$db->tables)) {
        $Invite =& $db->model( 'Invite' );
        $result = $Invite->find_by( 'nickname',$user->screen_name );
        if (!$result)
          trigger_error('Sorry, you have not been invited yet '.environment('email_from'), E_USER_ERROR);
      }
      
      $Identity =& $db->model('Identity');
      $Person =& $db->model('Person');
      $TwitterUser =& $db->model('TwitterUser');
      
      $twuser = $TwitterUser->find_by( 'twitter_id',$user->id );
      
      // a) twitter user exists, does not have a profile_id
      // b) twitter user exists, HAS a profile_id
      // c) twitter user does not exist
      
      if ($twuser) {
        
        if (!$twuser->profile_id) {
          // a
          $i = make_identity($user);
          if (!$i)
            trigger_error('sorry I was unable to create an identity', E_USER_ERROR);
          $twuser->set_value('profile_id',$i->id);
          $twuser->set_value('oauth_key',$session_oauth_token);
          $twuser->set_value('oauth_secret',$session_oauth_secret);
          $twuser->save_changes();
          if (!$twuser)
            trigger_error('sorry I was unable to create a twitter user', E_USER_ERROR);
        } else {
          // b
          $i = $Identity->find($twuser->profile_id);
          if (!$i)
            trigger_error('sorry I was unable to find the identity', E_USER_ERROR);
        }
      } else {
        // c
        $i = make_identity($user);
        if (!$i)
          trigger_error('sorry I was unable to create an identity', E_USER_ERROR);
        $twuser = make_twuser($user,$i->id,$session_oauth_token,$session_oauth_secret);
        if (!$twuser)
          trigger_error('sorry I was unable to create a twitter user', E_USER_ERROR);
      }
          
      $_SESSION['oauth_person_id'] = $i->person_id;
      
      if (empty($redirect_to)) {
        $content = "<p>there was an error in the oauth routine, sorry</p>";
      } else {
        $content = '<script type="text/javascript">'."\n";
        $content .= '  // <![CDATA['."\n";
        $content .= "  location.replace('".$redirect_to."');"."\n";
        $content .= '  // ]]>'."\n";
        $content .= '</script>'."\n";
      }
      break;
  }/*}}}*/
  return vars(
  array(
    
    &$content,
    
  ),
  get_defined_vars()
);
}

function make_identity( $user ) {
  global $db,$prefix,$request;
  $Identity =& $db->model('Identity');
  $Person =& $db->model('Person');
  $p = $Person->base();
  $p->save();
  $i = $Identity->base();

  $nicker = $db->escape_string($user->screen_name);
  
  for ( $j=1; $j<5000; $j++ ) {
    $sql = "SELECT nickname FROM ".$prefix."identities WHERE nickname LIKE '".$nicker."' AND (post_notice = '' OR post_notice IS NULL)";
    $result = $db->get_result( $sql );
    if ($db->num_rows($result) > 0) {
      $nicker = $nicker.$j;
    } else {
      continue;
    }
  }

  $i->set_value( 'nickname', $nicker );
  $i->set_value( 'avatar', $user->profile_image_url ); 
  $i->set_value( 'fullname', $user->name );
  $i->set_value( 'bio', $user->description );
  $i->set_value( 'homepage', $user->url );
  $i->set_value( 'locality', $user->location );
  $i->set_value( 'label', 'profile 1' );
  $i->set_value( 'person_id', $p->id );
  $i->save_changes();
  $i->set_etag($p->id);
  
  if (empty($prefix) && in_array('invites',$db->tables)) {
    $Membership =& $db->model( 'Membership' );
    $m = $Membership->base();
    $m->set_value( 'group_id', 4 ); // XXX
    $m->set_value( 'person_id', $p->id );
    $m->save_changes();
  }
  
  $i->set_value( 'profile', $request->url_for(array('resource'=>"_".$i->id)) );
  $i->set_value( 'profile_url', $request->url_for(array('resource'=>$nicker)) );

  $i->save_changes();
  //$i->set_value( 'update_profile', $updateProfile );
  //$i->set_value( 'post_notice', $postNotice );
  return $i;
}

function make_twuser( $user, $profile_id, $oauthkey, $oauthsecret ) {
  global $db;
  $Identity =& $db->model('Identity');
  $Person =& $db->model('Person');
  $nickname = $user->screen_name;
  $TwitterUser =& $db->model('TwitterUser');
  $twuser = $TwitterUser->find_by( 'twitter_id',$user->id );
  if ($twuser)
    return $twuser;
  $twuser = $TwitterUser->base();
  $twuser->set_value('description',$user->description);
  $twuser->set_value('screen_name',$nickname);
  $twuser->set_value('url',$user->url);
  $twuser->set_value('name',$user->name);
  $twuser->set_value('protected',$user->protected);
  $twuser->set_value('followers_count',$user->followers_count);
  $twuser->set_value('profile_image_url',$user->profile_image_url);
  $twuser->set_value('location',$user->location);
  $twuser->set_value('twitter_id',$user->id);
  $twuser->set_value('profile_id',$profile_id);
  $twuser->set_value('oauth_key',$oauthkey);
  $twuser->set_value('oauth_secret',$oauthsecret);
  $twuser->save_changes();
  return $twuser;
}

function authenticate_with_omb() {
  //
}

function authenticate_with_http() {
  global $db,$request;
  global $person_id;
  global $api_methods,$api_method_perms;
  
  if (array_key_exists($request->action,$api_method_perms)) {
    $arr = $api_method_perms[$request->action];
    if ($db->models[$arr['table']]->can($arr['perm']))
      return;
  }
  
  if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="your username/password"');
  } else {
    $Identity =& $db->get_table( 'identities' );
    $Person =& $db->get_table( 'people' );
    $i = $Identity->find_by(array(
      'nickname'=>$_SERVER['PHP_AUTH_USER'],
      'password'=>md5($_SERVER['PHP_AUTH_PW'])
    ),1);
    $p = $Person->find( $i->person_id );
    if (!(isset( $p->id ) && $p->id > 0)) {
      header('HTTP/1.1 401 Unauthorized');
      echo 'BAD LOGIN';
      exit;
    }
    $person_id = $p->id;
  }
}

function openid_login( &$vars ) {
  extract( $vars );
  
  global $request;

  if (isset($request->params['openid'])) {

    $openid = urldecode($request->params['openid']);
    
    if (!strstr($openid,'http'))
      $openid = 'http://' . $openid;
    
    if ("/" == substr($openid,-1))
      $openid = substr( $openid, 0, -1 );
    
    $request->set_param('return_url',$request->url_for( 'openid_continue' ));
    
    $request->set_param('protected_url',$request->base);
    
    $request->set_param('openid_url',trim($openid));

    authenticate_with_openid();
    
    if (!(empty($_SESSION['requested_url'])))
      redirect_to( $_SESSION['requested_url'] );
    else
      redirect_to( $request->base );
  }
  
  render( 'action', 'login' );
  
}

function openid_continue( &$vars ) {
  
  extract( $vars );
  
  $valid = false;
  
  if ( class_exists('MySQL') && environment('openid_version') > 1 && !isset($_SESSION['openid_degrade']) ) {
    
    global $openid;
    
    wp_plugin_include(array(
      'wp-openid'
    ));
    
    $logic = new WordPressOpenID_Logic(null);
    
    $logic->activate_plugin();
    
    $consumer = WordPressOpenID_Logic::getConsumer();
    
    $openid->response = $consumer->complete($_SESSION['oid_return_to']);
    
    switch( $openid->response->status ) {
      case Auth_OpenID_CANCEL:
        trigger_error('The OpenID assertion was cancelled.', E_USER_ERROR );
        break;
      
      case Auth_OpenID_FAILURE:
        // if we fail OpenID v2 here, we retry once with OpenID v1
        $_SESSION['openid_degrade'] = true;
        $request->set_param('return_url',$request->url_for( 'openid_continue' ));
        $request->set_param('protected_url',$request->base);
        $request->set_param('openid_url',$_SESSION['openid_url']);
        authenticate_with_openid();
        break;
      
      case Auth_OpenID_SUCCESS:
        $_SESSION['openid_complete'] = true;
        $valid = true;
        break;
      
    }
  
  }
  
  if (!($valid)) {
  
    include $GLOBALS['PATH']['library'] . 'openid.php';
  
    $openid = new SimpleOpenID;
  
    $openid->SetIdentity( $_SESSION['openid_url'] );
  
    $openid->SetApprovedURL( $request->url_for( 'openid_continue' ));
  
    $openid->SetTrustRoot( $request->base );
  
    $server_url = $_SESSION['openid_server_url'];
  
    $openid->SetOpenIDServer( $server_url );
  
    $valid = $openid->ValidateWithServer();
    
  }
  
  if ($valid)
    $_SESSION['openid_complete'] = true;
  else
    trigger_error( "Sorry, the openid server $server_url did not validate your identity.", E_USER_ERROR );


  complete_openid_authentication( $request );
  
  if (!(empty($_SESSION['requested_url'])))
    redirect_to( $_SESSION['requested_url'] );
  else
    redirect_to( $request->base );
  
}

function security_init() {
  
  global $request;
  
  // add Routes -- route name, pattern to match, and default request parameters
  
  $request->connect( 'openid_continue/:fromserver', array('action'=>'openid_continue') );
  
  $request->connect( 'openid_continue' );
  
  $request->connect( 'openid_login_return' );
  
  $request->connect( 'openid_submit' );
  
  $request->connect( 'password_submit' );

  $request->connect( 'password_register' );
  
  $request->connect( 'openid_logout' );
  
  $request->connect( 'openid_login' );
  
  $request->connect( 'openid_login/:openid', array('action'=>'openid_login') );
  
  $request->connect( 'email_login' );
  
  $request->connect( 'register' );
  
  $request->connect( 'email_submit' );
  
  $request->connect( 'ldap_login' );
  
  $request->connect( 'ldap_submit' );
  
  $request->connect( 'oauth_login' );
  
  $request->routematch();
  
  if (isset($_SESSION['oauth_person_id'])
  && $_SESSION['oauth_person_id'] >0) {
      $request->openid_complete = true;
    return $_SESSION['oauth_person_id'];
  } elseif ( isset( $_SESSION['openid_complete'] ) && check_cookie() ) {
    if ( !isset($request->openid_url) && $_SESSION['openid_complete'] == true)
      $request->openid_complete = true;
  }
  
}

function security_install() {
  //
}

function security_uninstall() {
  //
}


