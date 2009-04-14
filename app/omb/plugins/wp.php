<?php

  if (isset($_GET['s']) && !empty($_GET['s'])) {
    
    redirect_to('http://dejafeed.com:8080/search.jsp?query='.$_GET['s']);
    
  }

function wp_create_nonce($action = -1) {
  global $current_user;
  return $current_user;
}

function post_password_required() {
  return false;
}

function wp_list_comments() {
  echo "";
}

function cancel_comment_reply_link() {
  echo "";
}

function comment_id_fields() {
  echo "";
}

function get_the_tags( $id = 0 ) {
	return null;
}


function get_the_id() {
	global $id;
	return $id;
}

function is_front_page() {
  return true;
}

function allowed_tags() {
  return true;
}

function sanitize_post($post, $context = 'display') {
	if ( 'raw' == $context )
		return $post;
	if ( is_object($post) ) {
		if ( !isset($post->ID) )
			$post->ID = 0;
		foreach ( array_keys(get_object_vars($post)) as $field )
			$post->$field = sanitize_post_field($field, $post->$field, $post->ID, $context);
	} else {
		if ( !isset($post['ID']) )
			$post['ID'] = 0;
		foreach ( array_keys($post) as $field )
			$post[$field] = sanitize_post_field($field, $post[$field], $post['ID'], $context);
	}
	return $post;
}


function sanitize_post_field($field, $value, $post_id, $context) {
	return $value;
}

function get_the_author() {
	global $the_author;
  return $the_author->nickname;
}



function get_the_author_login() {
	global $the_author;
  return $the_author->nickname;
}

function get_author_link() {
	global $the_author;
  return $the_author->profile;
}

function get_the_category($id = false) {
  return "";
}

function get_the_title() {
  the_title();
}

function mysql2date() {
  return "";
}

function rewind_posts() {
	global $wp_query;
	return $wp_query->rewind_posts();
}

function &get_post(&$post, $output = OBJECT, $filter = 'raw') {
	global $post_cache, $wpdb, $blog_id;
  
  $_post = false;
	
	if ( empty($post) ) {
	  
	} elseif ( is_object($post) ) {
	  $_post = $post;
	  $_post->post_title = $post->title;
	  $_post->post_content = $post->body;
	  global $request;
	  $_post->guid = $request->url_for(array('resource'=>$post->table,'id'=>$post->id));
	} else {
		$post = (int) $post;
	}

	return $_post;
}

function do_action($tag, $arg = '') {
  global $db;
  trigger_before('wp_head',$db,$db);
}

function merge_filters($tag) {
}

class wpdb {
  
  var $base_prefix;
  var $prefix;
  var $show_errors;
  var $dbh;
  var $result;
  var $last_result;
  var $rows_affected;
  var $insert_id;
  var $col_info;
  var $posts;
  
  function wpdb() {
    global $prefix;
    $this->posts = 'posts';
    $this->col_info = array();
    $this->last_result = array();
    $this->base_prefix = "";
    $this->prefix = $prefix;
    $this->show_errors = false;
    global $db;
    $this->dbh =& $db->conn;
  }
  
  /**
   * Escapes content for insertion into the database, for security
   *
   * @param string $string
   * @return string query safe string
   */
  function escape($string) {
    global $db;
    return $db->escape_string( $string );
  }
  
  function hide_errors() {
    return true;
  }
  
  /**
   * Get one variable from the database
   * @param string $query (can be null as well, for caching, see codex)
   * @param int $x = 0 row num to return
   * @param int $y = 0 col num to return
   * @return mixed results
   */
  function get_var($query=null, $x = 0, $y = 0) {
    $pos = strpos($query,"SHOW TABLES");
    if (!($pos === false)) return true;
    if ( $query )
      $this->query($query);
    if ( $this->last_result[$y] ) {
      $values = array_values(get_object_vars($this->last_result[$y]));
    } else {
      //echo "<BR><BR>QUERY FAILED -- ".$query."<BR><BR>";
    }
    return (isset($values[$x]) && $values[$x]!=='') ? $values[$x] : null;
  }

  /**
   * Gets one column from the database
   * @param string $query (can be null as well, for caching, see codex)
   * @param int $x col num to return
   * @return array results
   */
  function get_col($query = null , $x = 0) {
    if ( $query )
      $this->query($query);

    $new_array = array();
    // Extract the column values
    for ( $i=0; $i < count($this->last_result); $i++ ) {
      $new_array[$i] = $this->get_var(null, $x, $i);
    }
    return $new_array;
  }

  /**
   * Get one row from the database
   * @param string $query
   * @param string $output ARRAY_A | ARRAY_N | OBJECT
   * @param int $y row num to return
   * @return mixed results
   */
  function get_row($query = null, $output = OBJECT, $y = 0) {
    if ( $query )
      $this->query($query);
    else
      return null;
    if ( !isset($this->last_result[$y]) )
      return null;
    if ( $output == OBJECT ) {
      return $this->last_result[$y] ? $this->last_result[$y] : null;
    } elseif ( $output == ARRAY_A ) {
      return $this->last_result[$y] ? get_object_vars($this->last_result[$y]) : null;
    } elseif ( $output == ARRAY_N ) {
      return $this->last_result[$y] ? array_values(get_object_vars($this->last_result[$y])) : null;
    } else {
      $this->print_error(" \$db->get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N");
    }
  }





/**
   * Return an entire result set from the database
   * @param string $query (can also be null to pull from the cache)
   * @param string $output ARRAY_A | ARRAY_N | OBJECT
   * @return mixed results
   */
  function get_results($query = null, $output = OBJECT) {
    if ( $query )
      $this->query($query);
    else
      return null;
    if ( $output == OBJECT ) {
      return $this->last_result;
    } elseif ( $output == ARRAY_A || $output == ARRAY_N ) {
      if ( $this->last_result ) {
        $i = 0;
        foreach( $this->last_result as $row ) {
          $new_array[$i] = (array) $row;
          if ( $output == ARRAY_N ) {
            $new_array[$i] = array_values($new_array[$i]);
          }
          $i++;
        }
        return $new_array;
      } else {
        return null;
      }
    }
  }


