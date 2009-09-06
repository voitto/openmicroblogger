<?php

if (isset($_POST['ozh_yourls'])) {

  if (!(signed_in()))
    return;
  
  $setting_name = 'ozh_yourls';
  $setting_value = serialize($_POST['ozh_yourls']);
  global $db,$request;
  
  $Setting =& $db->model('Setting');
  
  $sett = $Setting->find_by(array('name'=>$setting_name,'profile_id'=>get_profile_id()));
  
  if (!$sett) {
    $s = $Setting->base();
    $s->set_value('profile_id',get_profile_id());
    $s->set_value('person_id',get_person_id());
    $s->set_value('name',$setting_name);
    $s->set_value('value',$setting_value);
    $s->save_changes();
    $s->set_etag();
  } else {
    $sett->set_value('value',$setting_value);
    $sett->save_changes();
  }
  
  $profile = get_profile();
  redirect_to($request->url_for(array("resource"=>$profile->nickname))."/settings");
  
}

if (isset($_POST['ajax_shorten'])) {
  if (!(signed_in()))
    return;
  $url = $_POST['ajax_shorten'];
  shortener_init();
  lib_include( 'json' );
	global $wp_ozh_yourls;
	if (!$wp_ozh_yourls)
		wp_ozh_yourls_admin_init();
	$shorturl = wp_ozh_yourls_api_call( wp_ozh_yourls_service(), $_POST['ajax_shorten']);
	if ($shorturl)
	  echo $shorturl;
	else
	  echo $shorturl;
	exit;
}

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
      if ($db->table_exists('installs')) {
        $result = $db->get_result("SELECT apikey FROM installs WHERE apiname like '".$profile->nickname."'");
        $key = $db->result_value($result,0,'apikey');
      } else {
        $key = false;
      }
      
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

          $Membership =& $db->model('Membership');
          $Membership->save();
          
          $me = $Membership->base();
          $me->set_value( 'person_id', $p->id);
          $me->set_value( 'group_id', 2 );
          $me->save_changes();
          $me->set_etag($p->id);
          
          $Setting =& $db->model('Setting');
  
          $s = $Setting->base();
          $s->set_value('profile_id',$twuser->profile_id);
          $s->set_value('person_id',$p->id);
          $s->set_value('name','twitter_status');
          $s->set_value('value','enabled');
          $s->save_changes();
          $s->set_etag($p->id);

          $user = $rec->nickname;
          $pass = $passer;

          $data = trim('a:14:{s:7:"service";s:5:"other";s:8:"location";s:0:"";s:11:"yourls_path";s:0:"";s:10:"yourls_url";s:0:"";s:12:"yourls_login";s:0:"";s:15:"yourls_password";s:0:"";s:5:"other";s:4:"rply";s:11:"bitly_login";s:0:"";s:14:"bitly_password";s:0:"";s:10:"trim_login";s:0:"";s:13:"trim_password";s:0:"";s:10:"rply_login";s:3:"'.$user.'";s:13:"rply_password";s:5:"'.$pass.'";s:19:"pingfm_user_app_key";s:0:"";}');

          $s = $Setting->base();
          $s->set_value('profile_id',$twuser->profile_id);
          $s->set_value('person_id',$p->id);
          $s->set_value('name','ozh_yourls');
          $s->set_value('value',$data);
          $s->save_changes();
          $s->set_etag($p->id);
          
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
  include 'wp-content/language/lang_chooser.php'; //Loads the language-file  
  wp_plugin_include( 'yourls-wordpress-to-twitter' );

  global $wp_ozh_yourls;
  $filedir = "wp-content" . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR . 'yourls-wordpress-to-twitter';
	require_once($filedir.'/inc/core.php');
	require_once($filedir.'/inc/options.php');
	
  if ( !defined('WP_CONTENT_URL') )
  	define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
  if ( !defined('WP_PLUGIN_DIR') )
  	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
  if ( !defined('WP_PLUGIN_URL') )
  	define( 'WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins'.'/yourls-wordpress-to-twitter' );
  if ( !defined('PLUGINDIR') )
  	define( 'PLUGINDIR', 'wp-content/plugins'.'/yourls-wordpress-to-twitter' );
  

  app_register_init( 'shorteners', 'index.html', 'URL Shortener', 'shortener', 2 );
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


