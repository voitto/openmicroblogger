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

  $return_to = $request->url_for( 'openid_continue' ).'/';

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

  $openid->SetApprovedURL( $request->url_for( 'openid_continue' ).'/'); // y'all come back now

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
    trigger_error( 'sorry there was an openid error: '.serialize($openid->GetError()), E_USER_ERROR);
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
        
        $i->set_value( 'avatar', base_path(true).'resource/favicon.png' );

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
      if ( isset($_SESSION['fb_person_id'])
      && $_SESSION['fb_person_id'] > 0 ) {
        
      } elseif ( isset($_SESSION['oauth_person_id'])
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
  
  $return_url = $request->url_for( 'openid_continue' ).'/';
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
  
  $return_url = $request->url_for( 'openid_continue' ).'/';
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
  $return_url = $request->url_for( 'openid_continue' ).'/';
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
  
  //$i = $Identity->find_by(array(
  //  'nickname'=>$request->nickname
  //),1);
  
  //$p = $Person->find( $i->person_id );
  
  //if ( isset( $p->id ) && $p->id != 0) {
    
  $nick = $request->nickname;
  
  $sql = "SELECT id FROM ".$db->prefix."identities WHERE nickname LIKE '".$db->escape_string($nick)."' AND (post_notice = '' OR post_notice IS NULL)";
  
  $result = $db->get_result( $sql );
  
  if ( $db->num_rows($result) > 0) {
    
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
    $i->set_value( 'avatar', base_path(true).'resource/favicon.png' );

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

  if (isset($_GET['forward']) && !empty($_SERVER['HTTP_REFERER']))
    $_SESSION['logout_forward'] = $_SERVER['HTTP_REFERER'];

  unset_cookie();
  extract( $vars );
  $_SESSION['openid_complete'] = false;
  //unset($_SESSION['openid_email']);
  //unset($_SESSION['openid_url']);
  $_SESSION['oauth_person_id']=0;
  $_SESSION['fb_person_id']=0;
  unset($_SESSION['oauth_access_token']);
  unset($_SESSION['oauth_access_token_secret']);
  unset($_SESSION['oauth_request_token']);
  unset($_SESSION['oauth_request_token_secret']);  
  unset($_SESSION['oauth_state']);
  unset($_SESSION['oauth_twitter']);
  unset($_SESSION['fb_person_id']);
  unset($_SESSION['oauth_person_id']);
  unset($_SESSION['requested_url']);
  unset($_SESSION['openid_complete']);
  unset($_SESSION['oid_return_to']);
  if (isset($_SESSION['logout_forward']))
    redirect_to($_SESSION['logout_forward']);
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
            // XXX subdomain upgrade
            $redir = blog_url($b->nickname,true);
            $redir .= 'oauth_login';
            $redir .= "&oauth_token=".$_REQUEST['oauth_token'];
            $content = '<script type="text/javascript">'."\n";
            $content .= '  // <![CDATA['."\n";
            $content .= "  location.replace('".$redir."');"."\n";
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
          $i = make_identity(array(
            $user->screen_name,
            $user->profile_image_url,
            $user->name,
            $user->description,
            $user->url,
            $user->location
          ));
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
          if ($session_oauth_token != $twuser->oauth_key) {
            $twuser->set_value('oauth_key',$session_oauth_token);
            $twuser->set_value('oauth_secret',$session_oauth_secret);
            $twuser->save_changes();
          }
        }
      } else {
        // c
        $i = make_identity(array(
          $user->screen_name,
          $user->profile_image_url,
          $user->name,
          $user->description,
          $user->url,
          $user->location
        ));
        if (!$i)
          trigger_error('sorry I was unable to create an identity', E_USER_ERROR);
        $twuser = make_twuser($user,$i->id,$session_oauth_token,$session_oauth_secret);
        if (!$twuser)
          trigger_error('sorry I was unable to create a twitter user', E_USER_ERROR);
				$Setting =& $db->model('Setting');
				$cfg = $Setting->base();
				$cfg->set_value('profile_id',$i->id);
				$cfg->set_value('person_id',$i->person_id);
				$cfg->set_value('name','config.env.importtwitter_'.$user->id);
				$cfg->set_value('value',1);
				$cfg->save_changes();
				$cfg->set_etag();
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

function make_identity( $user, $newperson=false ) {
  global $db,$prefix,$request;
  $Person =& $db->model('Person');
  if ($newperson) {
	  $p = $Person->base();
	  $p->save();
  } elseif (get_person_id()) {
	  // make a new identity for the Person
	  $p = $Person->find(get_person_id());
  } else {
	  $p = $Person->base();
	  $p->save();
  }
	if (!(get_class($p) == 'Record')){
		$p = $Person->base();
	  $p->save();
	}


  $Identity =& $db->model('Identity');
  $i = $Identity->base();

  $nicker = $db->escape_string($user[0]);
  
  for ( $j=1; $j<50; $j++ ) {
    $sql = "SELECT nickname FROM ".$prefix."identities WHERE nickname LIKE '".$nicker."' AND (post_notice = '' OR post_notice IS NULL)";
    $result = $db->get_result( $sql );
    if ($db->num_rows($result) > 0) {
      $nicker = $db->escape_string($user[0]).$j;
    } else {
      break;
    }
  }

  $i->set_value( 'avatar', base_path(true).'resource/favicon.png' );
  $i->set_value( 'nickname', $nicker );
  if (!empty($user[1]))
    $i->set_value( 'avatar', $user[1] ); 
  $i->set_value( 'fullname', $user[2] );
  $i->set_value( 'bio', $user[3] );
  $i->set_value( 'homepage', $user[4] );
  $i->set_value( 'locality', $user[5] );
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

function facebook_login( &$vars ) {
  extract($vars);
  
  $app_id = environment('facebookAppId');
  $consumer_key = environment('facebookKey');
  $consumer_secret = environment('facebookSecret');
  $agent = environment('facebookAppName')." (curl)";
  
  add_include_path(library_path());
  add_include_path(library_path().'facebook-platform/php');
  add_include_path(library_path().'facebook_stream');
  
  require_once "facebook.php";
  require_once "FacebookStream.php";
  require_once "Services/Facebook.php";
  
  if (isset($_GET['forward']) && !empty($_SERVER['HTTP_REFERER']))
    $_SESSION['fb_forward'] = $_SERVER['HTTP_REFERER'];

	$sesskey = environment('facebookSession');

  $fb = new Facebook($consumer_key, $consumer_secret, true);

	$facebook->api_client->session_key = $sesskey;

  $_SESSION['fb_session'] = (string)$fb->api_client->session_key;
  $_SESSION['fb_userid'] = (string)$fb->user;

  $fs = new FacebookStream($consumer_key,$consumer_secret,$agent);
  
  $token = $fs->getAccessToken();

	$_SESSION['fb_request_token'] = $token;
	
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
  
  $user = $fs->GetInfo( $app_id, $_SESSION['fb_session'], $_SESSION['fb_userid'], $fields );
  
  $values = array();
  
  $values[] = str_replace(' ','',strtolower((string)$user->user->name));
  $values[] = (string)$user->user->pic_small;
  $values[] = (string)$user->user->name;
  $values[] = (string)$user->user->profile_blurb;
  $values[] = (string)$user->user->profile_url;
  $values[] = (string)$user->user->locale;
  
  $Identity =& $db->model('Identity');
  $Person =& $db->model('Person');
  $FacebookUser =& $db->model('FacebookUser');
  
  if (empty($prefix) && in_array('invites',$db->tables)) {
    $Invite =& $db->model( 'Invite' );
    $result = $Invite->find_by( 'nickname', (string)$user->user->name );
    if (!$result)
      trigger_error('Sorry, you have not been invited yet '.environment('email_from'), E_USER_ERROR);
  }
  
  $faceuser = $FacebookUser->find_by( 'facebook_id',$_SESSION['fb_userid'] );
  
  // a) facebook user exists, does not have a profile_id
  // b) facebook user exists, HAS a profile_id
  // c) facebook user does not exist
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
      // b
      $i = $Identity->find($faceuser->profile_id);
      if (!$i)
        trigger_error('sorry I was unable to find the identity', E_USER_ERROR);
    }
  } else {
    // c
    $i = make_identity($values);
    if (!$i)
      trigger_error('sorry I was unable to create an identity', E_USER_ERROR);
    $faceuser = make_fb_user($user,$i->id);
    if (!$faceuser)
      trigger_error('sorry I was unable to create a facebook user', E_USER_ERROR);
    $Setting =& $db->model('Setting');
		$cfg = $Setting->base();
		$cfg->set_value('profile_id',$i->id);
		$cfg->set_value('person_id',$i->person_id);
		$cfg->set_value('name','config.env.importfacebook_'.(string)$user->user->uid);
		$cfg->set_value('value',1);
		$cfg->save_changes();
		$cfg->set_etag();
  }

  $fb_can_offline = profile_setting('fb_can_upload');

  if (!$fb_can_offline) {
  	$fs->VerifyPerm($_SESSION['fb_userid'],'offline_access');
	  update_option('fb_can_offline',true);
  }

  $fb_can_tweet = profile_setting('fb_can_tweet');

  if (!$fb_can_tweet) {
	  $result = $fs->VerifyUpdate($_SESSION['fb_userid']);
	  update_option('fb_can_tweet',true);
  }

  $fb_can_upload = profile_setting('fb_can_upload');

  if (!$fb_can_upload) {
  	$fs->VerifyPerm($_SESSION['fb_userid'],'photo_upload');
	  update_option('fb_can_upload',true);
  }

  $_SESSION['fb_person_id'] = $i->person_id;
  
  if (isset($_SESSION['fb_forward']))
	  redirect_to($_SESSION['fb_forward']);

  redirect_to($request->base);
  
}