  // ==================================================================
  //  Basic Query  - see docs for more detail

  function query($query) {
    $return_val = 0;
    
    $pos = strpos($query,"update comments");
    if (!($pos === false))
      return true;

    $pos = strpos($query,"usermeta");
    if (!($pos === false))
      return true;
    global $db;

    $pos = strpos($query,"post_status");
    if (!($pos === false)) {
      global $posts,$request;
      if ($request->action == 'index')
        get_posts_init();
      $set = array();
      foreach($posts as $p=>$o) {
        $set[] = $p->id;
      }
      return $set;
    }
    
    if ( preg_match("/^\\s*(delete) /i",$query) )
      $query = str_replace("LIMIT 1","",$query);
    
    if ( class_exists('PostgreSQL') && preg_match("/^\\s*(replace into) /i",$query) )
      return;
    
    $this->result = $db->get_result($query);
    if ( preg_match("/^\\s*(insert|delete|update|replace) /i",$query) ) {
      $this->rows_affected = $db->affected_rows($db->conn);
      if ( preg_match("/^\\s*(insert|replace) /i",$query) ) {
        // todo -- pass the table and pkfield to last_insert_id
        //$this->insert_id = last_insert_id( $this->result, $pkfield, $table );
      }
      $return_val = $this->rows_affected;
    } else {
      $i = 0;
      $resultfields = $db->num_fields($this->result);
      while ($i < $resultfields ) {
        // todo -- figure out how to make a pg_fetch_field
        $this->col_info[$i] = $db->fetch_field($this->result,$i);
        $i++;
      }
      $num_rows = 0;
      while ( $row = $db->fetch_object($this->result) ) {
        $this->last_result[$num_rows] = $row;
        $num_rows++;
      }
      $this->num_rows = $num_rows;
      $return_val = $this->num_rows;
    }
    return $return_val;
  }
}

function get_locale() {
	global $locale;

	if (isset($locale))
		return apply_filters( 'locale', $locale );

	// WPLANG is defined in wp-config.
	if (defined('WPLANG'))
		$locale = WPLANG;

	if (empty($locale))
		$locale = '';

	$locale = apply_filters('locale', $locale);

	return $locale;
}

function get_users_of_blog($id="") {
  global $wpdb;
  $users = array();
  return $users;
}

function add_submenu_page( $up,$page,$menu,$access,$file,$func='',$url='' ) {
	global $submenu;
	if (!(is_array($submenu)))
	  $submenu = array();
  if (!(is_array($submenu[$up])))
    $submenu[$up] = array();
	$submenu[$up][] = array( 
	  $menu,
	  $access,
	  $file,
	  $page,
	  $url
	);
	$hook = preg_replace('!\.php!', '', $page );
	if (!empty ( $func ) && !empty ( $hook ))
		add_action( $hook, $func );
	return $hook;
}

function add_management_page( $page,$menu,$access,$file,$func='',$url='' ) {
	return add_submenu_page( $page, $page, $menu, $access, $file, $func, $url );
}

function balanceTags() {
  echo "";
}

function query_posts() {
  // meh
}

function post_class() {
  echo "";
}

function get_bloginfo( $var ) {
  global $blogdata;
  if (in_array($var,array('wpurl')))
    if (isset($blogdata[$var]))
      if ("/" == substr($blogdata[$var],-1))
        return substr($blogdata[$var],0,-1);
  if (isset($blogdata[$var]))
    return $blogdata[$var];
  return "";
} 

function add_option( $opt, $newval ) {
  update_option($opt,$newval);
}

if (!(class_exists('WpPost'))) {
class WpPost {
  var $post_password = "";
  var $comment_status = "closed";
  function WpPost() {
  }
}
}

if (!(class_exists('wpcomment'))) {
class wpcomment {
  var $user_id = 0;
  var $comment_author_email = "";
  var $comment_approved = false;
  function wpcomment() {
  }
}
}

function update_option( $opt, $newval ) {
  global $optiondata;
  if (isset($optiondata[$opt])) {
    if ($optiondata[$opt] == $newval)
      return;
  }
  global $db;
  $Setting =& $db->model('Setting');
  $s = $Setting->find_by(array('name'=>$opt,'profile_id'=>get_profile_id()));
  if (!$s) {
    $s = $Setting->base();
    $s->set_value('profile_id',get_profile_id());
    $s->set_value('name',$opt);
  }
  if (is_array($newval))
    $s->set_value('value',serialize($newval));
  else
    $s->set_value('value',$newval);
  $s->save_changes();
  $optiondata[$opt] = $newval;
}

class usermeta {
  
  var $ID = 0;
  var $oauth_consumers = array();
  var $has_openid = true;
  
  function usermeta($arr) {
    $this->ID = $arr['ID'];
    $this->oauth_consumers = $arr['oauth_consumers'];
    $this->has_openid = $arr['has_openid'];
  }
  
}

class WP_User {

  var $ID = 0;
  var $user_id = 0;
  var $user_email = "";
  var $first_name = "";
  var $last_name = "";
  
  var $data;
  var $user_login;
  var $user_level;
  var $user_url;
  var $user_pass;
  var $display_name;

  function WP_User( $uid, $name = "" ) {
    $this->ID = $uid;
    $this->user_id = $uid;
    $this->first_name = $name;
    $this->data = new usermeta(array(
      'ID'=>$uid,
      'has_openid'=>true,
      'oauth_consumers'=>array(
        'DUMMYKEY'=>array(
          'authorized'=>true,
          'endpoint1'=>'',
          'endpoint2'=>'')
      )
    //      $service = array('authorized' => true);
    //      foreach($services as $k => $v)
    //        if(in_array($k, array_keys($value)))
    //          $service[$k] = $v;
    //      $userdata->oauth_consumers[$key] = $service;
    //    }//end foreach services
    ));
    $this->user_login = '';
    $this->user_level = 0;
    $this->user_url = '';
    $this->user_pass = '';
    $this->display_name = $name;
    if ($uid > 0) {
      $profile = get_profile($uid);
      $this->first_name = $profile->nickname;
    }
    
  }
  
