<?php






after_filter( 'set_up_new_shortener', 'insert_from_post' );

function set_up_new_shortener( &$model, &$rec ) {
  global $request;
  if (!($request->resource == 'blogs'))
    return;
  // XXX subdomain upgrade
  $url = $request->url_for(array('resource'=>'twitter/'.$rec->nickname));
  require_once(ABSPATH.WPINC.'/class-snoopy.php');
  $snoop = new Snoopy;
  $snoop->agent = 'OpenMicroBlogger http://openmicroblogger.org';
  $snoop->submit($url);
  if (strpos($snoop->response_code, '200')) {
    
    $passer = get_code(5);
    
    if (signed_in()) {

      $profile = get_profile();

      global $db;
      $result = $db->get_result("SELECT apikey FROM installs WHERE apiname like '".$profile->nickname."'");
      $key = $db->result_value($result,0,'apikey');
    
      $Shortener =& $db->model('Shortener');
      if (!$Shortener->exists)
        $Shortener->save();
    
      $s = $Shortener->base();
      if ($key)
        $s->set_value('apikey',$key);
      $s->set_value('password',$passer);
      $s->set_value('profile_id',$profile->id);
      $s->set_value('type','tr.im');
      $s->set_value('urlcount',0);
      $s->set_value('urlbase',$_POST['shortener_domain']);
      $s->set_value('nickname',$rec->nickname);
      $s->set_value('endpoint',$request->domain.'/api/trim_url.<format>');
      
      $s->save_changes();
      $s->set_etag();
      
      $Identity =& $db->model('Identity');
      $Person =& $db->model('Person');
      $Entry =& $db->model('Entry');
      
      
      $TwitterUser =& $db->model('TwitterUser');
      $user_identity = get_profile();
      
      $twuser = $TwitterUser->find_by( 'profile_id',$user_identity->id);
      
      if ($twuser) {

        $user_person = $Person->find($user_identity->person_id);
      
        $mystuff = array();
      
        $Entry->find_by(array('person_id'=>$user_person->id));
      
        while ($e = $Entry->MoveNext()) {
          $model =& $db->get_table($e->resource);
          $twuser_rec = $model->find($e->record_id);
          if (!($e->resource == 'blogs')) {
            $saverec = $model->find($e->record_id);
            if ($saverec) {
              $mystuff[] = $saverec;
              $mystuff[] = $e;
            }
          }
        }
        
        // switch database namespace
        global $prefix;
        $prefix = $rec->prefix."_";
        $db->prefix = $prefix;
        
        if ($twuser->profile_id) {
          
          $Person =& $db->model('Person');
          $Person->save();
          
          $p = $Person->base();
          foreach ($user_person->attributes as $key=>$val)
            $p->set_value($key, $val);
          $p->save();

          $TwitterUser =& $db->model('TwitterUser');
          $TwitterUser->save();
          
          $t = $TwitterUser->base();
          foreach ($twuser->attributes as $key=>$val)
            $t->set_value($key, $val);
          $t->save();
          
          $saved = array();
          
          foreach ($mystuff as $r) {
            $model =& $db->get_table($r->table);
            if (!($r->table == 'entries') && !isset($saved[$r->table])) {
              $model->save();
              $saved[$r->table] = true;
            }
            $new = $model->base();
            foreach ($r->attributes as $key=>$val)
              $new->set_value($key, $val);
            $new->save();
          }
          
          $Method =& $db->model('Method');
          if (!$Method->exists)
            $Method->save();
                    
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
  
          
          $_SESSION['oauth_person_id'] = $p->id;
          $_SESSION['oauth_access_token'] = $t->oauth_key;
          $_SESSION['oauth_access_token_secret'] = $t->oauth_secret;
          
          $_SESSION['oauth_twitter'] = 'http://'.$rec->nickname.".".$request->domain;
          
        }
        
        redirect_to($request->base);
        
      } else {
        
        trigger_error('sorry, the Twitter username was not found', E_USER_ERROR );
        
      }
      
      
    } else {
      trigger_error('sorry, you must be signed in to do that', E_USER_ERROR );
    }
    
    
  } else {
    trigger_error('sorry, the new shortener could not be configured', E_USER_ERROR );
  }
  
}










