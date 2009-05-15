<?php 
get_header( );
include 'wp-content/language/lang_chooser.php'; //Loads the language-file
?>
<div class="sleeve_main">
<div id="main">
	<h2><?php echo $txt['search_search_results']; ?><?php the_search_query(); ?></h2>
	

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
		// Don't show the avatar if the previous post was by the same user
		$current_user_id = get_the_author_ID( );
		if( $previous_user_id !== $current_user_id or ( is_home() or is_front_page() )) {
			echo prologue_get_avatar( $current_user_id, get_the_author_email( ), 48 );
		}
		$previous_user_id = $current_user_id;
?>

	<h4>
		<?php the_author_posts_link( ); ?>
		<span class="meta">
			<?php the_time( ); ?> on <?php the_time( 'F j, Y' ); ?> |
			<?php comments_popup_link( __( '0' ), __( '1' ), __( '%' ) ); ?> |
			<a href="<?php the_permalink( ); ?>" class="thepermalink">#</a>
			<?php if (function_exists('post_reply_link')) 
				echo post_reply_link(array('before' => ' | ', 'reply_text' => 'r', 'add_below' => 'prologue'), get_the_id()); ?>
			<?php if (current_user_can('edit_post', get_the_id())) { ?>
			|  <a href="<?php echo (get_edit_post_link( get_the_id() ))?>" class="editpost" rel="<?php the_ID(); ?>">e</a>
			<?php } ?>
			<br />
			<?php tags_with_count( '', __( <?php echo $txt['search_tags']; ?> ), ', ', ' ' ); ?>
		</span>
	</h4>
	<div class="postcontent<?php if (current_user_can( 'edit_post', get_the_id() )) {?> editarea<?}?>" id="content-<?php the_ID(); ?>"><?php the_content( __( <?php echo $txt['search_more']; ?> ) ); ?></div> <!-- // postcontent -->
	<div class="bottom_of_entry">&nbsp;</div>
	<?php $withcomments = true; comments_template('/inline-comments.php'); ?>




</li>
<?php
	} // while have_posts
?>
	</ul>
<?php
} // if have_posts
?>

	</ul>
</div> <!-- // main -->
</div><!-- // sleeve -->
<?php
get_footer( );