  function user_login() {
    
  }
  
  function has_cap($x) {
    return false;
  }
  
}

class dbfield {
  var $name;
  var $type;
  var $size;
  function dbfield() {
  }
}

class WP_Query {
  var $in_the_loop = false;
  function get_queried_object() {
    global $response;
    $p = $response->collection->MoveNext();
    $p->ID = $p->id;
    $p->post_excerpt = '';
    $p->post_content = $p->body;
    $p->post_author = '';
    global $response;
    $response->collection->rewind();
    return $p;
  }
  
  function rewind_posts() {
		$this->current_post = -1;
		if ($this->post_count > 0) {
			$this->post = $this->posts[0];
		}
	}

  function WP_Query() {
  }
  function get() {
    return array();
  }
  function have_posts() {
    return have_posts();
  }
  function the_post() {
    return the_post();
  }
}

class wp_rewrite {
  function wp_rewrite() {
  }
}

if (!(class_exists('wptag'))) {
class wptag {
  var $term_id = 0;
  var $count = 0;
  var $name = "";
  function wptag() {
  }
}
}

function auth_redirect() {

}

function nocache_headers() {
  
}

function register_activation_hook() {
  
}

function register_deactivation_hook() {
  
}

function is_feed () {
	global $wp_query;

	return $wp_query->is_feed;
}

function is_admin() {
  return false;
}

function _wp_filter_build_unique_id($tag, $function, $priority = 10){
	global $wp_filter;

	// If function then just skip all of the tests and not overwrite the following.
	// Static Calling
	if( is_string($function) )
		return $function;
	// Object Class Calling
	else if(is_object($function[0]) )
	{
		$obj_idx = get_class($function[0]).$function[1];
		if( is_null($function[0]->wp_filter_id) ) {
			$count = count((array)$wp_filter[$tag][$priority]);
			$function[0]->wp_filter_id = $count;
			$obj_idx .= $count;
			unset($count);
		} else
			$obj_idx .= $function[0]->wp_filter_id;
		return $obj_idx;
	}
	else if( is_string($function[0]) )
		return $function[0].$function[1];
}

function load_textdomain($domain, $mofile) {
	global $l10n;

	if (isset($l10n[$domain]))
		return;

	if ( is_readable($mofile))
		$input = new CachedFileReader($mofile);
	else
		return;

	$l10n[$domain] = new gettext_reader($input);
}


function load_plugin_textdomain($domain, $path = false) {

	$locale = get_locale();
	if ( empty($locale) )
		$locale = 'en_US';

	if ( false === $path )
		$path = PLUGINDIR;

	$mofile = ABSPATH . "$path/$domain-$locale.mo";
	load_textdomain($domain, $mofile);
}

function get_currentuserinfo() {
  global $current_user;
  //  if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST )
  //    return false;
  if ( ! empty($current_user) )
    return;
  
  $uid = get_profile_id();
  
  if (!$uid)
    authenticate_with_openid();
  
  $user = new WP_User($uid);
  //  if ( empty($_COOKIE[USER_COOKIE]) || empty($_COOKIE[PASS_COOKIE]) ||
  //    !wp_login($_COOKIE[USER_COOKIE], $_COOKIE[PASS_COOKIE], true) ) {
  //    wp_set_current_user(0);
  //    return false;
  //  }
  
  //$user_login = $_COOKIE[USER_COOKIE];
  
  wp_set_current_user($user->ID);
}

function bloginfo( $attr ) {
  echo get_bloginfo($attr);
}

function get_option( $opt ) {
  global $optiondata;
  
  if (!isset($optiondata[$opt])){
    global $db;
    $Setting =& $db->model('Setting');
    $s = $Setting->find_by(array('name'=>$opt,'profile_id'=>get_profile_id()));
    if ($s)
      return $s->value;
    return "";
  }
    
  $data = $optiondata[$opt];
  
  if (strstr($data,"http") && "/" == substr($data,-1))
    $data = substr($data,0,-1);
  
  return $data;
}

function get_userdata( $user_id ) {
  return new WP_User(get_profile_id());
}

function get_usermeta( $user_id, $what ) {
  
  $user = wp_set_current_user($user_id);
  // not logged in, need to do a db search on this user_id and oauth it
  
  //$authed = $authed[$consumer->key];
  //if($authed && $authed['authorized']) {
  //$authed = get_usermeta($userid, 'oauth_consumers');
  return $user->data;
}

function wp_nonce_field( $var ) {
  echo '<input type="hidden" name="method" value="post" />'."\n";
}

function wp_schedule_event( $when, $howoften, $event ) {
  
}

function wp_new_user_notification( $userlogin ) {
  
}
function is_user_logged_in() {
  
  $id = get_profile_id();
  
  if ($id)
    return true;
    
  return false;
  
}
function wp_clearcookie() {
  
}
 function timer_stop(){
 return;
 }
function the_title_attribute() {
  the_title();
}
function get_num_queries() {
  return 0;
}
function wp_meta() {
  echo "";
}
function trackback_rdf() {
  echo "";
}
function wp_setcookie( $userlogin, $md5pass, $var1 = true, $var2 = '', $var3 = '', $var4 = true ) {
  
}

function wp_set_auth_cookie( $userid, $remember ) {
  
}

function wp_set_current_user($id, $name = '') {
  global $current_user;

  if ( isset($current_user) && ($id == $current_user->ID) )
    return $current_user;

  $current_user = new WP_User($id, $name);

  setup_userdata($current_user->ID);

  return $current_user;
}

