<?php
get_header( ); 
include 'wp-content/language/lang_chooser.php'; //Loads the language-file
?>
<div id="postpage">
<div id="main">
<div id="postlist">

<?php 
if( have_posts( ) ) { 
	while( have_posts( ) ) {
		the_post( ); 
?>

<div <?php post_class('post'); ?> id="post-<?php the_ID( ); ?>">
	<h2><?php the_title( ); ?></h2>
	<div class="entry">


		<?php the_content('<p class="serif">'.$txt['page_read_rest'].'&rarr;</p>'); ?>

		<?php if ( comments_open() ) comments_template(); ?>

		<?php wp_link_pages(array('before' => '<p><strong>'.$txt['page_pages'].'</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>


	</div> <!-- // entry -->
</div> <!-- post-<?php the_ID( ); ?> -->

<?php
	} // while have_posts

} // if have_posts
?>

	</div> <!-- // postlist -->
</div> <!-- // main -->
</div>
<?php
get_footer( );