function make_fb_user( $user, $profile_id ) {
  
  global $db;
  
  $Identity =& $db->model('Identity');
  $Person =& $db->model('Person');
  $nickname = str_replace(' ','',strtolower((string)$user->user->name));
  $FacebookUser =& $db->model('FacebookUser');
  $faceuser = $FacebookUser->find_by( 'facebook_id',(string)$user->user->uid );
  
  if ($faceuser)
    return $faceuser;
  
  $faceuser = $FacebookUser->base();
  
  $faceuser->set_value('description',       (string)$user->user->profile_blurb);
  $faceuser->set_value('screen_name',       $nickname);
  $faceuser->set_value('url',               (string)$user->user->profile_url);
  $faceuser->set_value('name',              (string)$user->user->name);
  $faceuser->set_value('protected',         0);
  $faceuser->set_value('followers_count',   0);
  $faceuser->set_value('profile_image_url', (string)$user->user->pic_small);
  $faceuser->set_value('location',          (string)$user->user->locale);
  $faceuser->set_value('facebook_id',       (string)$user->user->uid);
  $faceuser->set_value('profile_id',        $profile_id);
  $faceuser->save_changes();
  
  return $faceuser;

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


function openid_login( &$vars ) {
  extract( $vars );
  
  global $request;

  if (isset($request->params['openid'])) {

    $openid = urldecode($request->params['openid']);
    
    if (!strstr($openid,'http'))
      $openid = 'http://' . $openid;
    
    if ("/" == substr($openid,-1))
      $openid = substr( $openid, 0, -1 );
    
    $request->set_param('return_url',$request->url_for( 'openid_continue' ).'/');
    
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
        $request->set_param('return_url',$request->url_for( 'openid_continue' ).'/');
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
  
    $openid->SetApprovedURL( $request->url_for( 'openid_continue' ).'/');
  
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

  $request->connect( 'facebook_login' );

  $request->connect( 'authsub' );

  $request->connect( 'permanent_facebook_key/:key', array('action'=>'permanent_facebook_key') );

  $request->routematch();
  
    if (isset($_SESSION['fb_person_id'])
  && $_SESSION['fb_person_id'] >0) {
      $request->openid_complete = true;
    return $_SESSION['fb_person_id'];
  } elseif (isset($_SESSION['oauth_person_id'])
  && $_SESSION['oauth_person_id'] >0) {
      $request->openid_complete = true;
    return $_SESSION['oauth_person_id'];
  } elseif ( isset( $_SESSION['openid_complete'] ) && check_cookie() ) {
    if ( !isset($request->openid_url) && $_SESSION['openid_complete'] == true)
      $request->openid_complete = true;
  } elseif (check_cookie()) {
	  $_SESSION['openid_complete'] = true;
  	$request->openid_complete = true;
  }
  
}

function security_install() {
  //
}

function security_uninstall() {
  //
}


function get_twitter_oauth(){
	global $db,$prefix,$request;
  $sql = "SELECT oauth_key,oauth_secret FROM ".$prefix."twitter_users WHERE profile_id = ".get_profile_id();
  $result = $db->get_result( $sql );
  if ($db->num_rows($result) == 1) {
    // http://abrah.am
    lib_include('twitteroauth');
    $key = $db->result_value($result,0,'oauth_key');
    $secret = $db->result_value($result,0,'oauth_secret');
    $consumer_key = environment( 'twitterKey' );
    $consumer_secret = environment( 'twitterSecret' );    
    $to = new TwitterOAuth(
      $consumer_key, 
      $consumer_secret, 
      $key, 
      $secret
    );
    return $to;
  }
  return false;
}

function get_twitter_screen_name(){
	global $db,$prefix,$request;
  $sql = "SELECT screen_name FROM ".$prefix."twitter_users WHERE profile_id = ".get_profile_id();
  $result = $db->get_result( $sql );
  if ($db->num_rows($result) == 1)
    return $db->result_value($result,0,'screen_name');
  return false;
}

function explode_returned($responseString){
	$r = array();
  foreach (explode('&', $responseString) as $param) {
    $pair = explode('=', $param, 2);
    if (count($pair) != 2) continue;
    $r[urldecode($pair[0])] = urldecode($pair[1]);
  }
  return $r;
}
function setup_google_account(){
	if (!isset($_SESSION['googleAccessKey']) && !isset($_SESSION['googleAccessSecret']))
	  trigger_error('sorry the oauth credentials were not found', E_USER_ERROR);
	global $request,$db;
	$Setting =& $db->model('Setting');
	
	$stat = $Setting->find_by(array('name'=>'google_key','profile_id'=>get_profile_id()));
	
  if (!$stat && !empty($_SESSION['googleAccessKey']) && get_profile_id()) {
    $stat = $Setting->base();
    $stat->set_value('profile_id',get_profile_id());
    $stat->set_value('person_id',get_person_id());
    $stat->set_value('name','google_key');
    $stat->set_value('value',$_SESSION['googleAccessKey']);
    $stat->save_changes();
    $stat->set_etag();
    $stat = $Setting->base();
    $stat->set_value('profile_id',get_profile_id());
    $stat->set_value('person_id',get_person_id());
    $stat->set_value('name','google_secret');
    $stat->set_value('value',$_SESSION['googleAccessSecret']);
    $stat->save_changes();
    $stat->set_etag();
		$cfg = $Setting->base();
		$cfg->set_value('profile_id',get_profile_id());
		$cfg->set_value('person_id',get_person_id());
		$cfg->set_value('name','config.env.importgoogle_'.$_SESSION['googleAccessKey']);
		$cfg->set_value('value',1);
		$cfg->save_changes();
		$cfg->set_etag();

  }	
  redirect_to($request->base);

	exit;
	
	// this is how you make a gdata api request
  $endpoint = $scope;
	$parsed = parse_url($endpoint);
	$params = array();
	parse_str($parsed['query'], $params);
  lib_include('twitteroauth');
	$base_url = $request->base;
  $key = environment( 'googleKey' );
  $secret = environment( 'googleSecret' );
	$consumer = new OAuthConsumer($key, $secret, NULL);
  $hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
  $token = get_oauth_token($_SESSION['googleAccessKey'], $_SESSION['googleAccessSecret']);
	$oauth_req = OAuthRequest::from_consumer_and_token($consumer, $token, "GET", $endpoint, $params);
	$oauth_req->sign_request($hmac_method, $consumer, $token);
	$responseString = send_signed_request($oauth_req->get_normalized_http_method(),
	                                      $endpoint, $oauth_req->to_header(), NULL, false);
	echo $responseString;
	exit;

  $key = environment( 'googleKey' );
  $secret = environment( 'googleSecret' );
  $hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
	$consumer = new OAuthConsumer($key, $secret, NULL);
	$token = $arr['oauth_token'];
	$tokensecret = $arr['oauth_token_secret'];
  $token = new OAuthToken($token, $tokensecret);
  $endpoint = 'https://mail.google.com/mail/feed/atom/';
	$oauth_req = OAuthRequest::from_consumer_and_token($consumer, $token, "GET", $endpoint, NULL);
	$oauth_req->sign_request($hmac_method, $consumer, $token);
	$responseString = readUrl($oauth_req->to_url());
	print_r($responseString);
}

function authsub( &$vars ) {
	if (isset($_SESSION['googleAccessKey']) && isset($_SESSION['googleAccessSecret']))
	  setup_google_account();
  extract($vars);
  $scope = 'https://mail.google.com/mail/feed/atom/';
  $base_url = $request->base;
  $endpoints = array(
		'https://www.google.com/accounts/OAuthGetRequestToken?scope='.$scope,
		'https://www.google.com/accounts/OAuthAuthorizeToken',
		'https://www.google.com/accounts/OAuthGetAccessToken'
	);
  if (!isset($_SESSION['googleAccessKey']) && !isset($_SESSION['googleAccessSecret'])){
	  if (!isset($request->oauth_token)){
		  $req_req = get_oauth_request(NULL,$endpoints[0]);
		  $responseString = readUrl($req_req->to_url());
		  $r = explode_returned($responseString);
		  $token = $r['oauth_token'];
		  $secret = $r['oauth_token_secret'];
		  $callback_url = $base_url."/authsub?oauth_secret=".$secret;
		  $auth_url = $endpoints[1] . "?oauth_token=$token&oauth_callback=".urlencode($callback_url);
		  redirect_to($auth_url);
	  } else {
		  $token = get_oauth_token($request->oauth_token,$request->oauth_secret);
		  $acc_req = get_oauth_request($token,$endpoints[2]);
			$responseString = readUrl($acc_req->to_url());
		  $r = explode_returned($responseString);
		  $_SESSION['googleAccessKey'] = $r['oauth_token'];
		  $_SESSION['googleAccessSecret'] = $r['oauth_token_secret'];
		  redirect_to($request->url_for('authsub'));
	  }
	}
  setup_google_account();
}




function get_oauth_request($token,$endpoint) {
	if (!class_exists('OAuthToken'))
	  lib_include('twitteroauth');
	$base_url = $request->base;
  $key = environment( 'googleKey' );
  $secret = environment( 'googleSecret' );
	$consumer = new OAuthConsumer($key, $secret, NULL);
  $hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
	$parsed = parse_url($endpoint);
	$params = array();
	parse_str($parsed['query'], $params);
	$rq = OAuthRequest::from_consumer_and_token($consumer, $token, "GET", $endpoint, $params);
  $rq->sign_request($hmac_method, $consumer, $token);
  return $rq;
}
function get_oauth_token($token,$secret){
	if (!class_exists('OAuthToken'))
	  lib_include('twitteroauth');
	return new OAuthToken($token,$secret);
}

function send_signed_request($http_method, $url, $auth_header=null,
                             $postData=null, $returnResponseHeaders=true) {
  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_FAILONERROR, false);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

  if ($returnResponseHeaders) {
    curl_setopt($curl, CURLOPT_HEADER, true);
  }

  switch($http_method) {
    case 'GET':
      if ($auth_header) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, array($auth_header));
      }
      break;
    case 'POST':
      $headers = array('Content-Type: application/atom+xml', $auth_header);
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($curl, CURLOPT_POST, 1);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
      break;
    case 'PUT':
      $headers = array('Content-Type: application/atom+xml', $auth_header);
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $http_method);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
      break;
    case 'DELETE':
      $headers = array($auth_header);
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $http_method);
      break;
  }
  $response = curl_exec($curl);
  if (!$response) {
    $response = curl_error($curl);
  }
  curl_close($curl);
  return $response;
}


