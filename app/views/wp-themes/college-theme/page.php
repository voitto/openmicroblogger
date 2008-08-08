<?php get_header(); ?>

<?php if (have_posts()) : ?>
<?php while (have_posts()) : the_post(); ?>

<h2><a href="<?php the_permalink() ?>"><?php the_title(); ?></a></h2>
      <hr />
      <?php the_content('Read the rest of this entry &raquo;'); ?>
      <hr />
      <p class="comment-link"><?php comments_popup_link('0 Comments', '1 Comment', '% Comments'); ?></p>
    
    <?php endwhile; ?>
</div>
<ul id="tab-menu">
 <li class="TabLink"><a href="#top" id="tab0" onclick="ShowTab(0)"><span>Recent Entries</span></a></li>
 <li class="TabLink"><a href="#top" id="tab1" onclick="ShowTab(1)"><span>Recent Comments</span></a></li>
 <li class="TabLink"><a href="#top" id="tab2" onclick="ShowTab(2)"><span>Links</span></a></li>
 <li class="NavLinks" id="paging0"><div style="display:none" class="TabPaging"></div></li>
 <li class="NavLinks" id="paging1" style="display:none"><div style="display:none"></div></li>
 <li class="NavLinks" id="paging2" style="display:none"><div style="display:none"></div></li>
</ul>

<div class="tab-box" style="display: none" id="div0">  
 <ul><?php if (function_exists("mdv_recent_posts")) mdv_recent_posts(); ?></ul>
</div>

<div class="tab-box" style="display:none" id="div1">
 <ul><?php if (function_exists("mdv_recent_comments")) mdv_recent_comments(); ?></ul>
</div>

<div class="tab-box" style="display:none" id="div2">
 <ul><?php if (function_exists("mdv_most_commented")) mdv_most_commented(); ?></ul>
</div>

<script type="text/javascript">
ShowTab(0);
</script>

<?php else : ?>
<h2><?php _e('Not Found'); ?></h2>
<p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
</div>
<?php endif; ?>
<!-- End SC -->

<?php get_footer(); ?>
<?php get_sidebar(); ?>