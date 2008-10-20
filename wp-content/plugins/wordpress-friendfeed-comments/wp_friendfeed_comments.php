<?php
ob_start();
session_start();
/*
Plugin Name: Friendfeed Comments
Plugin URI: http://www.gurkanoluc.com/friendfeed-comments
Description:  This plugin automatically submits your WordPress blog posts URLs into FriendFeed, and in return shows all received FriendFeed comments under comments section of your blog posts. It allows you to show FriendFeed comments & 'likes' in your blog
Version: 0.5
Author: Gürkan OLUÇ
Author URI: http://www.gurkanoluc.com
*/

/*  Copyright 2008   Gürkan OLUÇ  (email : me@gurkanoluc.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

global $wpdb;
define('DB_PREFIX',$wpdb->prefix);
define('TABLE_NAME',DB_PREFIX.'ff_comments_posts');
define('COMMENTS_TABLE_NAME',DB_PREFIX.'ff_comments');
define('BLACKLIST_TABLE_NAME',DB_PREFIX.'ff_comments_blacklist');
define('PLUGIN_DIR',dirname(__FILE__));
define('URL',get_option('siteurl').'/wp-content/plugins/wordpress-friendfeed-comments/');
define('IS_DEBUG',0); // Değiştirmeyiniz

if( WPLANG == '' ) {
	include_once PLUGIN_DIR.'/en_EN.php';
} else { 
	if( file_exists(PLUGIN_DIR.'/'.WPLANG.'.php') ) {
		include_once PLUGIN_DIR.'/'.WPLANG.'.php';
	} else {
		include_once PLUGIN_DIR.'/en_EN.php';
	}
}

if( !class_exists('FriendFeed') ) { 
	include PLUGIN_DIR.'/friendfeed.php';
}

if( !function_exists('json_decode') AND !class_exists('Services_JSON') ) {
	require("JSON.php");
}

$ff_username = get_option('ff_username');
$ff_remote_key = get_option('ff_remote_key');

// --------------------------------------------------

/**
 * Install function
 * 
 * 
 * This function creates db tables if they don't exists
 * 
 */

// add_action('init', 'friendfeed_comments_install'); 

register_activation_hook(__FILE__,'friendfeed_comments_install');

function friendfeed_comments_install() {

	global $wpdb;

	/**
	 * Create ff_comments_posts table
	 */
	$sql = "CREATE TABLE IF NOT EXISTS `". TABLE_NAME ."` (
			`id` INT NOT NULL AUTO_INCREMENT ,
			`post_id` INT NOT NULL ,
			`friendfeed_id` VARCHAR( 255 ) NOT NULL ,
			PRIMARY KEY ( `id` )
			) ENGINE = InnoDB"			
			;
	
	$wpdb->query($sql);
	
	/**
	 * Create ff_comments table
	 */
	$sql = "CREATE TABLE IF NOT EXISTS `". COMMENTS_TABLE_NAME ."` (
		  `id` int(11) NOT NULL auto_increment,
		  `comment_ID` varchar(255) NOT NULL,
		  `comment_post_ID` int(11) NOT NULL,
		  `comment_author` varchar(255) NOT NULL,
		  `comment_author_url` varchar(255) NOT NULL,
		  `comment_date` datetime NOT NULL,
		  `comment_content` text NOT NULL,
		  `is_ff_comment` int(11) NOT NULL default '1',
		  PRIMARY KEY  (`id`)
) ENGINE=InnoDB";
			
	$wpdb->query($sql);
	
	/**
	 * Create ff_comments_blacklist table
	 */
	$sql = "CREATE TABLE IF NOT EXISTS `". BLACKLIST_TABLE_NAME ."` (
  						 `ff_id` varchar(255) NOT NULL,
  						  PRIMARY KEY  (`ff_id`)
			) ENGINE=InnoDB";
	
	$wpdb->query($sql);
	
	add_option('ff_username','');
	add_option('ff_remote_key','');
	add_option('send_admin_comment_to_ff',0);
	
		
}

/**
 * Adds CSS files quickly
 * 
 * This function takes css file name
 * and includes it to page
 * 
 * @param string
 * 
 * @return string
 * 
 */
function css_link($css_file) {
	return "<link rel=\"stylesheet\" href=\"".URL. 'css/'. $css_file.".css\" media=\"screen\"/>\n";
}

// --------------------------------------------------

