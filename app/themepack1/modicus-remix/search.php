<?php get_header(); ?>

<div class="post">
  <p>Look what we found.</p>
  <br/>
  <ul class="archive">
    <?php if (have_posts()) : ?>
    <?php while (have_posts()) : the_post(); ?>
    <li id="post-<?php the_ID(); ?>"><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>">
      <?php the_title(); ?>
      </a>
      <?php if ( function_exists('the_excerpt_reloaded')) :?>
      <?php the_excerpt_reloaded(15, '', 'excerpt', FALSE, '[more]', FALSE, 1, TRUE); ?>
      <?php else: ?>
      <?php the_excerpt(); ?>
      <?php endif; ?>
      <p class="postmetadata">Posted on
        <?php the_time('M d.y') ?>
        to
        <?php the_category(', ') ?>
        &nbsp;
        <?php comments_popup_link('Add a Comment', '1 Comment', '% Comments'); ?>
        &nbsp;&nbsp;
        <?php edit_post_link('Edit', '', ''); ?>
      </p>
    </li>
    <?php endwhile; ?>
  </ul>
  <div class="navigation">
    <div class="alignleft">
      <?php next_posts_link('&laquo; Previous Entries') ?>
    </div>
    <div class="alignright">
      <?php previous_posts_link('Next Entries &raquo;') ?>
    </div>
  </div>
  <!-- end navigation -->
  <?php else : ?>
  <li>Hmm. Nothing. That's not what either one of us expected.</li>
  </ul>
  <ul class="archive">
    <li>Try again?</li>
    <li>
      <ul>
        <?php include (TEMPLATEPATH . '/searchform.php'); ?>
      </ul>
      <br/>
    </li>
  </ul>
  <?php endif; ?>
</div>
<!-- end post -->
<?php include (TEMPLATEPATH . '/sidebar.php'); ?>
<?php get_footer(); ?>