function facebook_timeline(&$vars){
	extract($vars);
  global $db,$prefix;
  $sql = "SELECT DISTINCT facebook_id,oauth_key FROM ".$prefix."facebook_users, ".$prefix."identities WHERE ".$prefix."identities.person_id = ".get_person_id();
  $result = $db->get_result( $sql );
 if ($db->num_rows($result) == 1) {
		$app_id = environment('facebookAppId');
	  $consumer_key = environment('facebookKey');
	  $consumer_secret = environment('facebookSecret');
	  $agent = environment('facebookAppName')." (curl)";
	  add_include_path(library_path());
	  add_include_path(library_path().'facebook-platform/php');
	  add_include_path(library_path().'facebook_stream');
	  require_once "FacebookStream.php";
	  require_once "Services/Facebook.php";

$sesskey = 'a441dc31cd9e03b5b03b9912-1421801327';
  $appid = $app_id;
  $userid = $db->result_value($result,0,'facebook_id');


		  require_once "facebook.php";

	$fb = new Facebook($consumer_key, $consumer_secret, true);
	//	  $fs = new FacebookStream($consumer_key,$consumer_secret,$agent);
	$facebook->api_client->session_key = $sesskey;
	$facebook->api_client->user = $userid;
	    $data = $fb->api_client->stream_get();
		  print_r($data);
	    exit;



//  $access_token = $db->result_value($result,0,'oauth_key');

 $fs = new FacebookStream($consumer_key,$consumer_secret,$agent);
	$fs->VerifyPerm($userid,'offline_access');

		$hash = md5("app_id=".$appid."session_key=".$sesskey."source_id=".$userid.$fs->getApiSecret());
    
    $url = 'http://www.facebook.com/activitystreams/feed.php';
    $url .= '?source_id=';
    $url .= $userid;
    $url .= '&app_id=';
    $url .= $appid;
    $url .= '&session_key=';
    $url .= $sesskey;
    $url .= '&sig=';
    $url .= $hash;
    $url .= '&v=0.7&read';
				    $ch = curl_init();
				    if (defined("CURL_CA_BUNDLE_PATH")) curl_setopt($ch, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);
				    curl_setopt($ch, CURLOPT_URL, $url);
				    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
						curl_setopt($ch, CURLOPT_HEADER, false);
				    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
				    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				    curl_setopt($ch, CURLOPT_USERAGENT, "Safari " . phpversion());
				    $response = curl_exec($ch);
								echo "<BR><BR>";    
				echo $response;
				echo "<BR><BR>";    
				echo curl_getinfo($ch, CURLINFO_HTTP_CODE);
exit;




//    $auth_token
//echo $_SESSION['fb_request_token']; exit;
		$facebook = new Facebook($consumer_key, $consumer_secret);
		$infinite_key_array = $facebook->api_client->auth_getSession('CC1E30');
		print_r($infinite_key_array);
		echo "<BR>";
		echo $_SESSION['fb_session'];
		exit;


    
  
$sesskey = $_SESSION['fb_session'];

		$user = $fs->GetInfo($appid,$_SESSION['fb_session'],$userid,$fields);

		$hash = md5("app_id=".$appid."session_key=".$sesskey."source_id=".$userid.$fs->getApiSecret());
    

    $url = 'http://www.facebook.com/activitystreams/feed.php';
    $url .= '?source_id=';
    $url .= $userid;
    $url .= '&app_id=';
    $url .= $appid;
    $url .= '&session_key=';
    $url .= $sesskey;
    $url .= '&sig=';
    $url .= $hash;
    $url .= '&v=0.7&read';

		$hash = md5("v=1.0method=stream.getformat=XMLviewer_id=".$userid."session_key=".$sesskey."api_key=".$fs->getApiKey().$fs->getApiSecret());
		$url = "http://api.facebook.com/restserver.php?v=1.0&method=stream.get&format=XML&viewer_id=$userid&session_key=$sesskey&api_key=".$fs->getApiKey()."&sig=$hash";

		echo htmlspecialchars($url);
		
				    $ch = curl_init();
				    if (defined("CURL_CA_BUNDLE_PATH")) curl_setopt($ch, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);
				    curl_setopt($ch, CURLOPT_URL, $url);
				    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
						curl_setopt($ch, CURLOPT_HEADER, false);
				    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
				curl_setopt($curl, CURLOPT_POST, 1);
				    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				    curl_setopt($ch, CURLOPT_USERAGENT, "Safari " . phpversion());
				    $response = curl_exec($ch);
								echo "<BR><BR>";    
				echo $response;
				echo "<BR><BR>";    
				echo curl_getinfo($ch, CURLINFO_HTTP_CODE);
exit;


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

$user = $fs->GetInfo($appid,$_SESSION['fb_session'],$userid,$fields);
print_r($user); exit;

$fs->StreamRequest( $app_id, $_SESSION['fb_session'], $userid );
exit;
    //$token = $fs->getAccessToken();
//    $session = $fs->getSession($access_token);
//print_r($session);
//print_r($sessid); exit;
//echo $fs->api->auth->getSession();exit;
//echo "app_id=".$appid."session_key=".$sesskey."source_id=".$userid."[p]".$fs->getApiSecret();
		    $hash = md5("app_id=".$appid."session_key=".$sesskey."source_id=".$userid.$fs->getApiSecret());

		    $url = 'http://www.facebook.com/activitystreams/feed.php';
		    $url .= '?source_id=';
		    $url .= $userid;
		    $url .= '&app_id=';
		    $url .= $appid;
		    $url .= '&session_key=';
		    $url .= $sesskey;
		    $url .= '&sig=';
		    $url .= $hash;
		    $url .= '&v=0.7&read';

echo htmlspecialchars($url);exit;
		    $ch = curl_init();
		    if (defined("CURL_CA_BUNDLE_PATH")) curl_setopt($ch, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);
		    curl_setopt($ch, CURLOPT_URL, $url);
		    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
				curl_setopt($ch, CURLOPT_HEADER, false);
		    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		    curl_setopt($ch, CURLOPT_USERAGENT, "Safari " . phpversion());
		    $response = curl_exec($ch);
		echo $response;
		echo "<BR><BR>";    
		echo curl_getinfo($ch, CURLINFO_HTTP_CODE);


exit;
echo 1; exit;
    //$sessid = $_SESSION['fb_request_token'];
	  $fs->StreamRequest( $app_id, $sessid, $userid );
    exit;

    $token = $fs->getAccessToken();

    //$_SESSION['fb_request_token'] = $token;
    $sessid = $fs->getSession($token);
print_r($sessid); exit;

	  $fs->StreamRequest( $app_id, $sessid, $userid );
	  echo 'done';
	  exit;
  }


	exit;
}



