<?php get_header(); ?>

<div class="post">
  <p>Dive into the archives.</p>
  <br/>
  <?php if (have_posts()) : ?>
  <?php while (have_posts()) : the_post(); ?>
  <div class="interviewtime"><small>
    <?php the_time('M d.y') ?>
    / <a href="/index.php?author=<?php the_author_ID(); ?>">
    <?php the_author_nickname(); ?>
    </a></small></div>
  <div class="interviewlist"><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>">
    <?php the_title(); ?>
    </a></div>
  <br style="clear:both;line-height:5px;" />
  <?php endwhile; ?>
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
<?php endif; ?>
<?php include (TEMPLATEPATH . '/sidebar.php'); ?>
<?php get_footer(); ?>
