<div id="sidebar">
  <?php if (is_single()) { ?>
  <?php if (('open' == $post-> comment_status) && ('open' == $post->ping_status)) ?>
  <small>Posted on <span style="font-weight:bold; color:#00bf94;">
  <?php the_time('m.d.y') ?>
  </span> to
  <?php the_category(', ') ?>
  by <a href="/index.php?author=<?php the_author_ID(); ?>">
  <?php the_author_nickname(); ?>
  </a></small>
  <h1><a href="<?php echo get_permalink() ?>" rel="bookmark" title="Permanent Link: <?php the_title(); ?>">
    <?php the_title(); ?>
    </a> </h1>
  <p class="postmetadata alt"> <small> View all posts by <a href="/index.php?author=<?php the_author_ID(); ?>">
    <?php the_author_nickname(); ?>
    </a>.
    <?php comments_rss_link('Subscribe'); ?>
    to follow comments on this post.
    <?php if (('open' == $post-> comment_status) && ('open' == $post->ping_status)) {
							// Both Comments and Pings are open ?>
    <a href="#respond">Add your thoughts</a> or <a href="<?php trackback_url(true); ?>" rel="trackback">trackback</a> from your own site.
    <?php } elseif (!('open' == $post-> comment_status) && ('open' == $post->ping_status)) {
							// Only Pings are Open ?>
    Responses are currently closed, but you can <a href="<?php trackback_url(true); ?> " rel="trackback">trackback</a> from your own site.
    <?php } elseif (('open' == $post-> comment_status) && !('open' == $post->ping_status)) {

							// Comments are open, Pings are not ?>
    You can skip to the end and leave a response. Pinging is currently not allowed.
    <?php } elseif (!('open' == $post-> comment_status) && !('open' == $post->ping_status)) {
							// Neither Comments, nor Pings are open ?>
    Both comments and pings are currently closed.
    <?php } edit_post_link('Edit this entry.','',''); ?>
    </small> </p>
  <?php } ?>


  <?php if ( !function_exists('dynamic_sidebar')
        || !dynamic_sidebar(1) ) : ?>
  <?php endif; ?>
  <div class="bluebox">
    <!--Don't be uncool. Leave the theme credit. :) -->
    <small>Powered by <a href="http://dbscript.net" title="dbscript">dbscript</a></small>
    <small>Theme by <a href="http://www.artculture.com" title="International Art and Design Blog">Art Culture</a></small></div>
</div>
<!-- end sidebar -->