function setup_userdata($user_id = '') {
  global $user_login, $userdata, $user_level, $user_ID, $user_email, $user_url, $user_pass_md5, $user_identity;

  if ( '' == $user_id )
    $user = wp_get_current_user();
  else
    $user = new WP_User($user_id);

  //if ( 0 == $user->ID )
  //  return;

  $userdata = $user->data;
  $user_login  = $user->user_login;
  $user_level  = (int) $user->user_level;
  $user_ID  = (int) $user->ID;
  $user_email  = $user->user_email;
  $user_url  = $user->user_url;
  $user_pass_md5  = md5($user->user_pass);
  $user_identity  = $user->display_name;
}

function wp_signon( $u, $p ) {
  //array('user_login'=>'openid', 'user_password'=>'openid')
}

function wp_login( $u, $p ) {
  return true;
}

function wp_nonce_url( $var, $var2 ) {
  return $var;
}

function wp_print_scripts( $scripts = false ) {
  if (is_array($scripts)) {
    //
  }
}

function wp_enqueue_script( $file1,$file2=NULL ) {
  if (file_exists($file1))
    require_once $file1;
  if (!(NULL == $file2))
    if (file_exists($file2))
      require_once $file2;
}

function wp_title() {
  echo "";
}

function wp_head() {
    global $request;
    global $current_user;
    
    //trigger_before( 'admin_head', $current_user, $current_user );
    
    echo '<link rel="shortcut icon" href="'.base_path(true).'resource/favicon.ico" >';


    if ($request->resource == "posts" && environment('theme') == 'prologue-theme')
      echo '<script type="text/javascript" src="'.base_path(true).'resource/jquery-1.2.1.min.js"></script>';
    else
      echo '<script type="text/javascript" src="'.base_path(true).'resource/jquery-1.2.6.min.js"></script>';

    
    if ($request->resource == "posts" && $request->action == 'new')
      echo '
    <script type="text/javascript" src="'.base_path(true).'resource/markitup/jquery.markitup.pack.js"></script>
    <script type="text/javascript" src="'.base_path(true).'resource/markitup/sets/default/set.js"></script>
    <link rel="stylesheet" type="text/css" href="'.base_path(true).'resource/markitup/skins/markitup/style.css" />
    <link rel="stylesheet" type="text/css" href="'.base_path(true).'resource/markitup/sets/default/style.css" />
    
    ';
    
    echo '
    <script type="text/javascript" src="'.base_path(true).'resource/jquery.corner.js"></script>
    <script type="text/javascript" src="'.base_path(true).'resource/jquery.flash.js"></script>
    <script type="text/javascript" src="'.base_path(true).'resource/jquery.jqUploader.js"></script>

    <script type="text/javascript">
    $(document).ready(function(){
    	$("#postfile").jqUploader({
    	  background:"FFFFFF",
    	  barColor:"336699",
    	  allowedExt:"*.avi; *.jpg; *.jpeg; *.mp3; *.mov",
    	  allowedExtDescr: "Movies, Photos and Songs",
    	  validFileMessage: "Click [Upload]",
    	  endMessage: "",
    	  hideSubmit: false
    	});
    });
    
    </script>
    
    
    
    
    
    
    
    <script type="text/javascript">

  
  function show_page(url) {
    
    $("#main").html("<img src=\"'.base_path(true).'resource/jeditable/indicator.gif\">");
    
    $.get(url, function(str) {
      $("#main").hide();
      $("#main").html(str);
      $("#main").slideDown("fast");
    });
    
  }
   
function setMaxLength() {
	var x = document.getElementsByTagName("textarea");
	var counter = document.createElement("div");
	counter.className = "counter";
	for (var i=0;i<x.length;i++) {
		if (x[i].getAttribute("maxlength")) {
			var counterClone = counter.cloneNode(true);
			counterClone.relatedElement = x[i];
			counterClone.innerHTML = "<span>0</span>/"+x[i].getAttribute("maxlength");
			x[i].parentNode.insertBefore(counterClone,x[i].nextSibling);
			x[i].relatedElement = counterClone.getElementsByTagName("span")[0];
			x[i].onkeyup = x[i].onchange = checkMaxLength;
			x[i].onkeyup();
		}
	}
}

function checkMaxLength() {
	var maxLength = this.getAttribute("maxlength");
	var currentLength = this.value.length;
	if (currentLength > maxLength)
		this.relatedElement.className = "toomuch";
	else
		this.relatedElement.className = "";
	this.relatedElement.firstChild.nodeValue = currentLength;
	// not innerHTML
}


    </script>
    
    
    
    
    
    
    ';
    
    do_action('wp_head');
    
    if (isset($request->resource) && $request->resource == 'identities' && $request->id > 0) {
      
      // headers for a profile page
      
      echo '<meta http-equiv="X-XRDS-Location" content="'.$request->uri.'.xrds" />'."\n";
      echo '<meta http-equiv="X-Yadis-Location" content="'.$request->uri.'.xrds" />'."\n";
      
      // need to add OpenID headers here
      
    }
}

function wp_register_sidebar_widget( $var1, $var2, $var3 ) {
  return false;
}

function wp_register_widget_control( $var1, $var2, $var3 ) {
  return false;
}

function trackback_url() {
  echo "#";
}

function update_usermeta() {
  
}

function wp_insert_user( $user_data ) {
  
}

function pings_open() {
  return false;
}

function wp_footer() {
  echo "";
}

function wp_redirect( $url ) {
  redirect_to( $url );
}

function wp_safe_redirect( $url ) {
  redirect_to( $url );
}

function wp_insert_post( $arr ) {
  return false;
}

function wp_list_bookmarks() {
  echo "";
}

function wp_list_cats() {
  global $request;
  $blocks = environment('blocks');
  if (!empty($blocks)) {
    foreach ($blocks as $b) {
      echo '<li><script type="text/javascript" src="'.$request->url_for(array('resource'=>$b,'action'=>'block.js')).'"></script></li>';
    }
  }
}

function wp_get_current_commenter() {
  return 1;
}

function wp_get_current_user() {
  return new WP_User(get_profile_id());
}

