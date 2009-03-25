<?php
add_filter( 'the_content', 'make_clickable' );

add_action( 'wp_ajax_prologue_ajax_tag_search', 'prologue_ajax_tag_search' ); //Tag suggestion
add_action( 'wp_ajax_prologue_load_post', 'prologue_load_post' ); // Load posts for inline editing
add_action( 'wp_ajax_prologue_load_comment', 'prologue_load_comment' ); // Load comments for inline editing
add_action( 'wp_ajax_prologue_inline_save', 'prologue_inline_save' ); // Save post  after inline editing
add_action( 'wp_ajax_prologue_inline_comment_save', 'prologue_inline_comment_save' ); // Save comment   after inline editing
add_action( 'wp_ajax_prologue_latest_posts', 'prologue_latest_posts' ); // Load new posts 
add_action( 'wp_ajax_prologue_latest_comments', 'prologue_latest_comments' ); //check for new comments and loads comments into widget
add_action( 'wp_ajax_prologue_new_post', 'prologue_new_post' ); //Ajax posting
add_action( 'wp_ajax_prologue_new_comment', 'prologue_new_comment' ); //Ajax Commenting
add_action( 'wp_head', 'prologue_widget_recent_comments_avatar_style'); //Load styles for recent comments avatar widget


if (!is_admin())  add_action( 'wp_print_scripts', 'prologue_javascript' );


function prologue_javascript() {
	$prologue_updates = true; //($prologue_options['updates_enabled']=="yes") ? true : false ;
	$prologue_comments_updates = true; //($prologue_options['comments_updates_enabled']=="yes") ? true : false ;
	$prologue_tagsuggest = true; //($prologue_options['tagsuggest_enabled']=="yes") ? true : false ;
	$prologue_inlineedit = true; //($prologue_options['inlineedit_enabled']=="yes") ? true : false ;
	$prologue_comments_inlineedit = true; //($prologue_options['inlineedit_comments_enabled']=="yes") ? true : false ;

	wp_enqueue_script( 'jquery-color' );
	wp_enqueue_script( 'comment-reply' );
	//  Not working? $pr_modtime = filemtime( './inc/p2.js' );
	wp_enqueue_script( 'p2js', get_bloginfo('template_directory' ).'/inc/p2.js?022509-1', array( 'jquery' ) );
	
	if (($prologue_inlineedit || $prologue_comments_inlineedit) && is_user_logged_in()) 
		wp_enqueue_script( 'jeditable', get_bloginfo('template_directory').'/inc/jquery.jeditable.js', array( 'jquery' )  ); 
	
	wp_enqueue_script( 'scrollit', get_bloginfo('template_directory').'/inc/jquery.scrollTo-min.js', array( 'jquery' )  ); 
		
		
	if (is_front_page() && $prologue_tagsuggest && is_user_logged_in()) 
	if (is_front_page() && $prologue_tagsuggest && is_user_logged_in()) 
		wp_enqueue_script( 'suggest'); 		
}
function prologue_pageoptions_init() {
	global $page_options;
	#get_currentuserinfo();
	$page_options['nonce']= wp_create_nonce( 'ajaxnonce' );
	$page_options['prologue_updates'] = true; //($prologue_options['updates_enabled']=="yes") ? '1' : '0' ;
	$page_options['prologue_comments_updates'] = 1; //($prologue_options['comments_updates_enabled']=="yes") ? '1' : '0' ;
	$page_options['prologue_tagsuggest'] = 1; //($prologue_options['tagsuggest_enabled']=="yes") ? '1' : '0' ;
	$page_options['prologue_inlineedit'] = 1; //($prologue_options['inlineedit_enabled']=="yes") ? '1' : '0' ;
	$page_options['prologue_comments_inlineedit'] = 1; //($prologue_options['inlineedit_comments_enabled']=="yes") ? '1' : '0' ;
	$page_options['is_single'] = ( is_single() )? '1' : '0';
	$page_options['is_front_page'] = ( is_front_page() ) ? '1' : '0';
	$page_options['is_first_front_page'] = ( is_front_page() && !is_paged() ) ? '1' : '0';
	$page_options['is_user_logged_in'] = ( is_user_logged_in() ) ? '1' : '0';
}
add_action('wp_head', 'prologue_pageoptions_init');

function prologue_pageoptions_js() { ?>
	<script type='text/javascript'>
// <![CDATA[
//Prologue Configuration
<?php global $page_options; ?>
var ajaxUrl = "<?php  echo get_bloginfo( 'wpurl' )?>/wp-admin/admin-ajax.php";
var updateRate = "30000";
var nonce = "<?php echo $page_options['nonce'] ?>";
var templateDir  = "<?php bloginfo('template_directory'); ?>";
var isFirstFrontPage = <?php echo $page_options['is_first_front_page'] ?>;
var isFrontPage = <?php echo $page_options['is_front_page'] ?>;
var isSingle = <?php echo $page_options['is_single'] ?>;
var isUserLoggedIn = <?php echo $page_options['is_user_logged_in'] ?>;
var prologueTagsuggest = <?php echo $page_options['prologue_tagsuggest'] ?>;
var prologuePostsUpdates = <?php echo $page_options['prologue_updates'] ?>;
var prologueCommentsUpdates = <?php echo $page_options['prologue_comments_updates'] ?>;
var getPostsUpdate = 0;
var getCommentsUpdate = 0;
var inlineEditPosts =  <?php echo $page_options['prologue_inlineedit'] ?>;
var inlineEditComments =  <?php echo $page_options['prologue_comments_inlineedit'] ?>;
var wpUrl = "<?php  echo get_bloginfo( 'wpurl' )?>";
var rssUrl = "<?php bloginfo( 'rss_url' ); ?>";
var pageLoadTime="<?php echo gmdate( 'Y-m-d H:i:s' ); ?>";
var latestPermalink="<?php echo( latest_post_permalink() ) ?>";
var original_title = document.title;
var commentsOnPost = new Array;
var postsOnPage= new Array;
var postsOnPageQS ='';
var currPost=-1;
var currComment = -1;
var commentLoop = false;
var lcwidget = false;
var hidecomments = false;
var commentsLists ='';
var newUnseenUpdates = 0;
 // ]]>
</script> <?php
}
add_action('wp_head', 'prologue_pageoptions_js');