function has_twitter_account(){
	global $db;
	$Setting =& $db->model('Setting');
	$stat = $Setting->find_by(array(
		'person_id'=>get_person_id(),
	  'eq'    => 'like',
	  'name'  => '%importtwitter%'
	));
	if ($stat)
	  return true;
	return false;
}

function has_facebook_account(){
	global $db;
	$Setting =& $db->model('Setting');
	$stat = $Setting->find_by(array(
		'person_id'=>get_person_id(),
	  'eq'    => 'like',
	  'name'  => '%importfacebook%'
	));
	if ($stat)
	  return true;
	return false;
}

function has_google_account(){
	global $db;
	$Setting =& $db->model('Setting');
	$stat = $Setting->find_by(array(
		'profile_id'=>get_profile_id(),
	  'eq'    => 'like',
	  'name'  => '%importgoogle%'
	));
	if ($stat)
	  return true;
	return false;
}

function has_flickr_account(){
	global $db;
	$Setting =& $db->model('Setting');
	$stat = $Setting->find_by(array('name'=>'flickr_frob','profile_id'=>get_profile_id()));
	if ($stat){
		$stat = $Setting->find_by(array('name'=>'flickr_status','profile_id'=>get_profile_id()));
	  if (!$stat) {
	    $stat = $Setting->base();
	    $stat->set_value('profile_id',get_profile_id());
	    $stat->set_value('person_id',get_person_id());
	    $stat->set_value('name','flickr_status');
	    $stat->set_value('value','enabled');
	    $stat->save_changes();
	    $stat->set_etag();
	  }
	  return true;
	}
	return false;
}