/**
 * Adds JS files quickly
 * 
 * This function takes js file name
 * and includes it to page
 * 
 * @param string
 * 
 * @return string
 * 
 */
function js_link($js_file) {
	return "<script src=\"". URL.'js/'.$js_file .".js\" type=\"text/javascript\"></script>\n";
}

// --------------------------------------------------

/**
 * Generate link for plugin page
 * 
 * This functiong gets query string then adds it to 
 * options-general.php?page=wp_friendfeed_comments/wp_friendfeed_comments.php
 * 
 * @param string
 * 
 * return string
 * 
 */
function make_link($query_string = '') {
		if( empty($query_string)) {
			return 'options-general.php?page=wp_friendfeed_comments/wp_friendfeed_comments.php';
		} else {
			return 'options-general.php?page=wp_friendfeed_comments/wp_friendfeed_comments.php&'.$query_string;
		}
		
}

// --------------------------------------------------

/**
 * Look for flash message
 * 
 * This function looks to ff_flash_message session
 * If it's not empty function returns true, 
 * else function returns false
 * 
 * @return bool
 **/
function is_there_flash_notice() {
	return ( !empty($_SESSION['ff_flash_message']) ) ? true : false;
}

// --------------------------------------------------

/**
 * Set or Show Flash Notice
 * 
 * This function shows flash notice if $message variable is empty
 * If $message variable is not empty sets ff_flash_message session
 * value with given message
 * 
 * @param string
 * 
 * @return string|nothing
 * 
 **/
function flash_notice($message = '') {
	if( empty($message) ) {
		$msg = $_SESSION['ff_flash_message'];
		$_SESSION['ff_flash_message'] = '';
		return $msg;
	} else {
		$_SESSION['ff_flash_message'] = $message;
	}
}

// --------------------------------------------------

/**
 * Redirect Function
 * 
 * This function redirects page to given querystring
 * 
 * @param string
 * 
 */
function redirect($query_string='') {
	header('Location: '.make_link($query_string).'');
}

// --------------------------------------------------

add_action('admin_menu', 'friendfeed_comments_ekle');
/**
 * Add link to options panel
 * 
 * This function adds link to options-general.php page in admin panel
 * 
 * @return nothing
 * 
 */
function friendfeed_comments_ekle() {
	add_submenu_page('options-general.php', 'Friendfeed Comments', 'Friendfeed Comments', 10, __FILE__, 'friendfeed_comments_menu');
}

// --------------------------------------------------

add_action('admin_head','friendfeed_comments_head');
/**
 * Add extra head content to WP Admin head
 * 
 * @return nothing
 * 
 */
function friendfeed_comments_head() {
	echo "\n<!-- WP FF Comments JS -->\n";
	echo css_link('friendfeed_comments');
	// Different WP versions make problem with jquery
	// wp_enqueue_script('wp_ff_admin', '/wp-content/plugins/wordpress-friendfeed-comments/js/wp_ff_admin.js');
	// wp_print_scripts();
	
}

// --------------------------------------------------

/**
 * Add link to FF item
 *
 * @param integer
 *
 * @return string
 */
function ff_item_link($ff_id = '') {
	if( empty($ff_id) ) {
		return false;
	} else {
		return "<a href=\"http://friendfeed.com/e/{$ff_id}\" target=\"_blank\">{$ff_id}</a>";
	}
}

// --------------------------------------------------

add_action('wp_head','wp_ff_js');
function wp_ff_js() {
	
	if( is_single() ) { 
	echo "\n<!-- WP FF Comments JS -->\n";
		wp_enqueue_script('jquery');
		wp_enqueue_script('wp_ff_js', '/wp-content/plugins/wordpress-friendfeed-comments/js/wp_ff_front.js');
		wp_print_scripts(array('jquery','wp_ff_js'));
	} else {
		return false;
	}

}

// --------------------------------------------------

/**
 * Show likes function
 * 
 * This function shows people who liked post on FriendFeed
 * 
 * @return string
 * 
 */ 