function wp_get_archives($type) {
  echo "";
}

function get_header() {
  global $request;
  // this should be a separate filter, but it catches
  // folks who are not completely set-up and sends them
  // to the identity edit form to add a photo and nickname
    
    if (get_profile_id()) {
    
    $p = get_profile();

      $edit_uri = $request->url_for(array(
        'resource'=>'identities',
        'id'=>$p->id,
        'action'=>'edit'
      ));

    if (($request->resource != 'identities' || $request->action != 'edit') && (!isset($p->nickname) || empty($p->avatar))) {
      $_SESSION['message'] = "Photo and Nickname are required.";
      redirect_to($edit_uri);
    }

}  

  
  include('header.php');
}
function is_page() {
  return true;
}
function is_category() {
  return false;
}

function comments_link() {
  echo "";
}
function is_day() {
  return false;
}
function is_month() {
  return false;
}
function is_year() {
  return false;
}
function get_header_image() {
  return "there-is-no-image.jpg";
}

function get_footer() {
  include('footer.php');
}

function get_sidebar() {
  include('sidebar.php');
}



function get_permalink( ) {
  global $the_post,$request;
  return $request->url_for(array('resource'=>'posts','id'=>$the_post->id));
}

function get_tags( $arr ) {
  return array();
}

function get_tag_link( $category_id ) {
  return "#";
}

function get_tag_feed_link( $category_id ) {
  return "#";
}



function get_objects_in_term( $category_id, $post_tag ) {
  return array();
}

function wp_list_pages() {
  return array();
}
function next_posts_link() {
  echo "";
}
function previous_posts_link() {
  echo "";
}
function get_term( $category_id, $post_tag ) {
  return new wptag();
}

function avatar_by_id( $wpcom_user_id, $size ) {
  return false;
}

function attribute_escape( $value ) {
  return $value;
}

function the_post() {
  
  global $wpmode;
  global $wphaved;
  if ($wpmode == 'other') {
    if (!$wphaved) {
      $wphaved = true;
      return true;
    }
    return false;
  }
  
  global $the_post,$response,$the_author,$the_entry,$request;
  $the_post =& $response->collection->MoveNext();
  if (isset($the_post->profile_id) && $the_post->table == 'posts'){
    $the_author = get_profile($the_post->profile_id);
  }else{
    global $db;
    $Identity =& $db->model('Identity');
    if ($the_post && $the_post->table == 'ak_twitter') {

      $TwitterUser =& $db->model('TwitterUser');
      $the_author = $TwitterUser->find_by('twitter_id',$the_post->profile_id);
      if ($the_author) {
        $the_author->profile_url = $the_author->url;
        $the_author->profile = $the_author->url;
        $the_author->nickname = $the_author->screen_name;
        $the_author->email_value = '';

        $the_author->avatar = $the_author->profile_image_url;
        $the_author->fullname = $the_author->name;
        $the_author->id = $the_author->twitter_id;

        $the_post->local = 0;
        $the_post->parent_id = 0;
        $the_post->title = $the_post->tw_text;
      }
    } else {

      if ($the_post) {
        $the_entry = $the_post->FirstChild( 'entries' );
        if ($the_entry && $the_entry->person_id) {
          $the_author = owner_of($the_post);
        } else {
          $the_author = $Identity->base();
        }
      } else {
        $Post =& $db->model('Post');
        $the_post = $Post->base();
        $the_author = $Identity->base();
      }
    }
  }
  
  if (!empty($the_author->profile_url)) $the_author->profile = $the_author->profile_url; 
  
  global $comment_author; 
  global $comment_author_email;
  global $comment_author_url;
  
  $comment_author = $the_author->nickname;
  $comment_author_email = $the_author->email_value;
  $comment_author_url = $the_author->url;
  
    // show pretty URLs if not a Remote user
  if (empty($the_author->post_notice)) $the_author->profile = $request->url_for(array('resource'=>$the_author->nickname));
  
  return "";
}

function get_links() {
  echo "";
}

function the_excerpt() {
  echo "";
}

function get_posts_init() {
  global $posts;
  $posts = array();
  global $the_post,$response,$the_author,$the_entry,$request;
  if ($request->resource != 'posts')
    return false;
  while (have_posts()) {
    $p = $response->collection->MoveNext();
    $p->ID = $p->id;
    $p->post_excerpt = '';
    $p->post_content = $p->body;
    $p->post_author = '';
    $posts[] = $p;
  }
  $response->collection->rewind();
}

function get_post_meta($pid=0,$field,$bool) {
  
  global $posts;
  
  if (!(is_array($posts)))
    return '';

  $mapper = array(
    'description'    => 'body',
    'title'          => 'title',
    'aiosp_disable'  => '',
    'title_tag'      => '',
    'keywords'       => '',
    'autometa'       => '',
    'aiosp_disable'  => ''
  );
  
  foreach($posts as $p) {
    if ($p->ID == $pid && isset($mapper[$field])){
      $attr = $mapper[$field];
      if (isset($p->$attr))
        return $p->$attr;
    }
  }
  
  return '';
  
}

function link_pages() {
  echo "";
}

function wp_link_pages() {
  echo "";
}

function the_search_query() {
  echo "";
}

function comments_open() {
  return false;
}

function wp_list_categories() {
  echo "";
}

function post_comments_feed_link() {
  echo "";
}

function the_permalink() {
  global $the_post;
  url_for(array('resource'=>'posts','id'=>$the_post->id));
}

function the_date($timestamp=false) {
  if (!$timestamp)
      $timestamp = time();
  echo date( get_settings('date_format'), $timestamp );
}

function the_time( $format = "g:i A" ) {
  global $the_post;
  $timestamp = strtotime($the_post->created);
  if (!$timestamp)
      $timestamp = time();
  echo date( $format, $timestamp );
}

function wp_loginout() {
  echo "";
}

function wp_register() {
  echo "";
}

function the_tags( $var1="", $var2="", $var3="" ) {
  echo "";
}

