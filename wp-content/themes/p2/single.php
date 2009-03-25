<?php 
get_header( ); 

if( have_posts( ) ) {
	$first_post = true;

	while( have_posts( ) ) {
		the_post( );

		$email_md5		= md5( get_the_author_email( ) );
		$default_img	= urlencode( 'http://use.perl.org/images/pix.gif' );
?>
<div class="sleeve_main">
<div id="postpage">
<div id="main">
	<ul id="postlist">
		<li>
			<h2 class="title">
				<?php the_title(); ?>
			</h2>
			<h4>
				<?php echo prologue_get_avatar( get_the_author_ID( ), get_the_author_email( ), 48 ); ?>
				<?php the_author_posts_link( ); ?>
				
				<span class="meta">
			<?php the_time( ); ?> <em>on</em> <?php the_time( 'F j, Y' ); ?> |
			<?php comments_number( __( '0' ), __( '1' ), __( '%' ) ); ?>
			<span class="actions">
			<?php if (function_exists('post_reply_link')) 
				echo post_reply_link(array('before' => '', 'reply_text' => 'Reply', 'add_below' => 'prologue'), get_the_id()); ?>
			<?php if (current_user_can('edit_post', get_the_id())) { ?>
			|  <a href="<?php echo (get_edit_post_link( get_the_id() ))?>" class="post-edit-link" rel="<?php the_ID(); ?>">Edit</a>
			<?php } ?>
			</span>
			<br />
			<?php tags_with_count( '', __( 'Tags: ' ), ', ', ' ' ); ?>
			</span>
			</h4>
			<div class="postcontent<?php if (current_user_can( 'edit_post', get_the_id() )) {?> editarea<?}?>" id="content-<?php the_ID(); ?>">
				<?php the_content( __( '(More ...)' ) ); ?>
				<?php wp_link_pages(); ?>
			</div> <!-- // postcontent -->
			<div class="bottom_of_entry">&nbsp;</div>
			<?php
		comments_template( );

	} // while have_posts
} // if have_posts
?>
		</li>
	</ul>

</div> <!-- // main -->
</div>
</div> <!-- // sleeve -->
<?php get_footer() ; ?>