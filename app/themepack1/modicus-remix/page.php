<?php get_header(); ?>
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<div class="post" id="post-<?php the_ID(); ?>"> <a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>">
  <h1>
    <?php the_title(); ?>
  </h1>
  </a>
  <?php the_content(); ?>
</div>
<!-- end post -->
<?php endwhile; else: ?>
<p>Sorry, no posts matched your criteria.</p>
<?php endif; ?>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