function the_title() {
  global $the_post;
  if (!(environment('theme') == 'prologue-theme')) {
     echo $the_post->title;
  }
}

function get_avatar( $current_user_id, $pixels ) {
  global $the_author,$request,$the_post;
  $avatar = "";
  if (!empty($the_author->avatar)) {
    $avatar = $the_author->avatar;
  } else {
    $p = get_profile();
    if (!isset($the_post->id) || ($the_author->id == $p->id))
      $avatar = $p->avatar;
  }
  if (!(environment('theme') == 'P2'))
    return '
   
    <img alt=\'\' src=\''.$avatar.'\' 
    class=\'avatar avatar-48\' height=\'48\' width=\'48\' />
    ';
  if (!(empty($avatar)))
    return '<a href="'.$the_author->profile.'"><img alt="avatar" src="' . $avatar . '" style="width:'.$pixels.'px;height:'.$pixels.'px;" class="avatar" /></a>';
}

function get_the_author_email() {
  global $the_author;
  return $the_author->email_value;
}

function is_tag() {
}

function the_author_nickname() {
  global $the_author;
  echo $the_author->nickname;
}

function the_author() {
  global $the_author;
  echo $the_author->fullname;
}

function the_category() {
  global $the_post,$db;
  $e = $the_post->FirstChild('entries');
  $Join =& $db->get_table('categories_entries');
  $Join->find_by('entry_id',$e->id);
  $Category =& $db->model('Category');
  $comma = "";
  while ($cj = $Join->MoveNext()) {
    $c = $Category->find($cj->category_id);
    echo $comma.$c->name;
    $comma = ",";
  }
}

function __($text) {
  return $text;
}

function the_ID() {
  global $the_post;
  echo $the_post->id;
}

function the_author_ID() {
  global $the_author;
  echo $the_author->id;
}

function the_content( $linklabel ) {
  
  global $wpmode;
  global $wphaved;
  if ($wpmode == 'other') {
    echo content_for_layout();
    return;
  }
  
  global $the_post,$request,$the_author;
  
  $e = $the_post->FirstChild('entries');
  
  $title = $the_post->title;
  
  if (!in_array(environment('theme'),array('p2','prologue-theme'))) {
    
    $current_user_id = get_the_author_ID( );
    if (function_exists('prologue_get_avatar'))
      echo prologue_get_avatar( $current_user_id, get_the_author_email( ), 48 );

  }
  
  
  $title = render_notice( $title, $the_post, $the_author );
  
  echo "<p>".$title."</p>";
  
}

function render_notice($title,&$the_post,&$the_author) {
  global $request;
  if (strpos($title, 'http') !== false || strpos($title, '@') !== false) {
    $title = str_replace("\n"," ",$title);
    $expl = explode( " ", $title );
    if (is_array($expl)){
      foreach($expl as $k=>$v) {
        if (substr($v,0,1) == '@') {
          if ($the_post->local) {
            $expl[$k] = "@<a href=\"".$request->url_for(array('resource'=>''.substr($v,1)))."\">".substr($v,1)."</a>";
          } else {
            $parsed = parse_url($the_author->profile);
            $expl[$k] = "@<a href=\"".$parsed['scheme']."://".$parsed['host']."/".substr($v,1)."\">".substr($v,1)."</a>";
          }
        }
        if (substr($v,0,4) == 'http') {
          $expl[$k] = "<a href=\"".$v."\">".$v."</a>";
        }
      }
      $title = implode(" ", $expl);
    }
  }
  return $title;
}



function have_posts() {

  global $response;
  global $db;
  global $request;
  global $wpmode;
  global $wphaved;
  if ($wpmode == 'other') {
    if (!$wphaved)
      return true;
    return false;
  }
  
  if ($request->resource != 'posts')
    return false;
  
  //$Post =& $db->model('Post');
  //echo $Post->get_query();
  $rows = count($response->collection->members);

  if ($response->collection->_currentRow >= $rows)
    return false;
  
  if (!$response->collection->EOF && (0 < $rows))
    return true;
    
  return !$response->collection->EOF;
}

function get_author_feed_link( $id ) {
  return "#";
}

function the_author_posts_link( ) {
  global $the_author,$request;
  echo '<a href="';
  echo $the_author->profile;
  echo '" title="Posts by '.$the_author->nickname.'">'.$the_author->nickname.'</a>';
}

function get_the_author_ID() {
  global $the_author;
  return $the_author->id;
}

function show_prologue_nav() {
  global $request;
  global $db;
  $links = array();
  $pid = get_profile_id();
  $byid = 0;
  if (isset($request->params['byid']))
    $byid = $request->params['byid'];
  $links['Public'] = base_url(true);
  if ($byid > 0 && $byid != $pid) {
    $i = get_profile($byid);
  } elseif ($request->resource == 'identities' && $request->id != $pid) {
    $i = get_profile($request->id);
  } elseif ($pid > 0) {
    $i = get_profile();
  } else {
    $i = 0;
  }
  if ($i && $i->id > 0) {
    $links['Personal'] = $request->url_for(array(
        'resource'=>'posts',
        'byid'=>$i->id,
        'page'=>1 ));
    if (empty($i->post_notice))
      $links['Profile'] = $request->url_for(array('resource'=>$i->nickname));
    else
      $links['Profile'] = $i->profile;
  }
  if ($pid > 0) {
    if (member_of('administrators')) {
      $links['Admin'] = $request->url_for('admin');
    }
    $links['Logout'] = $request->url_for('openid_logout');
  } else {
    $links['Register'] = $request->url_for('register');
    $links['Login'] = $request->url_for('email_login');
  }
  echo '<ul id="nav">';
  foreach($links as $k=>$v)
    echo '<li class="top"><a href="'.$v.'" class="top_link"><span>'.$k.'</span></a></li>'."\n";
  echo '</ul>';
}

