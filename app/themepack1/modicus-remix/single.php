<?php get_header(); ?>
	<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
	<div class="post" id="post-<?php the_ID(); ?>">





				<?php the_content('<p class="serif">Read the rest of this entry &raquo;</p>'); ?>
				

				<?php wp_link_pages(array('before' => '<p><strong>Pages:</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
		<br/>
<div style="padding:5px; background:#f0f0f0;">
<span style="float:right;background:url(<?php bloginfo('template_url'); ?>/images/icon_rss.gif);padding-left:10px;background-repeat:no-repeat;background-position:left center;"><?php comments_rss_link('Subscribe to comments'); ?> </span> 						<?php if (('open' == $post-> comment_status) && ('open' == $post->ping_status)) {
							// Both Comments and Pings are open ?>
							 <a href="#respond">Comment</a> | <a href="<?php trackback_url(true); ?>" rel="trackback">Trackback</a> |

						<?php } elseif (!('open' == $post-> comment_status) && ('open' == $post->ping_status)) {
							// Only Pings are Open ?>
			Responses closed, but you can <a href="<?php trackback_url(true); ?> " rel="trackback">trackback</a>. |

						<?php } elseif (('open' == $post-> comment_status) && !('open' == $post->ping_status)) {
							// Comments are open, Pings are not ?>
			You can skip to the end and leave a response. Pinging is currently not allowed.

						<?php } elseif (!('open' == $post-> comment_status) && !('open' == $post->ping_status)) {
							// Neither Comments, nor Pings are open ?>
			Both comments and pings are currently closed. |

						<?php } edit_post_link('Edit this entry |','',''); ?>
</div><div style="padding:5px; background:#f0f0f0;margin:5px 0px;">
<small>Post Tags:</small> <?php the_tags('<small>',', ','</small>');?></div>			
		
<h2>Browse Timeline</h2>
		<ul class="noindent">
						<li><?php previous_post_link('&laquo; %link'); ?></li>
			<li><?php next_post_link('&raquo; %link'); ?></li>
		</ul>
<br />
	<!-- end entry -->
	
				<?php comments_template(); ?>

</div><!-- end post -->

	<?php endwhile; else: ?>
<p>Sorry, no posts matched your criteria.</p>

<?php endif; ?>
<?php get_sidebar(); ?>
<?php get_footer(); ?>