function friendfeed_comments_show_likes($limit = 4) {
	
	global $post,$wpdb,$ff_username,$ff_remote_key;
	
	$q = $wpdb->get_row($wpdb->prepare("SELECT * FROM ". TABLE_NAME ." WHERE post_id = %d",$post->ID));
	
	if( $q ) { 
		
		// FF'e Bağlan
		$ff = new FriendFeed($ff_username,$ff_remote_key);
		$ff_item = $ff->fetch('/api/feed/entry/'.$q->friendfeed_id);
		
		// Bağlantı başarısız ise false döndür
		if(!$ff_item) {
			return false;
		}
		
		// FF üzerinde like edilen ögeleri al
		$ff_likes = $ff_item->entries[0]->likes;
		
		$string = '';
		
		// Like eden varsa işlem yap
		// like edenlerin adlarını sayfaya ekle link olarak
		// @todo Bir sonraki sürümde burada kullanıcak CSS sınıfı, XHTML kodu falan hepsi özelleştirilebilir olsun?
		if( count($ff_likes) > 0 ) {
			$i = 0;
			foreach( $ff_likes AS $like ) {
				
				if( $i == $limit ) {
					$string .= '<a href="javascript:;" id="wp_ff_likes_link">'. sprintf(SHOW_OTHER_LIKES,(count($ff_likes)-$limit)).'</a>';
					$string .= '<span id="wp_ff_likes" style="display:none;">';
				}

				$string .= '<a href="'.$like->user->profileUrl.'" target="_blank">'.$like->user->name.'</a>';
				
				if( $i + 1 == $limit ) $string .= ' ';
				else { 
					if( $i + 1 < count($ff_likes) ) {
						$string .= ', ';
					}
				}
				
				if( $i + 1 == count($ff_likes) ) {
					$string .= '</span>';
				}
				
				$i++;
				
			}
			
			if( $i == 1 ) {
				return $string.' '.SINGLE_LIKED_MSG;
			} else {
				return $string.' '.LIKED_MSG;
			}
			
			return $string;
		
		} else {
			return false;
		} 
		
	} else {
		return false;
	}
	
}

// --------------------------------------------------

add_action('publish_post','friendfeed_comments_save');
/**
 * Save function
 * 
 * This function adds post to FriendFeed as a link after post had saved
 * 
 * @return bool
 * 
 */
function friendfeed_comments_save($post_ID) {

	global $wpdb,$ff_username,$ff_remote_key;
	
	
	$post_title = $_POST['post_title'];
	$permalink = get_permalink($post_ID);
	
	// Eğer bir yazı düzenleniyor ise yeni kayıt yapma
	// False döndür
	if( !isset($_POST['publish'])) {
		return false;
	} else { 
	
		if( empty($ff_username) OR empty($ff_remote_key) ) {
			return false;
		} else {
			$ff = new Friendfeed($ff_username,$ff_remote_key);
			$entry = $ff->publish_link($post_title, $permalink);
			
			if( $entry ) {
				$ff_id = $entry->id;
			} else {
				return false;
			}
			
			$sql = "INSERT INTO ".TABLE_NAME." (post_id ,friendfeed_id) VALUES (%d, %s)";
			
			
			$query = $wpdb->query($wpdb->prepare($sql,$post_ID,$ff_id));
			return true;
		}
	}

}

// --------------------------------------------------

/**
 * Main Function
 * 
 * @return string
 * 
 */
