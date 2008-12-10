<?php get_header(); ?>
	
	<!-- Container -->	
	<div id="content-wrap">
		<!-- Left Column -->
		<div id="leftcolumn">
		
			<!-- Featured Article -->
			<div id="featured">	
				<!-- Featured article loop -->
				<?php query_posts('cat=3&showposts=1'); ?>
				<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
				<!-- title of featured article -->
				<h3><a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a></h3>				<!-- content of featured article-->
				<?php the_excerpt_reloaded(30, '<img><a>', 'content', false, 'More...', true);?>
					<!-- Featured Article Post Details -->
					<div id="postdetails">
						<?php the_time('F j, Y'); ?> | <?php comments_popup_link(__('Leave comment'), __('1 Comment'), __('% Comments'));?> | <a href="<?php echo get_permalink(); ?>" title="Read More">Read More</a>
					</div>
				<!-- End of Loop fore featured article -->
				<?php endwhile; else : ?>
				<?php endif; ?>
			</div>
			

		</div>
		<!-- /Left Column -->
		
		
<?php get_sidebar(); ?>
<?php get_footer(); ?>