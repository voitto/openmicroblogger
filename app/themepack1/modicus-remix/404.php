<?php get_header(); ?>

<div style="margin-top:8px;"><img src="<?php bloginfo('template_directory'); ?>/images/headers/404.jpg" /></div>
<div class="post">
  <h3> While you're here though why not have a look around?</h3>
  <ul class="archive">
    Try a search...
    <li>
      <?php include (TEMPLATEPATH . '/searchform.php'); ?>
    </li>
  </ul>
  <?php if ( function_exists('random_posts')) :?>
  <ul class="archive">
    <h3>Or maybe  look at some random posts?</h3>
    <br />
    <?php random_posts('10','25','<li>','<br />','',' [...]</li>','false','true'); ?>
  </ul>
  <?php endif; ?>
</div>
<!-- end post -->
<?php include (TEMPLATEPATH . '/sidebar.php'); ?>
<?php get_footer(); ?>