function wp_ozh_yourls_omb_page() {
	$plugin_url = WP_PLUGIN_URL.'/'.plugin_basename( dirname(dirname(__FILE__)) );
	?>
	<div class="wrap">
	
	<?php /** ?>
	<pre><?php print_r(get_option('ozh_yourls')); ?></pre>
	<?php /**/ ?>

	<form method="post" action="<?php base_url(); ?>">
	<?php settings_fields('wp_ozh_yourls_options'); ?>
	<?php $ozh_yourls = get_option('ozh_yourls'); ?>

	<h3>URL Shortener Settings</h3>

	<table class="form-table">

	<tr valign="top">
	<th scope="row">URL Shortener Service</th>
	<td>

	<label for="y_service">You are using:</label>
	<select name="ozh_yourls[service]" id="y_service" class="y_toggle">
	<option value="" <?php selected( '', $ozh_yourls['service'] ); ?> >Please select..</option>
	<option value="yourls" <?php selected( 'yourls', $ozh_yourls['service'] ); ?> >your own YOURLS install</option>
	<option value="other" <?php selected( 'other', $ozh_yourls['service'] ); ?> >another public service such as TinyURL or tr.im</option>
	</select>
	
	<?php $hidden = ( $ozh_yourls['service'] == 'yourls' ? '' : 'y_hidden' ) ; ?>
	<div id="y_show_yourls" class="<?php echo $hidden; ?> y_service y_level2">
		<label for="y_location">Your YOURLS installation is</label>
		<select name="ozh_yourls[location]" id="y_location" class="y_toggle">
		<option value="" <?php selected( '', $ozh_yourls['location'] ); ?> >Please select...</option>
		<option value="local" <?php selected( 'local', $ozh_yourls['location'] ); ?> >local, on the same webserver</option>
		<option value="remote" <?php selected( 'remote', $ozh_yourls['location'] ); ?> >remote, on another webserver</option>
		</select>
		
		<?php $hidden = ( $ozh_yourls['location'] == 'local' ? '' : 'y_hidden' ) ; ?>
		<div id="y_show_local" class="<?php echo $hidden; ?> y_location y_level3">
			<label for="y_path">Path to the YOURLS config file</label> <input type="text" class="y_longfield" id="y_path" name="ozh_yourls[yourls_path]" value="<?php echo $ozh_yourls['yourls_path']; ?>"/> <span id="y_test_path"></span><br/>
			<em>Example: <tt>/home/you/site.com/yourls/includes/config.php</tt></em>
		</div>
		
		<?php $hidden = ( $ozh_yourls['location'] == 'remote' ? '' : 'y_hidden' ) ; ?>
		<div id="y_show_remote" class="<?php echo $hidden; ?> y_location y_level3">
			<label for="y_url">URL to the YOURLS API</label> <input type="text" id="y_url" class="y_longfield" name="ozh_yourls[yourls_url]" value="<?php echo $ozh_yourls['yourls_url']; ?>"/> <span id="y_test_url"></span><br/>
			<em>Example: <tt>http://site.com/yourls-api.php</tt></em><br/>
			<label for="y_yourls_login">YOURLS Login</label> <input type="text" id="y_yourls_login" name="ozh_yourls[yourls_login]" value="<?php echo $ozh_yourls['yourls_login']; ?>"/><br/>
			<label for="y_yourls_passwd">YOURLS Password</label> <input type="password" id="y_yourls_passwd" name="ozh_yourls[yourls_password]" value="<?php echo $ozh_yourls['yourls_password']; ?>"/><br/>
		</div>
		
	</div>
	
	<?php $hidden = ( $ozh_yourls['service'] == 'other' ? '' : 'y_hidden' ) ; ?>
	<div id="y_show_other" class="<?php echo $hidden; ?> y_service y_level2">

		<label for="y_other">Public service</label>
		<select name="ozh_yourls[other]" id="y_other" class="y_toggle">
		<option value="" <?php selected( '', $ozh_yourls['other'] ); ?> >Please select...</option>
		<option value="trim" <?php selected( 'trim', $ozh_yourls['other'] ); ?> >tr.im</option>
		<option value="rply" <?php selected( 'rply', $ozh_yourls['other'] ); ?> >rp.ly</option>
		<!--<option value="pingfm" <?php selected( 'pingfm', $ozh_yourls['other'] ); ?> >ping.fm</option>-->
		<option value="bitly" <?php selected( 'bitly', $ozh_yourls['other'] ); ?> >bit.ly</option>
		<option value="tinyurl" <?php selected( 'tinyurl', $ozh_yourls['other'] ); ?> >tinyURL</option>
		<option value="isgd" <?php selected( 'isgd', $ozh_yourls['other'] ); ?> >is.gd</option>
		</select>
		
		<?php $hidden = ( $ozh_yourls['other'] == 'bitly' ? '' : 'y_hidden' ) ; ?>
		<div id="y_show_bitly" class="<?php echo $hidden; ?> y_other y_level3">
			<label for="y_api_bitly_login">API Login</label> <input type="text" id="y_api_bitly_login" name="ozh_yourls[bitly_login]" value="<?php echo $ozh_yourls['bitly_login']; ?>"/> (case sensitive!)<br/>
			<label for="y_api_bitly_pass">API Key</label> <input type="text" id="y_api_bitly_pass" class="y_longfield" name="ozh_yourls[bitly_password]" value="<?php echo $ozh_yourls['bitly_password']; ?>"/><br/>
			<em>If you have a <a href="http://bit.ly/account/">bit.ly</a> account, entering your credentials will link the short URLs to it</em>
		</div>

		<?php $hidden = ( $ozh_yourls['other'] == 'trim' ? '' : 'y_hidden' ) ; ?>
		<div id="y_show_trim" class="<?php echo $hidden; ?> y_other y_level3">
			<label for="y_api_trim_login">Username</label> <input type="text" id="y_api_trim_login" name="ozh_yourls[trim_login]" value="<?php echo $ozh_yourls['trim_login']; ?>"/><br/>
			<label for="y_api_trim_pass">Password</label> <input type="password" id="y_api_trim_pass" name="ozh_yourls[trim_password]" value="<?php echo $ozh_yourls['trim_password']; ?>"/><br/>
			<em>If you have a <a href="http://tr.im/">tr.im</a> account, entering your credentials will link the short URLs to it</em>
		</div>

		<?php $hidden = ( $ozh_yourls['other'] == 'rply' ? '' : 'y_hidden' ) ; ?>
		<div id="y_show_rply" class="<?php echo $hidden; ?> y_other y_level3">
			<label for="y_api_rply_login">Username</label> <input type="text" id="y_api_rply_login" name="ozh_yourls[rply_login]" value="<?php echo $ozh_yourls['rply_login']; ?>"/><br/>
			<label for="y_api_rply_pass">Password</label> <input type="password" id="y_api_rply_pass" name="ozh_yourls[rply_password]" value="<?php echo $ozh_yourls['rply_password']; ?>"/><br/>
			<em>If you have a <a href="http://rp.ly/">rp.ly</a> account, entering your credentials will link the short URLs to it</em>
		</div>
		
		<?php $hidden = ( $ozh_yourls['other'] == 'pingfm' ? '' : 'y_hidden' ) ; ?>
		<div id="y_show_pingfm" class="<?php echo $hidden; ?> y_other y_level3">
			<label for="y_api_pingfm_user_app_key">Web Key</label> <input type="text" id="y_api_pingfm_user_app_key" name="ozh_yourls[pingfm_user_app_key]" value="<?php echo $ozh_yourls['pingfm_user_app_key']; ?>"/><br/>
			<em>If you have a <a href="http://ping.fm/">ping.fm</a> account, enter your private <a href="http://ping.fm/key/">Web Key</a></em>
		</div>
		
		<?php $hidden = ( $ozh_yourls['other'] == 'tinyurl' ? '' : 'y_hidden' ) ; ?>
		<div id="y_show_tinyurl" class="<?php echo $hidden; ?> y_other y_level3">
			<em>(this service needs no authentication)</em>
		</div>
		

		<?php $hidden = ( $ozh_yourls['other'] == 'isgd' ? '' : 'y_hidden' ) ; ?>
		<div id="y_show_isgd" class="<?php echo $hidden; ?> y_other y_level3">
			<em>(this service needs no authentication)</em>
		</div>
		
	</div>

	</td>
	</tr>
	</table>

	


	<p class="submit">
	<input type="submit" class="button-primary omb_submit" value="<?php _e('Save Changes') ?>" />
	</p>

	</form>

	</div> <!-- wrap -->

	
	<?php	
}