function friendfeed_comments_menu() {
	
	global $wpdb,$ff_username,$ff_remote_key;
	
	$action = $_GET['action'];
	
	echo "<div id=\"ff_comments\">";
	
	if( is_there_flash_notice() ) {  
		echo '<div class="updated fade" id="message"><p><strong>'. flash_notice() .'</strong></p></div>';
	} 
	
	if( empty($action) ) {
	
		echo '<div style="width:800px; margin-bottom:20px; height:auto;">';
		// echo '<a href="javascript:;" id="open_options"><h2 class="normal">'. OPTIONS .'</h2></a>';
		echo '<h2>'. OPTIONS .'</h2>';
		// echo '<div id="options" style="display:none;">';
		echo '<div id="options">';
		echo '<form method="POST" name="ff_form" action="'. make_link('action=save_ff'). '">';
		echo '<table class="form-table">';
		echo '<tr valign="top">';
		echo '<th scope="row">'. FF_USERNAME .'</th>';
		echo '<td><input name="ff_username" type="text" id="ff_username" size="40" value="'.get_option('ff_username').'" /></td>';
		echo '</tr>';
		echo '<tr valign="top">';
		echo '<th scope="row">'. FF_REMOTE_KEY .'</th>';
		echo '<td><input name="ff_remote_key" type="password" id="ff_remote_key" size="40" value="'.get_option('ff_remote_key').'" /></td>';
		echo '</tr>';
		echo '<tr valign="top">';
		if( get_option('ff_send_admin_comment') ) {
			echo '<td><input name="ff_send_admin_comment" type="checkbox" checked="checked" id="ff_send_admin_comment" size="40" value="1" /></td>';
		} else {
			echo '<td><input name="ff_send_admin_comment" type="checkbox" id="ff_send_admin_comment" size="40" value="1" /></td>';
		}
		echo '<th scope="row">'. FF_SEND_ADMIN_COMMENT .'</th>';
		echo '</tr>';		
		echo '</table>';
		echo '<p class="submit"><input type="submit" name="ff_save_btn" value="'. FF_SAVE .'" /></p>';	
		echo '</form>';
		echo '</div>';
		echo "</div>";
		
		$sql = "SELECT * FROM ". TABLE_NAME ." ORDER BY id DESC";
		$posts = $wpdb->get_results($sql);
		echo '<h2>'. YOUR_FF_ITEMS .'</h2>';
		echo '<table class="widefat" style="width:800px;">';
		echo '<thead>';
		echo '<tr>';
		echo '	<th class="check-column" scope="col" style="text-align:center;">ID</th>';
		// echo '	<th scope="col"></th>';
		echo '	<th scope="col">'. POST_TITLE .'</th>';
		echo '	<th scope="col">'. FRIENDFEED_ID .'</th>';
		echo '	<th scope="col">'. FF_DELETE .'</th>';
		echo '	<th scope="col">'. FF_COMMENTS .'</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		if( $posts ) {
			foreach( $posts AS $post ) {
				$post_data = friendfeed_comments_post_title($post->post_id);
				echo '<tr valign="top" class="alternate author-self status-publish" id="post-375">';
				echo '	<td>'.$post_data->ID.'</td>';
				echo '	<td><a href="'.get_permalink($post_data->ID).'" target="_blank">'. $post_data->post_title .'</a></td>';
				echo '	<td>'. ff_item_link($post->friendfeed_id) .'</td>';
				echo '	<td><a href="'. make_link('action=delete_entry&id=' . $post->id ).'" onclick="return confirm(\''. ARE_YOU_SURE .'\');">'. FF_DELETE .'</a></td>';
				echo '	<td><a href="'. make_link('action=show_comments&post_id=' . $post_data->ID ).'">'. SHOW_FF_COMMENTS .'</a></td>';
				echo '</tr>';
			}
		} else {
			echo '<tr>';
			echo '<td colspan="5">';
			echo '<center>'. FF_ITEMS_NOT_FOUND .'</center>';
			echo '</td>';
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';		
		
	}
	else if ( $action == 'save_ff' ) {
		friendfeed_comments_save_ff();
	} 
	else if( $action == 'delete_entry' ) {
		
		$id = $_GET['id'];
		wp_ff_delete_entry($id);

	}
	else if( $action == 'delete_comment' ) {
		$ff_id = $_GET['ff_id'];
		$post_id = intval($_GET['post_id']);
		wp_ff_delete_comment($ff_id, $post_id);
	}
	else if( $action == 'show_comments') {
		$post_id = $wpdb->escape($_GET['post_id']);
		show_posts_ff_comments($post_id);
	}
	echo "</div>";
}

// --------------------------------------------------

/**
 * This functions deletes saved FF entry and its comments from DB
 *
 * @param integer $id
 */
function wp_ff_delete_entry($id) {
	
	$item = $wpdb->get_row($wpdb->prepare("SELECT * FROM ". TABLE_NAME. " WHERE id = %d",$id));
	
	// Yorumları Sil
	$wpdb->query($wpdb->prepare("DELETE FROM ". COMMENTS_TABLE_NAME ." WHERE comment_post_ID = %d",$item->post_id));
	
	// Ögeyi Sil
	$sql = "DELETE FROM ". TABLE_NAME ." WHERE id = %d";
	$q = $wpdb->query($wpdb->prepare($sql,$id));
	
	if( $q ) {
		flash_notice(FF_ITEM_DELETED_SUCCESS);
	} else {
		flash_notice(FF_ITEM_DELETED_FAIL);
	}
	redirect();	
}

// --------------------------------------------------

/**
 * This function deletes comment with given ff ID
 * After delete process redirects page to page that lists 
 * Post's comments on FF
 *
 * @param string $ff_id
 * @param integer $post_id
 * @return nothin
 */
