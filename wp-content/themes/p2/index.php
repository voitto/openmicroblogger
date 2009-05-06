<?php
include 'wp-content/language/lang_chooser.php'; //Loads the language-file

if( 'POST' == $_SERVER['REQUEST_METHOD'] && !empty( $_POST['action'] ) && $_POST['action'] == 'post' ) {
	if ( ! is_user_logged_in() )
		auth_redirect();

	if( !current_user_can( 'publish_posts' ) ) {
		wp_redirect( get_bloginfo( 'url' ) . '/' );
		exit;
	}

	check_admin_referer( 'new-post' );

	$user_id		= $current_user->user_id;
	$post_content	= $_POST['posttext'];
	$tags			= $_POST['tags'];

	$char_limit		= 40;
	$post_title		= strip_tags( $post_content );
	if( strlen( $post_title ) > $char_limit ) {
		$post_title = substr( $post_title, 0, $char_limit ) . ' ... ';
	}
	
	//Try to detect image or video only posts, and set post title accordingly
	if ($post_title=='') {
		if (preg_match("/<object|<embed/", $post_content))
			$post_title= $txt['index_video_post'];
		elseif (preg_match("/<img/", $post_content))
			$post_title= $txt['index_image_post'];
		else
			$post_title= $txt['index_no_title'];
		}
	$post_id = wp_insert_post( array(
		'post_author'	=> $user_id,
		'post_title'	=> $post_title,
		'post_content'	=> $post_content,
		'tags_input'	=> $tags,
		'post_status'	=> 'publish'
	) );

	wp_redirect( get_bloginfo( 'url' ) . '/' );
	exit;
		
} 

get_header();

?>


<?php if (REALTIME_HOST) : ?>
  <script type="text/javascript">
    // <![CDATA[<script type="text/javascript">
    Meteor.hostid = '<?php echo get_profile_id(); ?>';
    Meteor.host = "<?php echo REALTIME_HOST; ?>";
    Meteor.registerEventCallback("process", test);
    Meteor.joinChannel("<?php echo $prefix; ?>", 0);
    Meteor.mode = 'stream';
    Meteor.connect();
    function test(data) {
      data = data.substring(0,(data.length - 10));
      eval( "data = " + data );
      if (data['in_reply_to'] > 0) {
        var selectr = "#prologue-"+data['in_reply_to']+" ul#comments";
        $(selectr).append(data['html']);
      } else {
        $("#postlist").prepend(data['html']);
      }
      $('a.oembed').oembed();
    };
    // ]]>
  </script>
<?php endif; ?>



<div class="sleeve_main">
<?php
	global $request;
	if (!(isset($request->params['nickname']))) {
	  if( current_user_can( 'publish_posts' ) )
			require_once dirname( __FILE__ ) . '/post-form.php';
	} else {
	  render_partial('profile');
  }
?>
<div id="main">
		<?php global $paged;?>
	<h2><?php echo $txt['index_recent_updates']; ?><?php if ($paged>1) echo('(Page '.$paged.') '); ?><a class="rss" href="<?php bloginfo( 'rss2_url' ); ?>"><?php echo $txt['index_rss']; ?></a> <span class="controls"></span></h2>