function shortener_init() {
  
  
  add_include_path(library_path().'urlshort/upload');
  require_once 'includes/config.php'; // settings
  require_once 'includes/gen.php'; // url generation and location
  $perma = parse_url( $_SERVER['REQUEST_URI'] );
  $_PERMA = explode( "/", $perma['path'] );
  @array_shift( $_PERMA );
  $url = new shorturl();
	if ( isset($_PERMA[0]) ) // check GET first
	{
		$id = mysql_escape_string($_PERMA[0]);
	}
	/*elseif ( REWRITE ) // check the URI if we're using mod_rewrite
	{
		$explodo = explode('/', $_SERVER['REQUEST_URI']);
		$id = mysql_escape_string($explodo[count($explodo)-1]);
	}*/
	else // otherwise, just make it empty
	{
		$id = '';
	}
	// if the id isnt empty and its not this file, redirect to its url
	if ( $id != '' && $id != basename($_SERVER['PHP_SELF']) )
	{
		$location = $url->get_url($id);
		if ( $location != -1 )
		{
			header('Location: '.$location, TRUE, 301);
		}
		else // failure to find url output 404
		{
			//echo '<br/><div class=error-display id=error-display style=\"display:block;\" \">That URL does not exist. Try again?</div>';
			//exit;
		}
	}
  
  //app_register_init( 'admin', 'urls.html', 'Url Shortener', 'shortener', 2 );
  if ('69-30-72-254.dq1sf.easystreet.com' == $_SERVER[REMOTE_HOST]) {
//    echo 1; exit;
  }
  
  //after_filter('fhorty','routematch');
  
}


function shortener_show() {
  // show something to profile visitors
  // the_content
}

function shortener_head() {
  // always load in head
  // wp_head, admin_head
}

function shortener_menu() {
  // trigger before Admin menu renders
  // admin_menu
}

function shortener_post() {
  // publish_post
}


function shortener_redirect(&$request,&$route) {

  add_include_path(library_path().'urlshort/upload');
  require_once 'includes/config.php'; // settings
  require_once 'includes/gen.php'; // url generation and location
  $perma = parse_url( $_SERVER['REQUEST_URI'] );
  $_PERMA = explode( "/", $perma['path'] );
  @array_shift( $_PERMA );
  $url = new shorturl();
	if ( isset($_PERMA[0]) ) // check GET first
	{
		$id = mysql_escape_string($_PERMA[0]);
	}
	/*elseif ( REWRITE ) // check the URI if we're using mod_rewrite
	{
		$explodo = explode('/', $_SERVER['REQUEST_URI']);
		$id = mysql_escape_string($explodo[count($explodo)-1]);
	}*/
	else // otherwise, just make it empty
	{
		$id = '';
	}
	// if the id isnt empty and its not this file, redirect to its url
	if ( $id != '' && $id != basename($_SERVER['PHP_SELF']) )
	{
		$location = $url->get_url($id);
		if ( $location != -1 )
		{
			header('Location: '.$location, TRUE, 301);
		}
		else // failure to find url output 404
		{
			echo '<br/><div class=error-display id=error-display style=\"display:block;\" \">That URL does not exist. Try again?</div>';
			exit;
		}
	}
}




function drop_all_blogs() {
  $tabresult = $db->get_result("SHOW tables");
  for($i=0;$tables[$i]=mysql_fetch_assoc($tabresult);$i++) {
    $key = $tables[$i]['Tables_in_rply'];
    if (strpos($key, '_') && !in_array($key,array('categories_entries','aggregates_entries','twitter_users','db_sessions'))) {
      
      //echo "DROP $key <BR>";
        $sql = "DROP TABLE ".$key;
        $result = $db->get_result( $sql );
    }
  }
}
  
