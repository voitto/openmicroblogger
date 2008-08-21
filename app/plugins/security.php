<?php

  /** 
   * dbscript -- restful openid framework
   * @version 0.5.0 -- 12-August-2008
   * @author Brian Hendrickson <brian@dbscript.net>
   * @link http://dbscript.net/
   * @copyright Copyright 2008 Brian Hendrickson
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
  
  if ( !( in_array( $action, $model->allowed_methods, true )))
    $action = 'get';
  
  $failed = false;
  
  authenticate_with_openid();
  
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
        if (!($model && $model->can_write_fields( $fieldlist )))
          $failed = true;
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
  
  if (class_exists('MySQL') && environment('openid_version') > 1)
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
            
        if (isset($_GET['openid_sreg_fullname']) && empty($i->given_name)) {
          $names = explode(' ',$_GET['openid_sreg_fullname']);
          if (strlen($names[0]) > 0 && empty($i->given_name))
            $i->set_value( 'given_name', $names[0] );
          if (isset($names[2]) && empty($i->family_name)) {
            $i->set_value( 'family_name', $names[2] );
          } elseif (isset($names[1]) && empty($i->family_name)) {
            $i->set_value( 'family_name', $names[1] );
          }
        
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

      trigger_error( "unable to find the Person, sorry", E_USER_ERROR );

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
  $protected_url = $request->base;
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
  $protected_url = $request->base;
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
  $protected_url = $request->base;
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

function password_submit( &$vars ) {
  extract($vars);
  global $request;
  $Identity =& $db->get_table( 'identities' );
  $i = $Identity->find_by(array(
    'nickname'=>$request->nickname,
    'password'=>md5($request->password)
  ),1);
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
        
        // need to create a URL?
        
        
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
  unset($_SESSION['requested_url']);
  unset($_SESSION['openid_complete']);
  unset($_SESSION['oid_return_to']);
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

function openid_login( &$vars ) {
  extract( $vars );
  
  global $request;

  if (isset($request->params['openid'])) {

    $openid = urldecode($request->params['openid']);
    
    if (!strstr($openid,'http'))
      $openid = 'http://' . $openid;
    
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
  
  if ( class_exists('MySQL') && environment('openid_version') > 1) {
    
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
        trigger_error('Sorry, I could not validate your identity with the OpenID server. If you administer this site, you can try setting the openid_version to 1.', E_USER_ERROR );
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
  
  $request->connect( 'openid_logout' );
  
  $request->connect( 'openid_login' );
  
  $request->connect( 'openid_login/:openid', array('action'=>'openid_login') );
  
  $request->connect( 'email_login' );
  
  $request->connect( 'register' );
  
  $request->connect( 'email_submit' );
  
  $request->connect( 'ldap_login' );
  
  $request->connect( 'ldap_submit' );
  
  $request->routematch();
  
  if ( isset( $_SESSION['openid_complete'] ) && check_cookie() )
    if ( !isset($request->openid_url) && $_SESSION['openid_complete'] == true)
      $request->openid_complete = true;
  
}

function security_install() {
  //
}

function security_uninstall() {
  //
}

?>