function wp_ff_delete_comment($ff_id, $post_id) {
	
	global $wpdb;
	
	if( empty($ff_id) ) {
		return false;
	}
	
	
	// Yorumu Silelim
	$wpdb->query($wpdb->prepare("DELETE FROM ". COMMENTS_TABLE_NAME ." WHERE comment_ID = %s",$ff_id));
	
	// Blacklist'e ekliyelim ki bir daha gelmesin pis şey :)
	wp_ff_add_comment_to_blacklist($ff_id);
	
	flash_notice('Yorum Başarı ile silindi!');
	
	redirect('action=show_comments&post_id='. $post_id);
	
}

// --------------------------------------------------

/**
 * Show post's FF comments
 * 
 * This function shows post's friendfeed comments from db
 * 
 * @return string
 * 
 */
function show_posts_ff_comments($post_id) {
	global $wpdb;
	$comments = $wpdb->get_results("SELECT * FROM ". COMMENTS_TABLE_NAME ." WHERE comment_post_ID = '". $post_id ."'");
	$post_data = friendfeed_comments_post_title($post_id);
	echo '<h2>'. $post_data->post_title .' '. COMMENTS_FOR_POST_IN_FF .'</h2>';
	echo '<table class="widefat" style="width:800px;">';
	echo '<thead>';
	echo '<tr>';
	echo '<th scope="col">'. COMMENT_ID .'</th>';
	echo '<th scope="col">'. COMMENT .'</th>';
	echo '<th scope="col">'. COMMENT_DATE .'</th>';
	echo '<th scope="col">'. COMMENT_ACTIONS .'</th>';
	echo '</tr>';
	echo '</thead>';
	echo '<tbody class="list:comment" id="the-comment-list">';
	if( $comments ) { 
		foreach( $comments AS $comment ) { 
			echo '<tr class="" id="comment-1871">';
			echo '<td class="check-column" style="text-align:center;">'. $comment->id .'. </td>';
			echo '<td class="comment">';
			echo '<p class="comment-author"><strong><a title="Profil" href="'. $comment->author_url .'" class="row-title">'. $comment->comment_author .'</a></strong><br/>';
			echo '<a href="'. $comment->comment_author_url .'">'. $comment->comment_author_url .'</a>';  
			echo '</p>';
			echo '<p>'. $comment->comment_content .'</p>';
			echo '</td>';
			echo '<td>'. date('d M Y H:i',strtotime($comment->comment_date)) .'</td>';
			echo '<td><a href="'. make_link('action=delete_comment&ff_id='. $comment->comment_ID .'&post_id='. $post_id).'" onclick="return confirm(\''. ARE_YOU_SURE .'\');">Sil</a></td>';
			echo '</tr>';
		}
	} else {
		echo '<tr>';
		echo '<td colspan="5">'. NO_FF_COMMENTS .'</td>';
		echo '</tr>';
	}
	echo '</table>';
	
}

// --------------------------------------------------

/**
 * Save FF Options
 * 
 * This function saves author's friendfeed nickname and remote key
 * 
 * @return nothing
 * 
 */
function friendfeed_comments_save_ff() {
	if( !empty($_POST['ff_username'])) update_option('ff_username',$_POST['ff_username']);
	if( !empty($_POST['ff_remote_key'])) update_option('ff_remote_key',$_POST['ff_remote_key']);
	update_option('ff_send_admin_comment',$_POST['ff_send_admin_comment']);
	flash_notice(FF_DATA_SAVED_MSG);
	
	redirect();	
}

// --------------------------------------------------

/**
 * Get FF ID Function
 * 
 * This functions retrieves post's ID on FriendFeed
 * 
 * @param integer
 * 
 * @return integer
 * 
 */
function get_ff_id($post_id) {
	global $wpdb;
	$q = $wpdb->get_row("SELECT * FROM ". TABLE_NAME ." WHERE post_id = '".$post_id."'");
	if($q) {
		return $q->friendfeed_id;
	} else {
		return false;
	}
}

// --------------------------------------------------

/**
 * Get post title Function
 * 
 * This function gets post's title
 * 
 * @param integer
 * 
 * @return string
 * 
 */
function friendfeed_comments_post_title($post_id) {
	global $wpdb;
	
	$sql = "SELECT ID,post_title FROM " . DB_PREFIX . "posts WHERE ID = %d ORDER BY ID DESC LIMIT 1";
	$post = $wpdb->get_row($wpdb->prepare($sql,$post_id));
	
	return $post;
	
}

