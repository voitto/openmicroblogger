<?php
/*
Template Name: Archives
*/
?>
<?php get_header(); ?>

<div class="post">
  <h1>The Archives</h1>
  It's probably in here somewhere. Have a look around. Explore the archives by author, time, and category. Or give the search on the right a spin.<br/>
  <br/>
  <h2>By Author</h2>
  <div style="padding:5px;background:#eaeaea;text-align:left;"><span style="float:right;">Duties / Here</span><a href="index.php?author=1">Your Name Here</a></div>
  <div style="background:#f0f8f5;padding:5px;"><img src="<?php bloginfo('template_directory'); ?>/images/thumb_Brad.jpg" style="margin:0px 5px 5px 0px;display:block;float:left;" /><i>Introduce yourself here! Just edit the 'archives.php' file in the modicus remix template!</i></div>
  <br />
  <br style="clear:both;" />
  <br />
  <h2>Archives by Month:</h2>
  <ul>
    <?php wp_get_archives('type=monthly'); ?>
  </ul>
  <br />
  <h2>Archives by Subject:</h2>
  <ul>
    <?php wp_list_cats(); ?>
  </ul>
</div>
<!-- end post -->
<?php get_sidebar(); ?>
<?php get_footer(); ?>
