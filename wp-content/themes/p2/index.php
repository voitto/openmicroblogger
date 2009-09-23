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
  
  <?php
    global $db;
    if (!empty($db->prefix))
      $chan = $db->prefix;
    else
      $chan = "chan";
  ?>
  
  <script type="text/javascript">
    // <![CDATA[
    Meteor.hostid = '<?php echo get_profile_id(); ?>';
    Meteor.host = "<?php echo REALTIME_HOST; ?>";
    Meteor.registerEventCallback("process", test);
    Meteor.joinChannel("<?php echo $chan; ?>", 0);
    Meteor.mode = 'stream';
    Meteor.connect();
    function test(data) {
      data = data.substring(0,(data.length - 10));
      eval( "data = " + data );
      if (data['in_reply_to']) {
        var selectr = data['in_reply_to'];
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
<div id="postlist">
<?php
	$previous_user_id = 0;
	while( have_posts( ) ) {
		the_post( );
?>

<?php
	$current_user_id = get_the_author_ID( );
	global $the_author;
	global $the_post;
?>



<span>
	<a href="<?php echo $the_author->profile_url; ?>">
	  <img src="<?php echo $the_author->avatar; ?>" height="48" width="48" border="0">
	</a>
</span>
<span>
	<strong>
		<a href="<?php echo $the_author->profile_url; ?>" title="<?php echo $the_author->name; ?>"><?php echo $the_author->nickname; ?></a>
	</strong>
	<span>
		<?php the_content( __( '(More ...)' ) ); ?>
	</span>
	<span>
		<a href="<?php echo $the_post->url; ?>">
			<span><?php echo laconica_time($the_post->created); ?></span>
		</a>
		<span>from 
			<a href="http://www.tweetdeck.com/">Source</a>
		</span>
	</span>
</span>
<span class="actions">
	<div>
		<a id="favorite" title="favorite this tweet">&nbsp;&nbsp;</a>
		<?php echo in_reply_to($the_post); ?>
	</div>
</span>


<hr />


<?php
	} // while have_posts
?>
</div>
<?php
} // if have_posts
else {
?>
<h3><?php echo $txt['index_no_updates_yet']; ?></h3>
<?php } ?>


	<div class="navigation"><p><?php posts_nav_link(' | ','&larr;&nbsp;' . $txt['index_newer'] . '&nbsp;' . $txt['index_posts'] . '','' . $txt['index_older'] . '&nbsp;' . $txt['index_posts'] . '&nbsp;&rarr;'); ?></p></div>
</div> <!-- // main -->
</div> <!-- // sleeve -->

<?php
get_footer( );