// --------------------------------------------------

add_filter('get_comments_number','set_comments_count');
/**
 * Set new comment number Function
 * 
 * This function sets new comments number by adding FriendFeed comments count to 
 * Normal comment numbers
 * 
 * @param integer
 * 
 * @return integer
 * 
 */
function set_comments_count($wp_comments_count = '') {

	global $post,$wpdb;
	
	$ff_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM ". TABLE_NAME ." WHERE post_id = %d",$post->ID));
	/**
	 * FF üzerinden yorumlar check ediyoruz
	 * Eğer veritabanında kayıtlı değilse
	 * Kayıt ediyoruz
	 */
	get_ff_comments_from_ff($ff_item->friendfeed_id);
	$ff_comments_count = get_ff_comments_count_from_db($post->ID);
	
	return $ff_comments_count + $wp_comments_count;
	
}

// --------------------------------------------------

add_filter('comments_array','set_comments_with_friendfeed');
/**
 * Add FF comments to WP comments array Function
 * 
 * This function adds FF comments to WP comments array
 * 
 * @param array
 * 
 * @return array
 * 
 */
function set_comments_with_friendfeed($comments) {

	global $post,$wpdb;
	
	$ff_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM ". TABLE_NAME ." WHERE post_id = %d",$post->ID));
	
	/**
	 * Eğer POST FF üzerine kayıt edilmemiş ise
	 * Normal yorumları döndür
	 */
	if(!$ff_item) {
		return $comments;
	} 
	
	/**
	 * Yorumları FF üzerinden check ediyoruz
	 * Eğer FF üzerinde olan blog üzerinde olmayan yorum varsa
	 * Veritabanına kaydediyoruz
	 */
	get_ff_comments_from_ff($ff_item->friendfeed_id);
	
	$ff_comments = get_ff_comments_from_db($post->ID);
	
	if( $ff_comments != false ) {
		foreach($ff_comments AS $comment) {
			$comments[] = $comment;
		}
	}
	
	return BubbleSort($comments,'comment_date',0);
}

// --------------------------------------------------

/**
 * Short Array Function
 * 
 * This function sorts array by given column name
 * 
 * @param array
 * @param string
 * @param integer
 * 
 * @return array
 * 
 */
function BubbleSort($sort_array,$column = 0,$reverse) {
  $lunghezza=count($sort_array);
  for ($i = 0; $i < $lunghezza ; $i++){
    for ($j = $i + 1; $j < $lunghezza ; $j++){
      if($reverse){
        if ($sort_array[$i]->$column < $sort_array[$j]->$column){
          $tmp = $sort_array[$i];
          $sort_array[$i] = $sort_array[$j];
          $sort_array[$j] = $tmp;
        }
      }else{
        if ($sort_array[$i]->$column > $sort_array[$j]->$column){
          $tmp = $sort_array[$i];
          $sort_array[$i] = $sort_array[$j];
          $sort_array[$j] = $tmp;
        }
      }
    }
  }
  return $sort_array;          
}

// --------------------------------------------------

add_filter('get_comment_author','add_ff_icon_to_comment_author');
/**
 * Add FF Icon to Comment Author Name
 *
 * This function adds mini FF icon to Comment Author's name
 * if comment comes from FF
 * 
 * @param string
 *
 * @return string
 */
function add_ff_icon_to_comment_author($author) {
	
	global $comment;
	
	$script_name = $_SERVER['PHP_SELF'];
	$script_name_array = explode('/',$script_name);
	
	/**
	  * Eğer yönetim panelinde yorumlar listeleniyorsa
	  * Normal yorumların listelenmesinde yorum yazarı kısmının
	  * Bozulmaması için sorgu yapıyoruz.
	  * Eğer kullanıcı wp-admin klasöründe bir yerlerde ise normal kullanıcı adı 
	  * Değilse FF iconu + Kullanıcı Adı dönüyor
	  */
	if ( $script_name_array[1] == 'wp-admin' )  {
		return $author;	
	} else {
	
		if( isset($comment->is_ff_comment) AND $comment->is_ff_comment == 1 ) { 
			return '<span style="float:left; margin-right:4px; "><img src="http://friendfeed.com/static/images/icons/internal.png"></span>'. $author;
		} else {
			return $author;
		}
	}
}

// --------------------------------------------------

/**
 * Get FF Comments Function
 * 
 * This function retrieves FF comments from FF API and
 * saves them to database if they aren't in blog's database
 * 
 * @param integer
 * 
 * @return array
 * 
 */
