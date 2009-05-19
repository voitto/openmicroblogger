<?php
/*
Plugin Name: Post to FriendFeed
Plugin Script: post2ff.php
Plugin URI: http://sudarmuthu.com/wordpress/post-to-friendfeed
Description: Publish an entry to FriendFeed of your blog post with images and excerpt.
Version: 0.5
License: GPL
Author: Sudar
Author URI: http://sudarmuthu.com/ 

=== RELEASE NOTES ===
2008-08-03 - v0.1 - first version
2008-08-03 - v0.2 - Fixed compatibility problem with FriendFeed Comments Plugin
2008-08-03 - v0.3 - Removed smilies from the list of images
2008-08-09 - v0.4 - Added an option to specify the number of images to be posted
2008-08-13 - v0.5 - Added support for Scheduled posts
*/

define('SMWPFFDIR', dirname(__FILE__) . '/');                
define('SMWPFFINC', SMWPFFDIR . 'friendfeed-api/');   

$smwpff_message = "";

function smwpff_publish2ff($post_id) {
	$post = &get_post($post_id);
		
    if(! class_exists('SimplePie')) {	
		require_once(SMWPFFINC . "friendfeed.php");
    }
    
    $friendfeed = new SM_FriendFeed(get_option('smwpff_nickname'), get_option("smwpff_key"));
    if (get_option("smwpff_import_comments") == "Yes") {
    	$entry = $friendfeed->publish_link($post->post_title, get_permalink($post->ID), $post->post_excerpt, get_images($post->post_content));
    } else {
    	$entry = $friendfeed->publish_link($post->post_title, get_permalink($post->ID), null, get_images($post->post_content)); 
    }
}

function get_images($post_content) {

	$pattern = "/(<img[^>]*>)[^>]*?>/i";
	$srcpattern = "/src=[\"']?([^\"']?.*(png|jpg|gif))[\"']?/i";
	preg_match_all($pattern, $post_content,$matches);
	
	$images_arr = array();

	for ($i = 0; $i < count($matches[1]); $i++ ) {
		$len = strlen($matches[1][$i]);
       	preg_match_all($srcpattern, $matches[1][$i],$pathmatches);
       	if (strpos($pathmatches[1][0], "/smilies") === false) {
       		array_push($images_arr, $pathmatches[1][0]);       		
       	}
	}
	
	if (get_option('smwpff_num_images')) {
		$num_images = get_option('smwpff_num_images');
	} else {
		$num_images = 3;
	}
	$images_arr = array_slice($images_arr, 0, $num_images);	
	return $images_arr;
}

if (!function_exists('smwpff_request_handler')) {
    function smwpff_request_handler() {
        global $smwpff_message;
        
        if (isset($_POST["smwpff_options_submit"]) && $_POST['smwpff_options_submit'] == "Update Options") {
			update_option("smwpff_import_comments", $_POST['smwpff_comments']);
			update_option("smwpff_nickname", $_POST['smwpff_nickname']);
			update_option("smwpff_key", $_POST['smwpff_key']);
			update_option("smwpff_num_images", $_POST['smwpff_num_images']);
        }
        
        $smwpff_message = '<br clear="all" /> <div id="message" class="updated fade"><p><strong>Setting saved. </strong></p></div>';
    }
}


function smwpff_display_options() {
	global $smwpff_message;
	echo $smwpff_message;
        
    $nickname = get_option('smwpff_nickname');
    $key = get_option('smwpff_key');
    $import_comments = get_option('smwpff_import_comments');
    $num_images = get_option('smwpff_num_images');
    
	print('<div class="wrap">');
	print('<h2>Post2FF Options</h2>');
    print ('<form action="'. get_bloginfo("wpurl") . '/wp-admin/options-general.php?page=post2ff.php' .'" method="post">');		
?>
	<table border="0" class="form-table">
    <tbody>
    <tr valign="top"> 
    	<th><label for="smwpff_nickname">FriendFeed Nickname</label></th>
    	<td>
    		<input type="text" value="<?php echo $nickname; ?>" name="smwpff_nickname" id="smwpff_nickname" />
    	</td>
	</tr>
    
    <tr valign="top"> 
    	<th><label for="smwpff_key">Remote Key</label></th>
    	<td>
    		<input type="text" value="<?php echo $key; ?>" name="smwpff_key" id="smwpff_key" />[ <a href="http://friendfeed.com/remotekey"  target="_blank">find your key</a> ]
    	</td>
	</tr>

    <tr valign="top"> 
    	<th><label for="smwpff_num_images">Number of images to import</label></th>
    	<td>
    		<input type="text" value="<?php echo $num_images; ?>" name="smwpff_num_images" id="smwpff_num_images" />
    	</td>
	</tr>
	
    <tr valign="top"> 
    	<th><label for="smwpff_comments">Import excerpt as first comment</label></th>
    	<td>
    		<input type="radio" value="Yes" name="smwpff_comments" <?php ($import_comments == "Yes") ? print "checked" : print "" ?> /> Yes
    		<input type="radio" value="No" name="smwpff_comments" <?php ($import_comments == "No") ? print "checked" : print "" ?> /> No
    	</td>
	</tr>

	<tr>
        <td colspan="2">
			<input type="submit" name="smwpff_options_submit" value="Update Options">
		</td>			
	</tr>			
	
	</tbody>
	</table>
	</form>
<?php
}

if (!function_exists('smwpff_install')) {
	function smwpff_install () {
	
      add_option("smwpff_import_comments", "Yes");
      add_option("smwpff_nickname", "");
      add_option("smwpff_key", "");
      add_option("smwpff_num_images", "3");
	      
	}
}


if(!function_exists('smwpff_add_menu')) {
	function smwpff_add_menu() {
	    //Add a submenu to Options
        add_options_page("Post2FF", "Post2FF", 8, basename(__FILE__), "smwpff_display_options");	    
	}
}

register_activation_hook(__FILE__,'smwpff_install');
add_action('admin_menu', 'smwpff_add_menu');
add_action('init', 'smwpff_request_handler');
add_action('draft_to_publish', 'smwpff_publish2ff');
add_action('future_to_publish', 'smwpff_publish2ff');
?>