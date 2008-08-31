<?php

// dbscript
global $request, $db;

// wordpress
global $blogdata, $optiondata, $current_user, $user_login, $userdata;
global $user_level, $user_ID, $user_email, $user_url, $user_pass_md5;
global $wpdb, $wp_query, $post, $limit_max, $limit_offset, $comments;
global $req, $wp_rewrite, $wp_version, $openid, $user_identity, $logic;

// added the following line to ParanoidHTTPFetcher line 171

// curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);

require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'wp-plugins'.DIRECTORY_SEPARATOR.'wp-config.php';

$db->create_openid_tables();

$blogdata = array(
  'home'=>$request->base,
  'name'=>environment('site_title'),
  'name'=>environment('site_subtitle'),
  'description'=>environment('site_description'),
  'wpurl'=>$request->base,
  'url'=>$request->base,
  'atom_url'=>$request->base."?posts.atom",
  'rss_url'=>$request->base."?posts.rss",
  'rss2_url'=>$request->base."?posts.rss",
  'charset'=>'',
  'html_type'=>'',
  'theme_url'=>theme_path(),
  'stylesheet_url'=>theme_path()."style.css",
  'pingback_url'=>$request->base,
  'template_url'=>theme_path()
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
  'home'=>$request->base,
  'comment_registration'=>true,
  'siteurl'=>$request->base,
  'posts_per_page'=>20,
  'prologue_recent_projects'=>''
);

define('OBJECT', 'OBJECT', true);
define('ARRAY_A', 'ARRAY_A', false);
define('ARRAY_N', 'ARRAY_N', false);


$wp_version = 2.6;
$wpdb = new wpdb();
$wp_query = new WP_Query();
$post = new wppost();
$limit_max = get_option( 'posts_per_page' );
$limit_offset = 0;
$comments = false;
$user_ID = get_profile_id();
$req = false;

function allowed_tags() {
  return true;
}