function get_ff_comments_from_ff($ff_id) {

	global $wpdb,$post,$ff_username,$ff_remote_key;
	$ff = new Friendfeed($ff_username,$ff_remote_key);
	
	if( !$ff ) {
		return false;
	}
	
	/**
	 * FF API üzerinden ögeye ait olan yorumları alıyoruz
	 */
	$ff_comments_q = $ff->fetch('/api/feed/entry/'.$ff_id);
	$ff_comments = $ff_comments_q->entries[0]->comments;
	
	if($ff_comments) {
	
		$insert_sql = "INSERT INTO ". COMMENTS_TABLE_NAME ." (comment_ID,
																	 comment_post_ID,
																	 comment_author,
																	 comment_author_url,
																	 comment_date,
																	 comment_content,
																	 is_ff_comment) VALUES ";
		$insert_sql_values = '';
	
		foreach( $ff_comments AS $comment ) { 
		
			$q = $wpdb->get_row($wpdb->prepare("SELECT * FROM ". COMMENTS_TABLE_NAME ." WHERE comment_ID = %s",$comment->id));
			
			/**
			 * Eğer yorum veritabanı tablosunda kayıtlı değilse
			 * Ve daha önce eklenip silinmemiş ise kayıt ediyoruz
			 */
			if( empty($q) ) {
				
				if( !wp_ff_is_comment_in_blacklist($comment->id) ) {
				
					$insert_sql_values .= "('".$wpdb->escape($comment->id)."',
										   '".$wpdb->escape($post->ID)."',
										   '".$wpdb->escape($comment->user->name)."',
										   '".$wpdb->escape($comment->user->profileUrl)."',
										   '".$wpdb->escape($comment->date)."',
										   '".$wpdb->escape($comment->body)."',
										   1),";
				}
						
			}
		}
		
		// Eğer insert stringi boş değil ise eklenecek yorum var demektir
		// Ee eklenecek yorum varsa ekliyelim değil mi? :) 
		if( !empty($insert_sql_values) ) {
		
			$wpdb->query($insert_sql.substr($insert_sql_values,0,strlen($insert_sql_values)-1));
		
		}
		
	}
	
	return $ff_comments;

}

// --------------------------------------------------

/**
 * Get FF comments from db Function
 * 
 * This function retrieves FF comments from blog's db
 * 
 * @param integer
 * 
 * @return array
 * 
 */
function get_ff_comments_from_db($post_ID) {
	global $wpdb;
	$ff_comments = $wpdb->get_results("SELECT * FROM " . COMMENTS_TABLE_NAME ." WHERE comment_post_ID = '". $post_ID ."' ORDER BY comment_date DESC");
	return $ff_comments;
}

// --------------------------------------------------

/**
 * Get comments count Function
 * 
 * This function gets FF comments count from blog's db
 * 
 * @param integer
 * 
 * @return integer
 * 
 */
function get_ff_comments_count_from_db($post_ID) {
	global $wpdb;
	$q = $wpdb->get_row("SELECT COUNT(*) AS comment_count FROM ". COMMENTS_TABLE_NAME ." WHERE comment_post_ID = '". $post_ID ."'");
	return $q->comment_count;
}

// --------------------------------------------------

add_action('comment_post','send_admin_comment_to_ff');
/**
 * Send Comment to FF Function
 *
 * This function adds comment to FF entry if
 * Send comments to FF option is true
 *
 * @param integer
 *
 * @return bool
 *
 */