<?php
if( have_posts( ) ) {
?>
<ul id="postlist">
<?php
	$previous_user_id = 0;
	while( have_posts( ) ) {
		the_post( );
?>

<li id="prologue-<?php the_ID(); ?>" class="user_id_<?php the_author_ID( ); ?>">

<?php
	$current_user_id = get_the_author_ID( );
	echo prologue_get_avatar( $current_user_id, get_the_author_email( ), 48 );
?>

	<h4>
		<?php the_author_posts_link( ); ?>
		<span class="meta">
			<?php global $the_post; echo laconica_time($the_post->created); ?> |
			<?php comments_popup_link( __( '0' ), __( '1' ), __( '%' ) ); ?>
			<span class="actions">
			<a href="<?php the_permalink( ); ?>" class="thepermalink"><?php echo $txt['index_permalink']; ?></a>
			<?php if (function_exists('post_reply_link')) 
				echo post_reply_link(array('before' => ' | ', 'reply_text' => $txt['index_link'], 'add_below' => 'prologue'), get_the_id()); ?>
			<?php if (current_user_can('edit_post', get_the_id())) { ?>
			|  <a href="<?php echo (get_edit_post_link( get_the_id() ))?>" class="post-edit-link" rel="<?php the_ID(); ?>"><?php echo $txt['index_edit']; ?></a>
			|  <a href="<?php echo (get_edit_post_link( get_the_id(), 'remove' ))?>" class="post-edit-link" rel="<?php the_ID(); ?>"><?php echo $txt['index_remove']; ?></a>
			<?php } ?>
			</span>
			<br />
			<?php tags_with_count( '', __( 'Tags:' ), ', ', ' ' ); ?>
		</span>
	</h4>
	<div class="postcontent<?php if (current_user_can( 'edit_post', get_the_id() )) {?> editarea<?php }?>" id="content-<?php the_ID(); ?>"><?php the_content( __( '(More ...)' ) ); ?></div> <!-- // postcontent -->
	<div class="bottom_of_entry">&nbsp;</div>
	<?php 
		if( (is_home() or is_front_page()) ) $withcomments = true; 
		comments_template('/inline-comments.php'); 
	?>
<?php
//Only append comment form to  first post with open comments
if( !$formvisible && 'open' == $post->comment_status ) {
$formvisible=1;
?>
<div id="wp-temp-form-div" style="display:none">
<div id="respond" style="display:none">

<h3><?php echo $txt['index_reply']; ?> <small id="cancel-comment-reply"><?php echo cancel_comment_reply_link() ?></small></h3>

<?php
if ( get_option('comment_registration') && !$user_ID ) {
?>

<p><?php echo $txt['index_you_must_be']; ?><a href="<?php echo get_option('siteurl'); ?>/wp-login.php?redirect_to=<?php the_permalink(); ?>" title="Log in"><?php echo $txt['index_logged_in']; ?></a><?php echo $txt['index_to_post_a_comment']; ?>.</p>

<?php
// if option comment_registration and not user_ID
} else { ?>

<form id="commentform" action="<?php echo get_option( 'siteurl' ); ?>/wp-comments-post.php" method="post">
	<div class="form"><textarea id="comment" name="comment" cols ="45" rows="3"></textarea></div>
	<label class="post-error" for="comment" id="commentindex_error"></label>
	<?php if( $user_ID ) { ?>
		<p>Logged in as <a href="<?php echo get_option( 'siteurl' ); ?>/wp-admin/profile.php"><?php echo $user_identity; ?></a>.  <a href="<?php echo get_option( 'siteurl' ); ?>/wp-login.php?action=logout" title="Log out"><?php echo $txt['index_logout']; ?> &rarr;</a></p>
<?php // if user_ID 
} else { ?>
<table>
	<tr>
		<td>
			<label for="author">Name <em>(required)</em></label>
			<div class="form"><input id="author" name="author" type="text" value="<?php echo $comment_author; ?>" /></div>
		</td>
		<td>
			<label for="email">Email <em>(required)</em></label>
			<div class="form"><input id="email" name="email" type="text" value="<?php echo $comment_author_email; ?>"  /></div>
		</td>
		<td class="last-child">
			<label for="url">Web Site</label>
			<div class="form"><input id="url" name="url" type="text" value="<?php echo $comment_author_url; ?>"  /></div>
		</td>
	</tr>
</table>
<?php } // else user_ID ?>

<div><input id="comment-submit" name="submit" type="submit" value="Post Comment"  /><?php comment_id_fields(); ?>&nbsp;<span class="progress"><img src="<?php bloginfo('template_directory'); ?>/i/indicator.gif" alt="Loading..." /></span></div>

</form>
</div>
</div>
<?php
} // else option comment_registration and not user_ID
} // if open comment_status
?>
</li>
<?php
	} // while have_posts
?>
	</ul>
<?php
} // if have_posts
else {
?>
<h3><?php echo $txt['index_no_updates_yet']; ?></h3>
<?php } ?>


	<div class="navigation"><p><?php posts_nav_link(' | ','&larr;&nbsp;Newer&nbsp;Posts','Older&nbsp;Posts&nbsp;&rarr;'); ?></p></div>
</div> <!-- // main -->
</div> <!-- // sleeve -->

<?php
get_footer( );
