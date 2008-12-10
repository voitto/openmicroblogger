<?php get_header(); ?>

<div class="post">
  <?php if (have_posts()) : ?>
  <?php while (have_posts()) : the_post(); ?>
  <a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>">
  <h1>
    <?php the_title(); ?>
  </h1>
  </a>
  <?php the_content('Read more...'); ?>
  <p class="postmetadata"><small><span style="float:right;">
    <?php comments_popup_link('No Comments', '1 Comment', '% Comments'); ?>
    /
    <?php edit_post_link('Edit', '', ''); ?>
    </span>
    <?php the_time('M d.y') ?>
    /
    <?php the_category(', ') ?>
    / by <a href="index.php?author=<?php the_author_ID(); ?>">
    <?php the_author_nickname(); ?>
    </a></small></p>
  <?php endwhile; ?>
  <?php endif; ?>
  <div class="navigation">
    <div class="alignleft">
      <?php next_posts_link('&laquo; Previous Entries') ?>
    </div>
    <div class="alignright">
      <?php previous_posts_link('Next Entries &raquo;') ?>
    </div>
  </div>
  <!-- end navigation -->
</div>
<!-- end post -->
<?php get_sidebar(); ?>
<?php get_footer(); ?>