function posts_nav_link() {
  global $request;
  global $response;
  if (isset($request->params['page']))
    $page = $request->params['page'];
  else
    $page = 1;
  
  $mapper = array('resource'=>'posts');
  
  if (isset($request->params['byid']))
    $mapper['byid'] = $request->params['byid'];
  
  if (count($response->collection->members) >= $response->collection->per_page ) {
    $mapper['page'] = ($page + 1);
    echo '<a href="'.$request->url_for( $mapper );
    echo '">&lt; older</a>';

  }
  
  if ($page > 1) {
    $mapper['page'] = ($page - 1);
    echo "&nbsp;&nbsp;&nbsp;";
    echo '<a href="'.$request->url_for( $mapper );
    echo '">newer &gt;</a>';
  }

}
function is_author() {
  return true;
}
function is_single() {
  return false;
}
function is_attachment() {
  return false;
}
function is_paged() {
  return false;
}
function is_search() {
  return false;
}
function is_date() {
  return true;
}
function is_archive() {
 return false;
}
function get_settings($opt) {
  global $optiondata;
  return $optiondata[$opt];
}
function wp_specialchars($var) {
  return htmlspecialchars($var);
}
function make_clickable($text) {
  return $text;
}
function is_home() {
  return true;
}
function is_404() {
  return false;
}
function load_theme_textdomain() {
  return "";
}
function language_attributes() {
  echo "";
}








function _e($t) {
  echo $t;
}



function register_sidebar() {
  return false;
}

function add_custom_image_header( $var, $name ) {
  return false;
}

function get_edit_post_link( &$post ) {
  global $the_post,$request;
  if (!isset($the_post->id))
    return "";
  return $request->url_for(array(
    'resource'  => 'posts',
    'id'        => $the_post->id,
    'action'    => 'edit'
  ));
}

function edit_post_link( $post ) {
  global $the_post,$request;
  if (!isset($the_post->id))
    return;
  if ($the_post->profile_id == get_profile_id() || get_profile_id() == 1)
    echo "<a href=\"".$request->url_for(array(
      'resource'  => 'posts',
      'id'        => $the_post->id,
      'action'    => 'edit'
    ))."\">edit</a>&nbsp;|&nbsp;<a href=\"".$request->url_for(array(
    'resource'  => 'posts',
    'id'        => $the_post->id,
    'action'    => 'remove'
  ))."\">remove</a>";
}

function comments_rss_link() {
  echo "#";
}

function pageGetPageNo() {
  
}

function sandbox_body_class() {
  
}

function get_posts() {

}

function dp_list_pages() {
  
}

function get_the_time() {
  
}

function sandbox_post_class() {
  
}

function get_post_custom_values() {
  
}

function dp_attachment_image() {
  
}

function get_day_link() {
  
}

function get_stylesheet_directory_uri() {
  
}

function comments_popup_link( $var1, $var2, $var3 ) {
  
  // jeditable
  
  global $the_post;
  global $request;
  
  $theme = environment('theme');
  
  if ($theme == 'prologue-theme') {
    echo "<a href=\"";
    echo $request->url_for(array(
      'resource'  => 'posts',
      'id'        => $the_post->id
    ));
    echo "\">reply</a>";
    if (!(environment('threaded')))
      return;
  }
  
  if ($theme == 'p2')
    return "";
  
  echo "|&nbsp;<a href=\"JavaScript:add_comment('addcomment-$the_post->id')";
  echo "\">comment</a><div id=\"addcomment-$the_post->id\"></div>";
  
$userurl = "http://megapump.com";
$etag = 1;

echo '
<script src="'.base_path(true).'resource/jeditable/jquery.jeditable.js" type="text/javascript"></script>

<script type="text/javascript">
// <![CDATA[

function add_comment(divid) {
  
  var submit_to = "'.$userurl.'";
  
  $("#"+divid).editable(submit_to, { 
      indicator   : "<img src=\"'.base_path(true).'resource/jeditable/indicator.gif\">",
      submitdata  : function() {
        return {
          "entry[etag]"     : "'.$etag.'",
        };
      },
      name        : "setting[value]",
      type      : "textarea",
      rows      : 3,
      submit    : "OK",
      noappend  : true,
      cancel    : "Cancel",
      tooltip   : "Click to edit...",
      callback  : function(value, settings) {
        return(value);
      }
  });
  
  $("#"+divid).click();
  
}



// ]]>
</script>

';

  

}

function comments_number() {
  echo "";
}

function comments_template() {
  
  // dbscript
  global $request, $db;
  
  // wordpress
  global $blogdata, $optiondata, $current_user, $user_login, $userdata;
  global $user_level, $user_ID, $user_email, $user_url, $user_pass_md5;
  global $wpdb, $wp_query, $post, $limit_max, $limit_offset, $comments;
  global $req, $wp_rewrite, $wp_version, $openid, $user_identity, $logic;
  global $comment_author; 
  global $comment_author_email;
  global $comment_author_url;
  $user_ID = 0;
  global $the_post;
  $id = $the_post->id;
  $comments = array(1);
  include('comments.php');
}

function get_template_directory() {
  return theme_path();
}

function comment_ID() {
  global $request;
  return $request->id;
}

function comment_author_link( ) {
  return "#";
}

function edit_comment_link( $label ) {
  echo "#";
}

function comment_time( $format ) {
  echo the_time($format);
}

function comment_date() {
  return "";
}

function comment_type() {
  echo "Comment";
}

function comment_text() {
  
  global $the_post;
global $db;
if ($the_post->id) {
$sql = "SELECT title from posts where parent_id = ".$the_post->id;

$result = $db->get_result($sql);

    while ( $row = $db->fetch_array( $result ) ) {
  //  $id = owner_of($p);
    //echo "<h3>".$id->nickname." said:</h3>\n";
    echo "<p>".$row['title']."</p>\n";
  }
}   else {
  
}
  
}

function check_admin_referer( $var ) {
  return false;
}

