<?php get_header(); ?>

	<!-- Container -->
	<div id="content-wrap">
	
		<!-- single post content -->
		<div id="singlepost">
			<div class="post">
				<!-- single post loop -->
				<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
			
				<!-- title of the single pot -->
				<h3><?php the_title(); ?></h3>
				<!-- content -->
				<?php the_content(__('')); ?>

				<!-- Single Post Details -->
			</div>

			<div id="singlepostinfo">
				This entry was posted
				<?php /* This is commented, because it requires a little adjusting sometimes.
					You'll need to download this plugin, and follow the instructions:
					http://binarybonsai.com/archives/2004/08/17/time-since-plugin/ */
					/* $entry_datetime = abs(strtotime($post->post_date) - (60*120)); echo time_since($entry_datetime); echo ' ago'; */ ?>
				on <?php the_time('l, F jS, Y') ?> and is filed under <?php the_category(', ') ?>.
				You can follow any responses to this entry through the <?php comments_rss_link('RSS 2.0'); ?> feed.
				<?php if (('open' == $post-> comment_status) && ('open' == $post->ping_status)) {
					// Both Comments and Pings are open ?>
				You can <a href="#respond">leave a response</a>, or <a href="<?php trackback_url(); ?>" rel="trackback">trackback</a> from your own site.
				<?php } elseif (!('open' == $post-> comment_status) && ('open' == $post->ping_status)) {
					// Only Pings are Open ?>
				Responses are currently closed, but you can <a href="<?php trackback_url(); ?> " rel="trackback">trackback</a> from your own site.
				<?php } elseif (('open' == $post-> comment_status) && !('open' == $post->ping_status)) {
					// Comments are open, Pings are not ?>
				You can skip to the end and leave a response. Pinging is currently not allowed.
				<?php } elseif (!('open' == $post-> comment_status) && !('open' == $post->ping_status)) {
					// Neither Comments, nor Pings are open ?>
				Both comments and pings are currently closed.
				<?php } edit_post_link('Edit this entry.','',''); ?>
			</div>
				
			<div class="single-entry-nav">
				<div class="left"><?php previous_post_link('&laquo; %link') ?></div>
				<div class="right"><?php next_post_link('%link &raquo;') ?></div>
				<div style="clear:both"></div>
			</div>
				
			<div id="comment-wrapper">
				<?php comments_template(); ?>
			</div>		
				
			<!-- End of Loop fore single post -->
			<?php endwhile; else : ?>
			<?php endif; ?>
		</div>
		<!-- /singlepost  -->
				
<?php get_sidebar(); ?>

<?php get_footer(); ?>