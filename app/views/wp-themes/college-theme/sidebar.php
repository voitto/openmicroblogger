  <div id="sidebar">
      <div class="dark-box">
        <h3>Search</h3>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <input type="text" name="s" class="search-box" />
        <input type="submit" class="search-button" value="" />
        </form>
        <div class="clear"></div>
      </div>
      <div class="dark-box-bottom"></div>
      <div class="med-box">
        <h3>Categories</h3>
        <ul>
        <?php wp_list_cats(); ?>
        </ul>
      </div>
      <div class="med-box-bottom"></div>
      <div class="light-box">
        <h3>Archives</h3>
        <ul>
        <?php wp_get_archives('type=monthly'); ?>
        </ul>
      </div>
      <div class="light-box-bottom"></div>
    </div>
</div>
</body>
</html>