function get_recent_post_ids( $return_as_string = true ) {
	global $wpdb;

	$recent_ids =  (array) $wpdb->get_results( "
		SELECT MAX(ID) AS post_id
		FROM {$wpdb->posts}
		WHERE post_type = 'post'
		  AND post_status = 'publish'      
		GROUP BY post_author
		ORDER BY post_date_gmt DESC
	", ARRAY_A );

	if( $return_as_string === true ) {
		$ids_string = '';
		foreach( $recent_ids as $post_id ) {
			$ids_string .= "{$post_id['post_id']}, ";
		}

		// Remove trailing comma
		$ids_string = substr( $ids_string, 0, -2 );

		return $ids_string;
	}

	$ids = array( );
	foreach( $recent_ids as $post_id ) {
		$ids[] = $post_id['post_id'];
	}

	return $ids;
}

function prologue_recent_projects_widget( $args ) {
	extract( $args );
	$options = get_option( 'prologue_recent_projects' );

	$title = empty( $options['title'] ) ? __( 'Recent Tags' ) : $options['title'];
	$num_to_show = empty( $options['num_to_show'] ) ? 35 : $options['num_to_show'];

	$num_to_show = (int) $num_to_show;

	$before = $before_widget;
	$before .= $before_title . $title . $after_title;

	$after = $after_widget;

	echo prologue_recent_projects( $num_to_show, $before, $after );
}

function prologue_recent_projects( $num_to_show = 35, $before = '', $after = '' ) {
	$cache = wp_cache_get( 'prologue_theme_tag_list', '' );
	if( !empty( $cache[$num_to_show] ) ) {
		$recent_tags = $cache[$num_to_show];
	}
	else {
		$all_tags = (array) get_tags( array( 'get' => 'all' ) );

		$recent_tags = array( );
		foreach( $all_tags as $tag ) {
			if( $tag->count < 1 )
				continue;

			$tag_posts = get_objects_in_term( $tag->term_id, 'post_tag' );
			$recent_post_id = max( $tag_posts );
			$recent_tags[$tag->term_id] = $recent_post_id;
		}

		arsort( $recent_tags );

		$num_tags = count( $recent_tags );
		if( $num_tags > $num_to_show ) {
			$reduce_by = (int) $num_tags - $num_to_show;

			for( $i = 0; $i < $reduce_by; $i++ ) {
				array_pop( $recent_tags );
			}
		}

		wp_cache_set( 'prologue_theme_tag_list', array( $num_to_show => $recent_tags ) );
	}

	echo $before;
	echo "<ul>\n";

	foreach( $recent_tags as $term_id => $post_id ) {
		$tag = get_term( $term_id, 'post_tag' );
		$tag_link = get_tag_link( $tag->term_id );
?>

<li>
<a class="rss" href="<?php echo get_tag_feed_link( $tag->term_id ); ?>">RSS</a>&nbsp;<a href="<?php echo $tag_link; ?>"><?php echo $tag->name; ?></a>&nbsp;(&nbsp;<?php echo $tag->count; ?>&nbsp;)
</li>

<?php
    } // foreach get_tags
?>

	</ul>

<p><a class="allrss" href="<?php bloginfo( 'rss2_url' ); ?>">All Updates RSS</a></p>

<?php
	echo $after;
}

function prologue_flush_tag_cache( ) {
	wp_cache_delete( 'prologue_theme_tag_list' );
}
add_action( 'save_post', 'prologue_flush_tag_cache' );

function prologue_recent_projects_control( ) {
	$options = $newoptions = get_option( 'prologue_recent_projects' );

	if( $_POST['prologue_submit'] ) {
		$newoptions['title'] = strip_tags( stripslashes( $_POST['prologue_title'] ) );
		$newoptions['num_to_show'] = strip_tags( stripslashes( $_POST['prologue_num_to_show'] ) );
	}

	if( $options != $newoptions ) {
		$options = $newoptions;
		update_option( 'prologue_recent_projects', $options );
	}

	$title = attribute_escape( $options['title'] );
	$num_to_show = $options['num_to_show'];
?>

<input type="hidden" name="prologue_submit" id="prologue_submit" value="1" />

<p><label for="prologue_title"><?php _e('Title:') ?> 
<input type="text" class="widefat" id="prologue_title" name="prologue_title" value="<?php echo $title ?>" />
</label></p>

<p><label for="prologue_num_to_show"><?php _e('Num of tags to show:') ?> 
<input type="text" class="widefat" id="prologue_num_to_show" name="prologue_num_to_show" value="<?php echo $num_to_show ?>" />
</label></p>

<?php
}
wp_register_sidebar_widget( 'prologue_recent_projects_widget', __( 'Recent Tags' ), 'prologue_recent_projects_widget' );
wp_register_widget_control( 'prologue_recent_projects_widget', __( 'Recent Tags' ), 'prologue_recent_projects_control' );


if( function_exists('register_sidebar') )
	register_sidebar();


function prologue_get_avatar( $wpcom_user_id, $email, $size, $rating = '', $default = 'http://s.wordpress.com/i/mu.gif' ) {
	if( !empty( $wpcom_user_id ) && $wpcom_user_id !== false && function_exists( 'get_avatar' ) ) {
		return get_avatar( $wpcom_user_id, $size );
	}
	elseif( !empty( $email ) && $email !== false ) {
		$default = urlencode( $default );

		$out = 'http://www.gravatar.com/avatar.php?gravatar_id=';
		$out .= md5( $email );
		$out .= "&amp;size={$size}";
		$out .= "&amp;default={$default}";

		if( !empty( $rating ) ) {
			$out .= "&amp;rating={$rating}";
		}

		return "<img alt='' src='{$out}' class='avatar avatar-{$size}' height='{$size}' width='{$size}' />";
	}
	else {
		return "<img alt='' src='{$default}' />";
	}
}

function prologue_comment($comment, $args, $depth) {
	$GLOBALS['comment'] = $comment;
?>
<li <?php comment_class(); ?> id="comment-<?php comment_ID( ); ?>">
	<?php echo prologue_get_avatar( $comment->user_id, $comment->comment_author_email, 32 ); ?>
	<h4>
		<?php comment_author_link( ); ?>
		<span class="meta"><?php comment_time( ); ?> on <?php comment_date( ); ?> <span class="actions"><a href="#comment-<?php comment_ID( ); ?>">Permalink</a><?php echo comment_reply_link(array('depth' => $depth, 'max_depth' => $args['max_depth'], 'before' => ' | ')) ?><?php edit_comment_link( __( 'Edit' ), ' | ',''); ?></span><br /></span>
	</h4>
		<div class="commentcontent<?php if (current_user_can('edit_post', $comment->comment_post_ID)) echo(' comment-edit') ?>"  id="commentcontent-<?php comment_ID( ); ?>">
				<?php comment_text( ); ?>
		<?php if ($comment->comment_approved == '0') : ?>
		<p><em><?php _e('Your comment is awaiting moderation.') ?></em></p>
		<?php endif; ?>
		</div>
<?php	
}

function prologue_comment_widget_html($comment, $size, $tdclass, $echocomment=true ) {
	$avatar = get_avatar( $comment, $size );
		if( $comment->comment_author_url )
			$avatar = "<a href='{$comment->comment_author_url}' rel='nofollow'>{$avatar}</a>";

		$thiscomment  = '<tr><td title="' . $comment->comment_author . '" class="recentcommentsavatar' . $tdclass . '" style="height:' . $size . 'px; width:' . $size . 'px">' . $avatar . '</td>';
		$thiscomment .= '<td class="recentcommentstext' . $tdclass . '">';
		if( $comment->comment_author == '' )
			$comment->comment_author = 'Anonymous';
		$author = $comment->comment_author;
		$excerpt = wp_html_excerpt($author, 20);
		if ( $author != $excerpt )
			$author = $excerpt.'&hellip;';
		if( $comment->comment_author_url == '' ) {
			$authorlink = $author;
		} else {
			$authorlink = "<a href='{$comment->comment_author_url}' rel='nofollow'>" . $author . "</a>";
		}
		$post_title = get_the_title( $comment->comment_post_ID );
		$excerpt = wp_html_excerpt( $post_title, 30 );
		if( $post_title != $excerpt )
			$post_title = $excerpt.'&hellip;';
		$comment_content = $comment->comment_content;
		$excerpt = attribute_escape(wp_html_excerpt( $comment_content, 50 ));
		if( $comment_content != $excerpt )
			$comment_content = $excerpt.'&hellip;';
		$thiscomment .= sprintf( __( "%s on <a href='%s' class='tooltip' title='%s'>%s</a>" ) . '</td></tr>', $authorlink, get_permalink($comment->comment_post_ID) . '#comment-' . $comment->comment_ID, $comment_content, $post_title );
		$tdclass = 'end';
		if ($echocomment)
			echo $thiscomment;
		else
		return $thiscomment;
}

function prologue_comment_frontpage($comment, $args, $echocomment = true) {
	$GLOBALS['comment'] = $comment;
	$depth = prologue_get_comment_depth(get_comment_ID());
	$comment_text =  apply_filters('comment_text', $comment->comment_content);
	$thiscomment = 	'<li '. comment_class($class = '', $comment_id = null, $post_id = null, $echo = false ) .' id="comment-'. get_comment_ID() .'">'
					. prologue_get_avatar( $comment->user_id, $comment->comment_author_email, 32 ) .
					'<h4>
					'. get_comment_author_link() .
					' <span class="meta">'. get_comment_time() .' <em>on</em> '. get_comment_date( ) .'<span class="actions"><a href="'. get_permalink() .'#comment-'. get_comment_ID() .'">Permalink</a>'. prologue_get_comment_reply_link(array('depth' => $depth, 'max_depth' => $args['max_depth'], 'before' => ' | ', 'reply_text' => 'Reply'), $comment->comment_ID, $comment->comment_post_ID);
					if (current_user_can('edit_post', $comment->comment_post_ID)) 
						$thiscomment.=' | <a class="comment-edit-link" href="' . get_edit_comment_link( $comment->comment_ID ) . '" title="' . __( 'Edit comment' ) . '">Edit</a>';
					$thiscomment.='</span><br /></span></h4>';
	
					$thiscomment.='<div class="commentcontent';
					if (current_user_can('edit_post', $comment->comment_post_ID)) 
						$thiscomment.=' comment-edit';
					$thiscomment.='" id="commentcontent-'. get_comment_ID() .'">'. $comment_text;
					if ($comment->comment_approved == '0') 
						$thiscomment.='<p><em>'. _('Your comment is awaiting moderation.') .'</em></p>';
					$thiscomment.='</div>';

	if ($echocomment)
		echo $thiscomment;
	else
		return $thiscomment;
}

function tags_with_count($format = 'list', $before = '', $sep = '', $after = '') {
	global $post;
	$posttags = get_the_tags($post->ID, 'post_tag');
	
	if ( !$posttags )
		return;	
	
	foreach ( $posttags as $tag ) {
		if ( $tag->count > 1 && !is_tag($tag->slug) ) {
			$tag_link = '<a href="' . get_term_link($tag, 'post_tag') . '" rel="tag">' . $tag->name . ' (' . $tag->count . ')</a>';
		} else {
			$tag_link = $tag->name;
		}
		
		if ( $format == 'list' )
			$tag_link = '<li>' . $tag_link . '</li>';
		
		$tag_links[] = $tag_link;
	}
	
	echo $before . join( $sep, $tag_links ) . $after;
}

function latest_post_permalink()
{
	global $wpdb;
	$sql = "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' ORDER BY post_date DESC LIMIT 1";
	$last_post_id = $wpdb->get_var($sql);
	$permalink = get_permalink($last_post_id);
	return $permalink;
}


function prologue_ajax_tag_search() {
	global $wpdb;
	$s = $_GET['q']; // is this slashed already?
	if ( false !== strpos( $s, ',' ) ) {
		$s = explode( ',', $s );
		$s = $s[count( $s ) - 1];
	}
	$s = trim( $s );
	if ( strlen( $s ) < 2 )
		die; // require 2 chars for matching
	$results = $wpdb->get_col( "SELECT t.name FROM $wpdb->term_taxonomy AS tt INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id WHERE tt.taxonomy = 'post_tag' AND t.name LIKE ('%". $s . "%')" );
	echo join( $results, "\n" );
	exit;
}
	
function prologue_inline_save() {	
	check_ajax_referer( 'ajaxnonce', '_inline_edit' );
	if ( !is_user_logged_in() ) {
		echo('<p>Sorry - error: Not logged in</p>');
		exit;
		}

	$post_id	= $_POST['post_ID'];
	$post_id=substr($post_id, (strpos($post_id, '-')+1));
	if ( !current_user_can( 'edit_post', $post_id )) {
		echo('<p>Sorry - error: Not allowed to edit post</p>');
		exit;
	}
	
	$user_id = $current_user->ID;
	$post_content	= $_POST['content'];
	$char_limit = 40;
	$post_title	= strip_tags( $post_content );
	if( strlen( $post_title ) > $char_limit ) {
		$post_title = substr( $post_title, 0, $char_limit ) . ' ... ';
	}
	//Try to detect image or video only posts, and set post title accordingly
	$post_title = trim($post_title);
	if ($post_title=='') {
		if (preg_match("/<object|<embed/", $post_content))
			$post_title='Video Post';
		elseif (preg_match("/<img/", $post_content))
			$post_title='Image Post';
		else
			$post_title='No Title';
		}
	$post = wp_update_post( array(
		'post_title'	=> $post_title,
		'post_content'	=> $post_content,
		'post_modified'	=> current_time('mysql'),
		'post_modified_gmt'	=> current_time('mysql', 1),
		ID=>$post_id
	));
	
	$thepost = get_post($post);

	echo apply_filters('the_content',$thepost->post_content);
	
	exit;
}

function prologue_inline_comment_save() {	
	check_ajax_referer( 'ajaxnonce', '_inline_edit' );
	if ( !is_user_logged_in() ) {
		echo('<p>Sorry - error: Not logged in</p>');
		exit;
		}

	$comment_id	= $_POST['comment_ID'];
	$comment_id = substr($comment_id, (strpos($comment_id, '-')+1));
	$this_comment=get_comment($comment_id);
	if ( !(current_user_can('edit_post', $this_comment->comment_post_ID))) {
		echo('<p>Sorry - error: Not allowed to edit this comment</p>');
		exit;
	}
	
	$user_id = $current_user->ID;
	$comment_content = $_POST['comment_content'];
	$char_limit = 40;
	
	$comment = wp_update_comment( array(
		'comment_content'	=> $comment_content,
		'comment_ID' => $comment_id
	));
	
	$thecomment = get_comment($comment_id);
	echo $thecomment->comment_content;
	exit;
}



function prologue_load_post() {	
	check_ajax_referer( 'ajaxnonce', '_inline_edit' );
	if ( !is_user_logged_in() ) {
		echo('<p>Sorry - error: Not logged in</p>');
		exit;
		}
	$post_id=$_GET['post_ID'];
	$post_id=substr($post_id, (strpos($post_id, '-')+1));
	if ( !current_user_can( 'edit_post', $post_id )) {
		echo('<p>Sorry - error: Not allowed to edit post</p>');
		exit;
	}
	$this_post=get_post($post_id);
	$post_content=$this_post->post_content;
	echo stripslashes($post_content);
	exit;
}

function prologue_load_comment() {	
	check_ajax_referer( 'ajaxnonce', '_inline_edit' );
	if ( !is_user_logged_in() ) {
		echo('<p>Sorry - error: Not logged in</p>');
		exit;
		}
	$comment_id=$_GET['comment_ID'];
	$comment_id=substr($comment_id, (strpos($comment_id, '-')+1));
	$this_comment=get_comment($comment_id);
	$comment_content=$this_comment->comment_content;
	echo stripslashes($comment_content);
	exit;
}



function prologue_new_post() {
	if( 'POST' == $_SERVER['REQUEST_METHOD'] && !empty( $_POST['action'] ) && $_POST['action'] == 'prologue_new_post' ) {
		if ( !is_user_logged_in() ) {
			echo('<p>Sorry - error: Not logged in</p>');
			exit;
			}
		if( !current_user_can( 'publish_posts' ) ) {
			echo('<p>Sorry - error: Not allowed to post</p>');
			exit;
		}
	check_ajax_referer( 'ajaxnonce', '_ajax_post' );
		$user_id		= $current_user->user_id;
		$post_content	= $_POST['posttext'];
		$tags			= attribute_escape($_POST['tags']);
		if($tags == __('Tag it')) $tags = '';
		
		$char_limit		= 40;
		$post_title		= strip_tags( $post_content );
		if( strlen( $post_title ) > $char_limit ) {
			$post_title = substr( $post_title, 0, $char_limit ) . ' ... ';
		}
		//Try to detect image or video only posts, and set post title accordingly
		if ($post_title=='') {
			if (preg_match("/<object|<embed/", $post_content))
				$post_title='Video Post';
			elseif (preg_match("/<img/", $post_content))
				$post_title='Image Post';
			else
				$post_title='No Title';
			}
		$post_id = wp_insert_post( array(
			'post_author'	=> $user_id,
			'post_title'	=> $post_title,
			'post_content'	=> $post_content,
			'tags_input'	=> $tags,
			'post_status'	=> 'publish'
		) );
		if ($post_id)
				echo("$post_id");
			else 
				echo("0");
	}
	exit;
}

function prologue_new_comment() {
	if( 'POST' == $_SERVER['REQUEST_METHOD'] && !empty( $_POST['action'] ) && $_POST['action'] == 'prologue_new_comment' ) {
		
		check_ajax_referer( 'ajaxnonce', '_ajax_post' );
		
		$comment_content      = ( isset($_POST['comment']) ) ? trim($_POST['comment']) : null;
		$comment_post_ID	= ( isset($_POST['comment_post_ID']) ) ? trim($_POST['comment_post_ID']) : null;   
		// If the user is logged in
		$user = wp_get_current_user();
		if ( $user->ID ) {
			if ( empty( $user->display_name ) )
				$user->display_name=$user->user_login;
			$comment_author       = $user->display_name;
			$comment_author_email = $user->user_email;
			$comment_author_url   = $user->user_url;
			$comment_author_url   = $user->user_url;
			$user_ID 			  = $user->ID;
			if ( current_user_can('unfiltered_html') ) {
				if ( wp_create_nonce('unfiltered-html-comment_' . $comment_post_ID) != $_POST['_wp_unfiltered_html_comment'] ) {
					kses_remove_filters(); // start with a clean slate
					kses_init_filters(); // set up the filters
					}
			}
		} else {
			if ( get_option('comment_registration') ) {
				echo('Error: '. __('Sorry, you must be logged in to post a comment.') );
				exit;
				}
		}

		$comment_type = '';

		if ( get_option('require_name_email') && !$user->ID ) {
			if ( 6 > strlen($comment_author_email) || '' == $comment_author ) {
				echo('Error: '. __('Error: please fill the required fields (name, email).') );
				exit;
				}
			elseif ( !is_email($comment_author_email)) {
				echo('Error: '. __('Error: please enter a valid email address.') );
				exit;
			}
		}

		if ( '' == $comment_content) {
			echo('Error: '. __('please type a comment.') );
			exit;
			}
		
		$comment_parent = isset($_POST['comment_parent']) ? absint($_POST['comment_parent']) : 0;

		$commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_ID');

		$comment_id = wp_new_comment( $commentdata );
		$comment = get_comment($comment_id);
		if ( !$user->ID ) {
			setcookie('comment_author_' . COOKIEHASH, $comment->comment_author, time() + 30000000, COOKIEPATH, COOKIE_DOMAIN);
			setcookie('comment_author_email_' . COOKIEHASH, $comment->comment_author_email, time() + 30000000, COOKIEPATH, COOKIE_DOMAIN);
			setcookie('comment_author_url_' . COOKIEHASH, clean_url($comment->comment_author_url), time() + 30000000, COOKIEPATH, COOKIE_DOMAIN);
		}		
		if ($comment)
			echo($comment_id);
		else 
			echo("'Error: '.Unknown error occured. Comment not posted.");
	}
exit;
}

	
function prologue_latest_comments() {
	
	global $wpdb, $comments, $comment, $max_depth, $depth, $user_login, $user_ID, $user_identity;
	
	$number = 10; //max amount of comments to load
	$load_time=$_GET['load_time'];
	$lc_widget=$_GET['lcwidget'];
	$visible_posts =  $_GET['vp'];
	
	if ( get_option('thread_comments') )
		$max_depth = get_option('thread_comments_depth');
	else
		$max_depth = -1;

	//Widget info
	if ( !isset($options) )
		$options = get_option('widget_recent_comments');			
	$size = $options[ 'avatar_size' ] ? $options[ 'avatar_size' ] : 32;
	
	//get new comments
	if ($user_ID) {
		$comments = $wpdb->get_results("SELECT * FROM $wpdb->comments WHERE (comment_approved = '1' OR ( user_id = '$user_ID' AND comment_approved = '0' ))  AND comment_date_gmt > '$load_time' ORDER BY comment_date_gmt DESC LIMIT $number");
	} else if ( empty($comment_author) ) {
		$comments = $wpdb->get_results("SELECT * FROM $wpdb->comments WHERE comment_approved = '1' AND comment_date_gmt > '$load_time' ORDER BY comment_date_gmt DESC LIMIT $number");
	}
	else {
		$comments = $wpdb->get_results("SELECT * FROM $wpdb->comments WHERE (comment_approved = '1' OR ( comment_author = '$comment_author' AND comment_author_email = '$comment_author_email' AND comment_approved = '0' ) ) AND comment_date_gmt > '$load_time' ORDER BY comment_date_gmt DESC LIMIT $number");		
	}
	$number_of_new_comments = count($comments);
	$i=0;
	if ($number_of_new_comments > 0) {
		foreach ($comments as $comment) {

			//Setup comment html if post is visible
			$comment_html = '';
			if  (in_array($comment->comment_post_ID, $visible_posts)) {
				$comment_html = prologue_comment_frontpage($comment, array('max_depth' => $max_depth, 'before' => ' | ', 'reply_text' => 'r'), $depth, false);
			}

			//Setup widget html if widget is visible
			$comment_widget_html = '';
			if ($lc_widget) {
				$comment_widget_html = prologue_comment_widget_html($comment, $size, 'top', false);
			}
			
			$prepare_comments[$i] = array("id"=>$comment->comment_ID, "postID" => $comment->comment_post_ID, "commentParent" =>  $comment->comment_parent, "html" => $comment_html, "widgetHtml" => $comment_widget_html);	
		$i++;
		}
		
		$json_data = array("numberofnewcomments" =>$number_of_new_comments, "comments" => $prepare_comments, "lastcommenttime" => gmdate( 'Y-m-d H:i:s' ));
		echo (json_encode($json_data));
	}	
	else { // No new comments
	 echo('0');
	}

	exit;
}	


function prologue_latest_posts() {	
	$load_time=$_GET['load_time'];
	$frontpage=$_GET['frontpage'];
	$num_posts = 10; //max amount of posts to load
	$number_of_new_posts = 0;
	$prologue_query = new WP_Query('showposts=' . $num_posts . '&post_status=publish');
	ob_start();
	while ($prologue_query->have_posts()) : $prologue_query->the_post();
		if (get_gmt_from_date(get_the_time( 'Y-m-d H:i:s' )) <=  $load_time) continue;
		$number_of_new_posts++;
		if ($frontpage) {
	?>
<li id="prologue-<?php the_ID(); ?>" class="newupdates user_id_<?php the_author_ID( ); ?>">


<?php
	$current_user_id = get_the_author_ID( );
	echo prologue_get_avatar( $current_user_id, get_the_author_email( ), 48 );
?>

<?php
}
endwhile;
$posts_html = ob_get_contents();
ob_end_clean();
if ($number_of_new_posts == 0) {
	echo 0;
	exit;
}
else {
	$json_data = array("numberofnewposts" =>$number_of_new_posts, "html" => $posts_html, "lastposttime" => gmdate( 'Y-m-d H:i:s' ));
	echo (json_encode($json_data));	
}
exit;
}




function prologue_next_comments_link($label='', $max_page = 0) {
	global $wp_query;
	global $post;
	$page = get_query_var('cpage');

	if ( !$page )
		$page = 1;

	$nextpage = intval($page) + 1;

	if ( empty($max_page) )
		$max_page = $wp_query->max_num_comment_pages;

	if ( empty($max_page) )
		$max_page = get_comment_pages_count();

	if ( $nextpage > $max_page )
		return;

	if ( empty($label) )
		$label = __('Newer Comments &raquo;');

	echo '<a href="' . clean_url( get_comments_pagenum_link( $nextpage, $max_page ) );
	$attr = apply_filters( 'next_comments_link_attributes', '' );
	echo "\" $attr rel=\"post-$post->ID cpage-$nextpage\">". preg_replace('/&([^#])(?![a-z]{1,8};)/', '&#038;$1', $label) .'</a>';
}



function prologue_previous_comments_link($label='') {

	$page = get_query_var('cpage');

	if ( !$page )
		$page = 1;

	if ( $page <= 1 )
		return;

	$prevpage = intval($page) - 1;

	if ( empty($label) )
		$label = __('&laquo; Older Comments');

	echo '<a href="' . clean_url(get_comments_pagenum_link($prevpage));
	$attr = apply_filters( 'previous_comments_link_attributes', '' );
	echo "\" $attr rel=\"post-$post->ID cpage-$prevpage\">". preg_replace('/&([^#])(?![a-z]{1,8};)/', '&#038;$1', $label) .'</a>';
}

/* Recent comments with avatars */
function prologue_widget_recent_comments_avatar($args) {
	global $wpdb, $comments, $comment;
	extract($args, EXTR_SKIP);
	if ( !isset($options) )
		$options = get_option('widget_recent_comments');
	$title = empty($options['title']) ? __('Recent Comments') : $options['title'];
	if ( !$number = (int) $options['number'] )
		$number = 5;
	else if ( $number < 1 )
		$number = 1;else if ( $number > 15 )
		$number = 15;

	if ( !$comments = wp_cache_get( 'recent_avatar_comments', 'widget' ) ) {
		$comments = $wpdb->get_results("SELECT comment_author, comment_author_url, comment_author_email, comment_ID, comment_post_ID, user_id, comment_content FROM $wpdb->comments WHERE comment_approved = '1' ORDER BY comment_date_gmt DESC LIMIT $number");
		wp_cache_add( 'recent_avatar_comments', $comments, 'widget' );
	}
	$size = $options[ 'avatar_size' ] ? $options[ 'avatar_size' ] : 24;
	?>
	<?php echo $before_widget; ?>
		<?php echo $before_title . $title . $after_title; ?>
		<table class='recentcommentsavatar' cellspacing='0' cellpadding='0' border='0' id="recentcommentstable"><?php
		$tdclass = 'top';
		if ( $comments ) : foreach ($comments as $comment) :
			prologue_comment_widget_html($comment, $size, $tdclass, true);
		endforeach; endif;?></table>
	<?php echo $after_widget; ?>
<?php
}

if(!function_exists('wp_delete_recent_comments_avatar_cache')) {
	function wp_delete_recent_comments_avatar_cache() {
		wp_cache_delete( 'recent_avatar_comments', 'widget' );
	}
	add_action( 'comment_post', 'wp_delete_recent_comments_avatar_cache' );
	add_action( 'wp_set_comment_status', 'wp_delete_recent_comments_avatar_cache' );
}

function prologue_widget_recent_comments_avatar_control() {
	$options = $newoptions = get_option('widget_recent_comments');
	if ( $_POST["recent-comments-submit"] ) {
		$newoptions['title'] = strip_tags(stripslashes($_POST["recent-comments-title"]));
		$newoptions['number'] = (int) $_POST["recent-comments-number"];
		$newoptions['avatar_size'] = (int) $_POST["recent-comments-avatar-size"];
		$newoptions['avatar_bg'] = preg_replace('/[^a-z0-9#]/', '', $_POST["recent-comments-avatar-bg"] );
		$newoptions['text_bg'] = preg_replace('/[^a-z0-9#]/i', '', $_POST["recent-comments-text-bg"] );
	}
	if ( print_r( $options, 1 ) != print_r( $newoptions, 1 ) ) {
		$options = $newoptions;
		update_option('widget_recent_comments', $options);
		wp_delete_recent_comments_cache(); // If user selects "No Avatars", the core recent comments widget is used, so we need to clear that cache too.
		wp_delete_recent_comments_avatar_cache();
	}
	$title = attribute_escape($options['title']);
	$avatar_bg = $options[ 'avatar_bg' ];
	$text_bg   = $options[ 'text_bg' ];
	$avatar_size = $options[ 'avatar_size' ] == '' ? '48' : $options[ 'avatar_size' ];
	if ( !$number = (int) $options['number'] )
		$number = 5;
	else if ( $number < 1 )
		$number = 1;
	else if ( $number > 15 )
		$number = 15;
?>
			<p><label for="recent-comments-title"><?php _e('Title:'); ?> <input id="recent-comments-title" name="recent-comments-title" type="text" class="widefat" value="<?php echo $title; ?>" /></label></p>
			<p><label for="recent-comments-number"><?php _e('Number of comments to show:'); ?> <input style="width: 25px; text-align: center;" id="recent-comments-number" name="recent-comments-number" type="text" value="<?php echo $number; ?>" /></label> <small><?php _e('(at most 15)'); ?></small></p>
			<p><label for="recent-comments-avatar-size"><?php _e('Avatar Size (px):'); ?> <select name='recent-comments-avatar-size'>
			<option value='1'<?php echo 1 == $avatar_size ? ' selected' : ''; ?>><?php _e( 'No Avatars' ); ?></option>
			<option value='16'<?php echo 16 == $avatar_size ? ' selected' : ''; ?>>16x16</option>
			<option value='32'<?php echo 32 == $avatar_size ? ' selected' : ''; ?>>32x32</option>
			<option value='48'<?php echo 48 == $avatar_size ? ' selected' : ''; ?>>48x48</option>
			<option value='96'<?php echo 96 == $avatar_size ? ' selected' : ''; ?>>96x96</option>
			<option value='128'<?php echo 128 == $avatar_size ? ' selected' : ''; ?>>128x128</option>
			</select></label></p>
			<p><label for="recent-comments-avatar-bg"><?php _e('Avatar background color:'); ?> <input style="width: 50px;" id="recent-comments-avatar-bg" name="recent-comments-avatar-bg" type="text" value="<?php echo $avatar_bg; ?>" /></label></p>
			<p><label for="recent-comments-text-bg"><?php _e('Text background color:'); ?> <input style="width: 50px;" id="recent-comments-text-bg" name="recent-comments-text-bg" type="text" value="<?php echo $text_bg; ?>" /></label></p>

			<input type="hidden" id="recent-comments-submit" name="recent-comments-submit" value="1" />
<?php
}

function prologue_widget_recent_comments_avatar_style() {
	$options = get_option('widget_recent_comments');
	$avatar_bg = $options[ 'avatar_bg' ] == '' ? '' : 'background: ' . $options[ 'avatar_bg' ] . ';';
	$text_bg = $options[ 'text_bg' ] == '' ? '' : 'background: ' . $options[ 'text_bg' ] . ';';
	$style = "
<style type='text/css'>
table.recentcommentsavatar img.avatar { border: 0px; margin:0; }
table.recentcommentsavatar a {border: 0px !important; background-color: transparent !important}
td.recentcommentsavatartop {padding:0px 0px 1px 0px;
							margin:   0px;
							{$avatar_bg} }
td.recentcommentsavatarend {padding:0px 0px 1px 0px;
							margin:0px;
							{$avatar_bg} }
td.recentcommentstexttop {
							{$text_bg} border: none !important; padding:0px 0px 0px 10px;}
td.recentcommentstextend {
							{$text_bg} border: none !important; padding:0px 0px 2px 10px;}
</style>";
	echo $style;
}

function prologue_widget_recent_comments_avatar_register() {
	$options = get_option('widget_recent_comments');
	if( isset( $options[ 'avatar_size' ] ) && $options[ 'avatar_size' ] == 1 && is_admin() == false )
		return;
	$class = array('classname' => 'widget_recent_comments');
	wp_register_sidebar_widget('recent-comments', __('Recent Comments'), 'prologue_widget_recent_comments_avatar', $class);
	wp_register_widget_control('recent-comments', __('Recent Comments'), 'prologue_widget_recent_comments_avatar_control' );

	if ( is_active_widget('prologue_widget_recent_comments_avatar') )
		add_action('wp_head', 'prologue_widget_recent_comments_avatar_style');
}
add_action('init', 'prologue_widget_recent_comments_avatar_register', 10);


//Search related Functions

function search_comments_distinct($distinct) {
	global $wp_query, $wpdb;
	if (!empty($wp_query->query_vars['s'])) {
		return 'DISTINCT';
	}
}

add_filter('posts_distinct', 'search_comments_distinct');

function search_comments_where($where) {
	global $wp_query, $wpdb;
		if (!empty($wp_query->query_vars['s'])) {
				$or = " OR ( comment_post_ID = ".$wpdb->posts . ".ID  AND comment_approved =  '1' AND comment_content LIKE '%" . $wpdb->escape($wp_query->query_vars['s']) . "%') ";
	  			$where = preg_replace("/\bor\b/i",$or." OR",$where,1);
		}
		return $where;
}
add_filter('posts_where', 'search_comments_where');

function search_comments_join($join) {
		global $wp_query, $wpdb, $request;

		if (!empty($wp_query->query_vars['s'])) {
			$join .= " LEFT JOIN $wpdb->comments ON ( comment_post_ID = ID  AND comment_approved =  '1')";
	    	}
		return $join;
}
add_filter('posts_join', 'search_comments_join');

function get_search_query_terms() {
	$query_array = array();
		$search = get_query_var('s');
		$search_terms = get_query_var('search_terms');
		if (!empty($search_terms)) {
			$query_array = $search_terms;
		} else if (!empty($search)) {
			$query_array = array($search);
		} 
	return $query_array;
}

function hilite($text) {
	$query_terms = get_search_query_terms();
	foreach ($query_terms as $term) {
		if (!empty($term) && $term != ' ') {
			$term = preg_quote($term, '/');
			if (!preg_match('/<.+>/',$text)) {
				$text = preg_replace('/(\b'.$term.'\b)/i','<span class="hilite">$1</span>',$text);
			} else {
				$text = preg_replace('/(?<=>)([^<]+)?(\b'.$term.'\b)/i','$1<span class="hilite">$2</span>',$text);
			}
		}
	}
	return $text;
}

function hilite_tags($tags) {
	$query_terms = get_search_query_terms();
	foreach ($query_terms as $term) {
		if (!empty($term) && $term != ' ' && !empty($tags)) {
		foreach ($tags as $tag) {
			if (!empty($tag) && $tag != ' ') {
				if ($tag->name == $term)
					$tag->name ="<span class=\"hilite\">$tag->name</span>";
				}
			}
		}
	}
	return $tags;
}

// Highlight text and comments:
add_filter('the_content', 'hilite');
add_filter('get_the_tags', 'hilite_tags');
add_filter('the_excerpt', 'hilite');
add_filter('comment_text', 'hilite');

function iphone_css() {
if ( strstr( $_SERVER['HTTP_USER_AGENT'], 'iPhone' ) or attribute_escape($_GET['iphone']) == true) { ?>
<meta name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;"/> 
<style type="text/css">
#header_img img, #sidebar, #postbox .avatar {
	display: none;
}
#header {
	margin: 0;
	padding: 0;
}
#header .sleeve {
	padding: 0;
	margin: 0;
	width: 100%;
}
#header h1, #header small {
	display: block;
	width: 100%;
}
#header h1 {
	padding-left: 16px;
	margin-bottom: 2px;
}