function apply_filters($tag, $string) {
	global $wp_filter, $merged_filters;

	if ( !isset( $merged_filters[ $tag ] ) )
		merge_filters($tag);

	if ( !isset($wp_filter[$tag]) )
		return $string;

	reset( $wp_filter[ $tag ] );

	$args = func_get_args();

	do{
		foreach( (array) current($wp_filter[$tag]) as $the_ )
			if ( !is_null($the_['function']) ){
				$args[1] = $string;
				$string = call_user_func_array($the_['function'], array_slice($args, 1, (int) $the_['accepted_args']));
			}

	} while ( next($wp_filter[$tag]) !== false );

	return $string;
}

function current_user_can( $action ) {
  global $request;
  if ($action == 'publish_posts' && ($request->resource != 'posts' || $request->action != 'index'))
    return false;
  $id = get_profile_id();
  if (isset($request->params['byid']))
    $byid = $request->params['byid'];
  else
    $byid = 0;
  if ($byid && $id == $byid)
    return true;
  elseif (!$byid && $id)
    return true;
  return false;
}

function sanitize_user($user) { 
  return $user; 
}

function setup_postdata( &$post ) {
  return "";
}

function dynamic_sidebar() {
  global $request;
  global $sidebar_done;

  if (!$sidebar_done && get_profile_id() && $request->resource == 'identities' && in_array($request->action,array('edit','entry'))) {
    if ($request->id == get_profile_id())
      render_partial('admin');
    $sidebar_done = true;
    return true;
  }
    
    
  $blocks = environment('blocks');
  if (!empty($blocks) && !$sidebar_done) {
    foreach ($blocks as $b) {
      // if it's the prologue theme, don't show PAGES in sidebar
      if (!($b == 'pages' && environment('theme') == 'prologue-theme')){
        //$renderpartial = true;
        if (isset($renderpartial)) {
          // this would be better/faster, but not working yet
          echo '<script type="text/javascript">';
          render_partial(array('resource'=>$b,'action'=>'block.js'));
          echo '</script>';
        } else {
          // doing a call back to the server for each block. not cool XXX
          echo '<script type="text/javascript" src="'.$request->url_for(array('resource'=>$b,'action'=>'block.js')).'"></script>';
        }
      }
    }
    $sidebar_done = true;
  }
  if (environment('theme') == 'prologue-theme')
    echo '<a href="http://openmicroblogger.org"><img src="http://openmicroblogger.org/omb.gif" style="border:none;" alt="openmicroblogger.org" /></a>'."\n";
  return true;
}

function single_tag_title( ) {
  echo "";
}

function get_categories() {
  echo "";
}

function register_xrd($id, $type=array(), $expires=false) {
  $xrd = get_option('xrds_simple');
  if(!is_array($xrd)) $xrd = array();
  $xrd[$id] = array('type' => $type, 'expires' => $expires, 'services' => array());
  update_option('xrds_simple', $xrd);
}//end function register_xrd

/*
Format of $content:
array(
  'NodeName (ie, Type)' => array( array('attribute' => 'value', 'content' => 'content string') , ... ) ,
)
*/
function register_xrd_service($xrd_id, $name, $content, $priority=10) {
  $xrd = get_option('xrds_simple');
  if(!is_array($xrd[$xrd_id])) register_xrd($xrd_id);
  $xrd[$xrd_id]['services'][$name] = array('priority' => $priority, 'content' => $content);
    update_option('xrds_simple', $xrd);
}//end register_xrd_service



function wp_add_options($prefix,$options) {
  
  foreach( $options as $optname=>$optvalue )
    add_option( $prefix.'_'.$optname, $optvalue );
  
}










// dbscript
global $request, $db;

// wordpress
global $blogdata, $optiondata, $current_user, $user_login, $userdata;
global $user_level, $user_ID, $user_email, $user_url, $user_pass_md5;
global $wpdb, $wp_query, $post, $limit_max, $limit_offset, $comments;
global $req, $wp_rewrite, $wp_version, $openid, $user_identity, $logic;
global $submenu;

global $comment_author; 
global $comment_author_email;
global $comment_author_url;


// added the following line to ParanoidHTTPFetcher line 171

// curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);

require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'wp-plugins'.DIRECTORY_SEPARATOR.'wp-config.php';

if (environment('openid_version') > 1)
  $db->create_openid_tables();


$wp_version = 2.6;
$wpdb = new wpdb();
$wp_query = new WP_Query();
$post = new WpPost();
$comments = false;
$user_ID = get_profile_id();
$req = false;

if ($user_ID)
  $comments = true;

$blogdata = array(
  'home'=>base_url(true),
  'name'=>environment('site_title'),
  'subtitle'=>environment('site_subtitle'),
  'description'=>environment('site_description'),
  'wpurl'=>base_url(true),
  'url'=>base_url(true),
  'atom_url'=>base_url(true)."?posts.atom",
  'rss_url'=>base_url(true)."?posts.rss",
  'rss2_url'=>base_url(true)."?posts.rss",
  'charset'=>'',
  'html_type'=>'',
  'theme_url'=>theme_path(),
  'stylesheet_url'=>theme_path()."style.css",
  'stylesheet_directory'=>theme_path(),
  'pingback_url'=>base_url(true),
  'template_url'=>theme_path(true)
);

$optiondata = array(
  'date_format'=>'F j, Y',
  'gmt_offset'=>(date('Z') / 3600),
  'xrds_simple'=>array(),
  'oauth_services'=>array(),
  'oauth_version'=>0.12,
  'upload_path'=>'',
  'oid_enable_approval'=>true,
  'oid_enable_commentform'=>true,
  'home'=>base_url(true),
  'comment_registration'=>!$comments,
  'siteurl'=>base_url(true),
  'posts_per_page'=>20,
  'prologue_recent_projects'=>''
);

define('OBJECT', 'OBJECT', true);
define('ARRAY_A', 'ARRAY_A', false);
define('ARRAY_N', 'ARRAY_N', false);

define('TEMPLATEPATH', theme_path(true) );


$limit_max = get_option( 'posts_per_page' );
$limit_offset = 0;













?>