function send_admin_comment_to_ff($comment_ID) {

	global $wpdb,$ff_username,$ff_remote_key;
	
	$ff = new FriendFeed($ff_username,$ff_remote_key);
	
	if( !$ff ) {
		return false;
	} else {
	
		$ff_send_admin_comment = get_option('ff_send_admin_comment');
	
		if( empty($ff_send_admin_comment) ) {
			return false;
		} else { 
		
			// Eklenen yorumun bilgilerini al
			$comment = get_commentdata($comment_ID,1);
			
			$comment_post_ID = $comment['comment_post_ID'];
			
			$ff_item = $wpdb->get_row($wpdb->prepare('SELECT * FROM '. TABLE_NAME . ' WHERE post_id = %d',$comment_post_ID));
			
			if( !$ff_item ) {
				return false;
			} else { 
				// Yorumu yapan kullanıcı sitede kayıtlı mı?
				if( wp_ff_user_is_exists($comment['user_id'])) {
				
					// Kullanıcının sitedeki rolü
					$user_role = wp_ff_get_user_role($comment['user_id']);
					
					// Eğer admin ise
					if( $user_role['administrator'] == 1 ) {
						
						// FF API @ sorunu için hack :)  
						$comment = str_replace('@',' @',$comment['comment_content']);
						
						// Eğer Karakter Sayısı 512'den az ise tek yorum gönder
						if( mb_strlen($comment['comment_content']) < 512 ) {
							$comment_id = $ff->add_comment($ff_item->friendfeed_id,$comment);
						} else {
							
							$link = get_permalink($comment['comment_post_ID']). '#comment-' .$comment_ID;
							
							$link_character_count = mb_strlen($link,'UTF-8');	
						
							$limit = 500 - ( $link_character_count + 5 );

							// Yorumu limitlemek lazım
							$comment = mb_substr($comment,0,$limit,'UTF-8');
							$comment_content = $comment . '... '. $link;

							$comment_id = $ff->add_comment($ff_item->friendfeed_id,$comment_content);
						    	
						}
						
						if( $comment_id ) {
							
							wp_ff_add_comment_to_blacklist($comment_id);
							return true;
							
							
						} else {
							return false;
						}
					
					} else {
						return false;
					}
				} else {		
					return false;
				}
			}
		
		}
	
	}
	
}

// --------------------------------------------------

/**
 * User is Exists Function
 *
 * This functions looks wp_users table with given user_ID
 * If it finds a record function returns true
 *
 * @param integer
 *
 * @return bool
 *
 */
function wp_ff_user_is_exists($user_ID) {
	global $wpdb;
	
	$user = $wpdb->get_row($wpdb->prepare("SELECT ID FROM $wpdb->users WHERE ID = %d",$user_ID));
	
	if( $user ) {
		return true;
	} else {
		return false;
	}
		
}

// --------------------------------------------------

/**
 * Get User Role Function
 *
 * This function gets user role from wp_usersmeta table 
 * with given user_ID value
 *
 * @param integer
 *
 * @return array
 *
 */
function wp_ff_get_user_role($user_ID) {

	global $wpdb;
	
	$meta_key = $wpdb->get_row($wpdb->prepare("SELECT umeta_id, user_id, meta_key FROM $wpdb->usermeta WHERE user_id = %d AND meta_key = 'wp_capabilities'",$user_ID));

	$usermeta = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->usermeta WHERE umeta_id = %d",$meta_key->umeta_id));
	
	return unserialize($usermeta->meta_value);
	
}

// --------------------------------------------------

/**
 * Add Comment to Blacklist Function
 *
 * This function adds comment to blacklist with given ff id
 * When adding comments to ff_comments table
 * if ff id is in the blacklist it will not be saved to ff_comments table
 * When listing comments
 * Comment will be retrieved from wp_comments table cause it has been saved there
 * Sorry for my bad english :) 
 *
 * @param integer
 *
 * @return bool
 *
 */
function wp_ff_add_comment_to_blacklist($ff_id) {
	
	global $wpdb;
	// echo $wpdb->prepare("INSERT INTO ". BLACKLIST_TABLE_NAME ." (ff_id) VALUES (%s)",$ff_id);
	$wpdb->query($wpdb->prepare("INSERT INTO ". BLACKLIST_TABLE_NAME ." (ff_id) VALUES (%s)",$ff_id));
	$wpdb->query($wpdb->prepare("DELETE FROM ". COMMENTS_TABLE_NAME ." WHERE comment_ID = %s",$ff_id));
	return true;
}

// --------------------------------------------------

/**
 * This function looks at ff_comments_blacklist table
 * with given ff_id value. If it finds record at table
 * it returns true, else it returns false
 *
 * @param integer $ff_id
 * @return bool
 */
function wp_ff_is_comment_in_blacklist($ff_id) {
	
	global $wpdb;
	$ff_comment = $wpdb->get_row($wpdb->prepare("SELECT * FROM ". BLACKLIST_TABLE_NAME ." WHERE ff_id = %s",$ff_id));
	
	if(!$ff_comment) {
		return false;
	} else {
		return true;
	}
	
	
}



// if (isset($_GET['activate']) && $_GET['activate'] == 'true') {
    // add_action('init', 'friendfeed_comments_install'); 
// } 

?>