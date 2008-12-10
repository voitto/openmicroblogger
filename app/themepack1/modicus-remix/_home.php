<?php get_header(); ?>

<div id="homebody"> <span style="text-transform: uppercase;">
  <?php bloginfo('description'); ?>
  </span>
  <!-- To use this optional home page, rename this file to home.php. You'll need to edit the following by hand. Replace [category name] with the name of a category you want to display on the home page. You can copy the block $my_query block as many times as you like. -->
  <?php $my_query = new WP_Query('category_name=[category name]&showposts=1');
  while ($my_query->have_posts()) : $my_query->the_post();
  $do_not_duplicate = $post->ID; ?>
  <span class="cat">
  <?php the_category('&nbsp;') ?>
  </span> <a href="<?php the_permalink() ?>" rel="bookmark">
  <?php the_title(); ?>
  </a>
  <?php endwhile; ?>
  <a href="about" title="About">ABOUT</a> FACE. DIVE INTO THE <a href="archives" title="Archives">ARCHIVES</a>. DON&rsquo;T MISS A TRICK: <a href="<?php bloginfo('rss2_url'); ?>" class="subscribe">SUBSCRIBE.</a> </div>
<!-- end homebody -->
<?php get_footer(); ?>