#main h2 .controls {
	display: none;
}
.actions {
	clear: both;
	display: block;
	position: static !important;
	text-align: right;
	height: 1em;
	top: 0em !important;
	margin-bottom: -2em;
}
.meta {
line-height: 1em;
	width: 100%;
}
div.postcontent, div.commentcontent {
	margin-left: 30px;
}
#main h4 {
	line-height: 1.5;
	clear: both;
	margin-bottom: .5em;
}
.avatar {
position: relative;
left: 0px;
top: 5px;
margin-bottom: -20px;
}
#main #respond.replying, #main .commentlist li #respond.replying  {
position: absolute;
width: 100%;
height: 100%;	
	margin-left: 0 !important;
	z-index: 1000;
	left: 0px !important;
}
li #respond textarea {
	width: 80%;
	height: 80%;
}
#main h4 {
	position: relative;
	margin-left: 30px;
}
h1 a {
	display: block;
	width: 295px;
	font-family: Helvetica;
}
#footer {
	width: 100%;
	font-size: 8px;
	min-width: 0;
}
#main ul.commentlist, #main ul.commentlist ul {
	margin-left: 20px !important;
}

#wrapper {
width: 100%;
	
	min-width: 0;
	margin: 0;
	padding: 0;
	overflow: visible;
	position: static;
}
.avatar {
	width: 20px;
	height: 20px;
}
.sleeve_main {
width: 100%;
	margin: 0;
}
#header {
	padding: 0;
	margin: 0;
	width: 100%;
}

