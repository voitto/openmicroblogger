<br style="clear:both;" />
<?php wp_footer(); ?>
<div id="footer2"> <br />
  <div class="blackbar" style="text-transform:uppercase;"><small><span style="float:right;">
    <ul class="nav">
      <?php
wp_list_pages('title_li='); ?>
    </ul>
    </span>&copy;  Copyright 2007 <a href="<?php echo get_option('home'); ?>/">
    <?php bloginfo('name'); ?>
    </a>. Thanks for visiting!</small></div>
  <br />
</div>
<!-- end footer -->
</div>
<!-- end wrapper -->
</body></html>