function do_action() {
  return true;
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
    $this->posts = 'posts';
    $this->col_info = array();
    $this->last_result = array();
    $this->base_prefix = "";
    $this->prefix = "";
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
      echo "<BR><BR>QUERY FAILED -- ".$query."<BR><BR>";
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

    $pos = strpos($query,"update usermeta");
    if (!($pos === false))
      return true;
    global $db;
    
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

function get_bloginfo( $var ) {
  global $blogdata;
  if (isset($blogdata[$var]))
    return $blogdata[$var];
  return "";
} 

function add_option( $opt, $newval ) {
  global $optiondata;
  $optiondata[$opt] = $newval;
}


class wppost {
  var $post_password = "";
  var $comment_status = "open";
  function wppost() {
  }
}

class wpcomment {
  var $user_id = 0;
  var $comment_author_email = "";
  var $comment_approved = false;
  function wpcomment() {
  }
}

function update_option( $opt, $newval ) {
  global $optiondata;
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
    return array();
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

class wptag {
  var $term_id = 0;
  var $count = 0;
  var $name = "";
  function wptag() {
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

function add_filter() {
  
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
  global $blogdata;
  if (isset($blogdata[$attr]))
    echo $blogdata[$attr];
}

function get_option( $opt ) {
  global $optiondata;
  
  if (!isset($optiondata[$opt]))
    return "";
    
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


  return true;
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

function wp_enqueue_script( $file ) {
  require_once $file;
}

function wp_title() {
  echo environment('site_title');
}

function wp_head() {
    global $request;
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

    if (($request->uri != $edit_uri) && (!isset($p->nickname) || empty($p->avatar))) {
      $_SESSION['message'] = "Photo and Nickname are required.";
      redirect_to($edit_uri);
    }

}  

  
  include('header.php');
}
function is_page() {
  return false;
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

function get_avatar( $wpcom_user_id, $email, $size, $rating = '', $default = 'http://s.wordpress.com/i/mu.gif' ) {
  echo "";
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

function get_recent_post_ids( $return_as_string = true ) {
  return "";
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
  global $the_post,$response,$the_author,$the_entry;
  $the_post =& $response->collection->MoveNext();
  if (isset($the_post->profile_id)){
    $the_author = get_profile($the_post->profile_id);
  }else{
    global $db;
    $Identity =& $db->model('Identity');
    if ($the_post) {
      $the_entry = $the_post->FirstChild( 'entries' );
      if ($the_entry->person_id) {
        $the_author = $Identity->find_by('entries.person_id',$the_entry->person_id);
      } else {
        $the_author = $Identity->base();
      }
    } else {
      $Post =& $db->model('Post');
      $the_post = $Post->base();
    }
  }
  if (!empty($the_author->profile_url)) $the_author->profile = $the_author->profile_url; 
  
  return "";
}
function get_links() {
  echo "";
}
function the_excerpt() {
  echo "";
}
function get_post_meta() {
  return array();
}
function wp_link_pages() {
  echo "";
}
function the_search_query() {
  echo "";
}
function comments_open() {
  return true;
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
  return "";
}

function prologue_get_avatar( $current_user_id, $author_email, $pixels ) {
  global $the_author,$request,$the_post;
  $avatar = "";
  if (!empty($the_author->avatar)) {
    $avatar = $the_author->avatar;
  } else {
    $p = get_profile();
    if (!isset($the_post->id) || ($the_author->id == $p->id))
      $avatar = $p->avatar;
  }
  if (!(empty($avatar)))
    return '<a href="'.$the_author->profile.'"><img alt="avatar" src="' . $avatar . '" style="width:'.$pixels.'px;height:'.$pixels.'px;" class="avatar" /></a>';
}

function get_the_author_email() {
  global $the_author;
  return $the_author->email_value;
}

function the_author() {
  global $the_author;
  echo $the_author->fullname;
}

function the_category() {
  return "";
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
  global $the_post,$request,$the_author;
  $e = $the_post->FirstChild('entries');
  
  $title = $the_post->title;
  
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
  
  if ($e->content_type != 'text/html') {
    echo "<div class='snap_preview'><p><a href=\"".$request->url_for(array('resource'=>'__'.$the_post->id))."\">".$the_post->title."</a></p></div>";
  } else {
    echo "<div class='snap_preview'><p>".$title."</p></div>";
  }
}

function have_posts() {

  global $response;
  global $db;
  
  
  //$Post =& $db->model('Post');
  //echo $Post->get_query();
  $rows = count($response->collection->members);

  if ($response->collection->_currentRow >= $rows)
    return false;
  if (!$response->collection->EOF && (0 < $rows))
    return true;
  return $response->collection->EOF;
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
  $links['Public'] = $request->base;
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
    $links['Profile'] = $i->profile;
  }
  if ($pid > 0) {
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

function prologue_recent_projects_widget( $args ) {
  return "";
}

function prologue_recent_projects( $num_to_show = 35, $before = '', $after = '' ) {
  return $before.$after;
}

function prologue_recent_projects_control() {
  return "";
}

function prologue_admin_header_style( ) {
  return "";
}

function _e($t) {
  echo $t;
}

function load_javascript() {
  return "";
}

function register_sidebar() {
  return false;
}

function add_action( $act, $func ) {
  return false;
}

function add_custom_image_header( $var, $name ) {
  return false;
}

function edit_post_link( $post ) {
  global $the_post,$request;
  if ($the_post->profile_id == get_profile_id())
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

function comments_popup_link( $var1, $var2, $var3 ) {
  global $the_post;
  global $request;
  echo "<a href=\"";
  echo $request->url_for(array(
    'resource'  => 'posts',
    'id'        => $the_post->id
  ));
  //url_for(array(
  //  'resource' => 'comments',
  //  'action'   => 'new',
  //  'id'       => $the_post->id
  //));
  echo "\">reply</a>";
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
  
  include('comments.php');
}

function comment_ID() {
  return 0;
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

function comment_text() {
  return "";
}

function check_admin_referer( $var ) {
  return false;
}

function apply_filters( $pre, $content ) {
  return false;
}

function current_user_can( $action ) {
  global $request;
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

function setup_postdata( $post ) {
  return "";
}

function dynamic_sidebar() {
  global $request;
  $blocks = environment('blocks');
  if (!empty($blocks)) {
    foreach ($blocks as $b) {
      echo '<script type="text/javascript" src="'.$request->url_for(array('resource'=>$b,'action'=>'block.js')).'"></script>';
    }
  }
  echo '<a href="http://openmicroblogger.org"><img src="http://openmicroblogger.org/omb.gif" style="border:none;" alt="openmicroblogger.org" /></a>'."\n";
  return true;
}

function single_tag_title( ) {
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



?>