#main {
	margin: 0 10px;
	padding: 0;
	float: none;
}
#main ul#postlist ul li {
	margin-left: 0;
}
h1 {
	font-size: 2em;
	font-family: Georgia, "Times New Roman", serif;
	margin-left: 0;
	margin-top: 5px;
	margin-bottom: 10px;
	padding: 0;
}

h2 {
	font-size: 1.2em;
	font-weight: bold;
	color: #555;
}

#postbox form {
	padding: 5px;
}

#postbox textarea#posttext {
	height: 50px;
	border: 1px solid #c6d9e9;
	margin-bottom: 10px;
	padding: 2px;
	font: 1.4em/1.2em "Lucida Grande",Verdana,"Bitstream Vera Sans",Arial,sans-serif;
}
#postbox input#tags,  #commentform #comment {
	font-size: 1.2em;
	padding: 2px;
	border: 1px solid #c6d9e9;
	width: 300px;
	margin-left: 0;
}
#postbox {
	margin: 0;
	padding: 0;
}
#postbox label {
	color: #333;
	display: block;
	font-size: 1.2em;
	margin-bottom: 4px;
	margin-left: 0;
	font-weight: bold;
}
#postbox .inputarea {
	padding-left: 0;
}

#notify {
	width: 70%;
left: 15%;
top: 30%;
}
#postbox input#submit {
	font-size: 1.2em;

	margin-top: 5px;
}