function setup_new_tweetiepic( &$rec ) {
  global $request,$db;
  $url = blog_url($rec->nickname,true);
  require_once(ABSPATH.WPINC.'/class-snoopy.php');
  $snoop = new Snoopy;
  $snoop->agent = 'OpenMicroBlogger http://openmicroblogger.org';
  $snoop->submit($url);
  if (!strpos($snoop->response_code, '200'))
    trigger_error('unable to connect to your new microblog stream',E_USER_ERROR);

  $profile = get_profile();
  $Identity =& $db->model('Identity');
  $Person =& $db->model('Person');
  $user_identity = get_profile();
  $user_person = $Person->find($user_identity->person_id);

  global $prefix;
  $prefix = $rec->prefix."_";
  $db->prefix = $prefix;

  $Entry =& $db->model('Entry');
  $Entry->save();

  $Setting =& $db->model('Setting');
	$Setting->save();

	$Method =& $db->model('Method');
	$Method->save();

  $Identity =& $db->model('Identity');
  $Identity->save();
  
	$Person =& $db->model('Person');
	$Person->save();

	$p = $Person->base();
	foreach ($user_person->attributes as $key=>$val)
	  $p->set_value($key, $val);
	$p->save();

  $i= $Identity->base();
  $i->set_value( 'id', $user_identity->id );
  $i->set_value( 'person_id', $p->id );
  $i->set_value( 'label', 'profile 1' );
  $i->set_value( 'nickname', $user_identity->nickname );
  $i->set_value( 'url', blog_url($rec->nickname,true)."".$user_identity->nickname );
//  $i->set_value( 'password', md5($passer) );
  $i->set_value( 'bio', $passer );
  $i->set_value( 'avatar', base_path(true).'resource/favicon.png' );

//echo $passer;
  $i->save_changes();
  $i->set_etag( $p->id );

  $Membership =& $db->model('Membership');
  $Membership->save();
  $me = $Membership->base();
  $me->set_value( 'person_id', $p->id);
  $me->set_value( 'group_id', 2 );
  $me->save_changes();
  $me->set_etag($p->id);
  $Setting =& $db->model('Setting');
  $user = '';
  $pass = '';
  $data = base64_encode('a:14:{s:7:"service";s:5:"other";s:8:"location";s:0:"";s:11:"yourls_path";s:0:"";s:10:"yourls_url";s:0:"";s:12:"yourls_login";s:0:"";s:15:"yourls_password";s:0:"";s:5:"other";s:4:"rply";s:11:"bitly_login";s:0:"";s:14:"bitly_password";s:0:"";s:10:"trim_login";s:0:"";s:13:"trim_password";s:0:"";s:10:"rply_login";s:3:"'.$user.'";s:13:"rply_password";s:5:"'.$pass.'";s:19:"pingfm_user_app_key";s:0:"";}');
  $s = $Setting->base();
  $s->set_value('profile_id',$user_identity->id);
  $s->set_value('person_id',$p->id);
  $s->set_value('name','ozh_yourls');
  $s->set_value('value',$data);
  $s->save_changes();
  $s->set_etag($p->id);
  $m = $Method->base();
  $m->set_value( 'code', '
    do_shorten();
  ');
  $m->set_value( 'function', 'api_trim_url' );
  $m->set_value( 'route', 'api/trim_url' );
  $m->set_value( 'resource', 'posts' );
  $m->set_value( 'permission', 'read' );
  $m->set_value( 'enabled', true );
  $m->set_value( 'omb', 0 );
  $m->set_value( 'oauth', 1 );
  $m->set_value( 'http', 1 );
  $m->save_changes();
  $m->set_etag($p->id);
  $m = $Method->base();
  $m->set_value( 'code', '
    do_shorten();
  ');
  $m->set_value( 'function', 'api_trim_simple' );
  $m->set_value( 'route', 'api/trim_simple' );
  $m->set_value( 'resource', 'posts' );
  $m->set_value( 'permission', 'read' );
  $m->set_value( 'enabled', true );
  $m->set_value( 'omb', 0 );
  $m->set_value( 'oauth', 1 );
  $m->set_value( 'http', 1 );
  $m->save_changes();
  $m->set_etag($p->id);
  redirect_to($request->base);

}


function set_my_tweetiepic_pass() {

	$stream = get_option('tweetiepic_stream',get_profile_id());
  global $db,$request;

	if ($stream){
	  $Blog =& $db->model('Blog');
	  $b = $Blog->find_by('prefix',$stream);
	  $blognick = $b->nickname;
	  $blogprefix = $b->prefix;
	} else {
		return;
	}

  $profile_id = get_profile_id();

  global $prefix;
  $prefix = $blogprefix."_";
  $db->prefix = $prefix;

  $Identity =& $db->model('Identity');

  $i= $Identity->find($profile_id);
  $i->set_value( 'password', md5($_POST['newpass']) );
  $i->save_changes();

 redirect_to($request->base);

}



function render_rss_feed($pro,$tweets){
	global $request;
	echo '<?xml version="1.0"?>
	<!-- RSS generated by OpenMicroBlogger v0.5.0 on '.date( "n/j/Y; g:i:s A e" ).' -->
	<rss version="2.0" xmlns:scripting="http://flickrfan.org/scriptingNamespace.html" xmlns:media="http://search.yahoo.com/mrss/">
		<channel>
			<title>'.environment('site_title').' / '.$pro->nickname.'</title>
			<link>'.$pro->profile_url.'</link>
			<description>'.environment('site_title').' updates from '.$pro->fullname.' / @'.$pro->nickname.'</description>
			<language>en-us</language>
			<copyright></copyright>
			<pubDate>'.date( "D, j M Y H:i:s T" ).'</pubDate>
			<lastBuildDate>'.date( "D, j M Y H:i:s T", strtotime( $tweets->updated )).'</lastBuildDate>
			<generator>OpenMicroBlogger</generator>
	    ';
	    do_action('rss2_head');
	echo '
	';

	while ($p = $tweets->MoveNext()) {
	
	$posturl = $request->url_for(array('resource'=>'posts','id'=>$p->id));
	$comurl = $posturl;
	echo '		<item>
				<title>'.$p->title.'</title>
				<link>'.$posturl.add_extension_if_blob($p).'</link>
				<scripting:byline>'.$pro->fullname.'</scripting:byline>
				<guid>'.$posturl.'</guid>
	      <comments>'.$comurl.'</comments>
				<description>'.$p->body.'</description>
				<pubDate>'.date( "D, j M Y H:i:s T", strtotime( $p->created )).'</pubDate>'.add_rss_if_blob($p,$posturl).'
		  </item>
	';
	}
	echo '	</channel>
	</rss>
	';


	
	
	
	
	
	
	
	
}


function add_extension_if_blob($p){
	global $db;
	$Entry =& $db->model('Entry');
	$e = $Entry->find($p->entry_id);
  if (in_array(extension_for($e->content_type), array('jpg','png','gif')))
	  return "/entry.".extension_for($e->content_type);
	return "";
}

function add_rss_if_blob($p,$posturl){
	global $db,$request;
	$Upload =& $db->model('Upload');
	$e = $Upload->find_by(array('target_id'=>$p->entry_id));
	if (!$e) return;
	$thumburl = $request->url_for(array('resource'=>'uploads','action'=>'preview','id'=>$u->id));
	if (in_array(extension_for($e->content_type), array('jpg','png','gif'))){
		$dname = "upload".$u->id.".".extension_for($e->content_type);
		if (!file_exists("/tmp/".$dname)){
			$download = tempnam("/tmp",$dname);
			set_time_limit(0);
			ini_set('display_errors',false);//Just in case we get some errors, let us know....
			$fp = fopen ($download, 'w+');//This is the file where we save the information
			$ch = curl_init( $thumburl.".".extension_for($e->content_type));//Here is the file we are downloading
			curl_setopt($ch, CURLOPT_TIMEOUT, 5000);
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_exec($ch);
			curl_close($ch);
			fclose($fp);
		} else {
			$download = "/tmp/".$dname;
		}
		if (extension_for($e->content_type) == 'jpg')
	    $pic = imagecreatefromjpeg($download);
	  if (extension_for($e->content_type) == 'gif')
	    $pic = imagecreatefromgif($download);
	  if (extension_for($e->content_type) == 'png')
	    $pic = imagecreatefromstring(file_get_contents($download));

    $Thumbnail =& $db->model('Thumbnail');
    $t = $Thumbnail->find_by(array('target_id'=>$e->id));

    if (!$t){
      $t = $Thumbnail->base();
      $t->set_value('target_id',$e->id);
      $t->save_changes();
      $t->set_etag($e->person_id);
		  $uploadfile = 'uploads'.DIRECTORY_SEPARATOR.'thumbnails'.$t->id;
		  photoCreateCropThumb( $uploadfile, $download, 150, 100, $download );
		} else {
		  $uploadfile = 'uploads'.DIRECTORY_SEPARATOR.'thumbnails'.$t->id;
		}

		if (extension_for($e->content_type) == 'jpg')
	    $th = imagecreatefromjpeg($uploadfile);
	  if (extension_for($e->content_type) == 'gif')
	    $th = imagecreatefromgif($uploadfile);
	  if (extension_for($e->content_type) == 'png')
	    $th = imagecreatefromstring(file_get_contents($uploadfile));

    

		return 
	 '
				<enclosure url="'.$posturl.add_extension_if_blob($p).'" type="'.$e->content_type.'" length="'.filesize($download).'" />
				<media:content url="'.$posturl.add_extension_if_blob($p).'" type="'.$e->content_type.'" height="'.imagesy($pic).'" width="'.imagesx($pic).'"/>
				<media:title>'.$p->title.'</media:title>
				<media:description type="html">'.$p->body.'</media:description>
				<media:thumbnail url="'.$thumburl.'" height="'.imagesy($th).'" width="'.imagesx($th).'"/>';
	}
	return "";
}

function permanent_facebook_key(&$vars){
	extract($vars);
	
  $app_id = environment('facebookAppId');
  $consumer_key = environment('facebookKey');
  $consumer_secret = environment('facebookSecret');
  $agent = environment('facebookAppName')." (curl)";
  
  add_include_path(library_path());
  add_include_path(library_path().'facebook-platform/php');
  add_include_path(library_path().'facebook_stream');

  require_once "facebook.php";
  require_once "FacebookStream.php";
  require_once "Services/Facebook.php";

  $facebook = new Facebook($consumer_key, $consumer_secret);
  $infinite_key_array = $facebook->api_client->auth_getSession($request->params['key']);
  if ($infinite_key_array['session_key'])
    echo "your permanent session key is ". $infinite_key_array['session_key'];
  else
    echo "sorry there was an error getting your permanent session key";
  exit;
}