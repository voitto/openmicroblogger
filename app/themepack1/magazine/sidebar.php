		<!-- Right Column -->
		<div id="rightcolumn">	
			<h2 id="titlespons">Sponsors</h2>
			<!-- Sponsor Do not forget to assign a class as .right or .left to the images when you add and ad image -->
			<div id="sponsors">
				<?php include (TEMPLATEPATH . '/125x125ads.php'); ?>
				<div style="clear:both;"></div>
			</div>
			<!-- Categories -->
			<div id="categories">
				<?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('sidebarleft') ) : ?>
					<h2>Categories</h2>
					<ul>
						<?php wp_list_cats('sort_column=name&optioncount=0&use_desc_for_title=0'); ?>
					</ul>	
				<?php endif; ?>
			</div>
			<!-- Archive -->
			<div id="archive">
				<?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('sidebarright') ) : ?>
				<h2>Links</h2>
				<ul>
					<?php get_links('-1', '<li>', '</li>', '', FALSE, 'id', FALSE, FALSE, -1, FALSE); ?>
				</ul>
				<h2>Archive</h2>
				<form id="archiveform" action="">
					<select name="archive_chrono" onchange="window.location =
					(document.forms.archiveform.archive_chrono[document.forms.archiveform.archive_chrono.selectedIndex].value);">
					<option value=''>Select Month</option>
					<?php get_archives('monthly','','option'); ?>
					</select>
				</form>
				<?php endif; ?>
			</div>
			<div style="clear:both"></div>			
			
			<?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('sidebarwide') ) : ?>
			
			<?php endif; ?>
			
			<!-- Video of the day -->
			<?php query_posts('cat=4&showposts=1'); ?>
			<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
			<h2><?php the_title(); ?></h2>
			<?php the_content(); ?>
			<?php endwhile; else : ?>		
			<?php endif; ?>
			<!-- /Video of the day -->
		</div>
		<div style="clear:both"></div>		
	</div>