#main ul {
	list-style: none;
	margin-top: 16px;
	margin-left: 0;
}

#wpcombar {
	display: none;
}
body {
	padding-top: 0 !important;
}
</style>
<?php } }
add_action('wp_head', 'iphone_css');

/* prologue_get_comment_reply_link 
    Modified to replace query string with blog url in output string
*/
function prologue_get_comment_reply_link($args = array(), $comment = null, $post = null) {
	global $user_ID;

	$defaults = array('add_below' => 'comment', 'respond_id' => 'respond', 'reply_text' => __('Reply'),
		'login_text' => __('Log in to Reply'), 'depth' => 0, 'before' => '', 'after' => '');

	$args = wp_parse_args($args, $defaults);
	if ( 0 == $args['depth'] || $args['max_depth'] <= $args['depth'] )
		return;

	extract($args, EXTR_SKIP);

	$comment = get_comment($comment);
	$post = get_post($post);

	if ( 'open' != $post->comment_status )
		return false;

	$link = '';

	if ( get_option('comment_registration') && !$user_ID )
		$link = '<a rel="nofollow" href="' . site_url('wp-login.php?redirect_to=' . get_permalink()) . '">' . $login_text . '</a>';
	else
		$link = "<a rel='nofollow' class='comment-reply-link' href='". get_permalink($post). "#" . $respond_id . "' onclick='return addComment.moveForm(\"$add_below-$comment->comment_ID\", \"$comment->comment_ID\", \"$respond_id\", \"$post->ID\")'>$reply_text</a>";
	return apply_filters('comment_reply_link', $before . $link . $after, $args, $comment, $post);
}


function prologue_comment_depth_loop($comment_id, $depth)  {
	$comment=get_comment($comment_id);
	if ($comment->comment_parent!=0) {
		$depth++;
		return prologue_comment_depth_loop($comment->comment_parent, $depth);
	}
	return $depth;
}

function prologue_get_comment_depth($comment_id) {
	$depth = 1;
	$depth = prologue_comment_depth_loop($comment_id, $depth);
	return $depth;
}

function prologue_comment_depth($comment_id) {
	echo prologue_get_comment_depth($comment_id);
}
