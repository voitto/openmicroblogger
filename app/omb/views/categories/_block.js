<?php ob_start(); ?>

  <li class="categories">
    <h2>Categories</h2>
      <ul>
  <?php while( $Category = $collection->MoveNext() ) : ?>
    
    <?php
      global $db;
      $Entry =& $db->get_table( 'entries' );
      $Join =& $db->get_table( 'categories_entries' );
      $Join->find_by( 'category_id', $Category->id );
      $count = 0;
    ?>
    
    <?php while( $Member = $Join->MoveNext() ) : ?>
    
    <?php $count++; ?>
    
    <?php endwhile; ?>
    
     <li class="cat-item">
        <a href="<?php url_for( array( 'resource'=>'categories', 'id'=>$Category->id )); ?>"><?php print $Category->name; ?></a><?php if ($count > 0) print " (".$count.")"; ?>

    </li>
    
    
    <?php endwhile; ?>
    
  </ul>
</li>

<?php $content = ereg_replace("'","\'",ereg_replace("\n","",ob_get_contents())); ob_end_clean(); ?>

<?php print "document.write('".$content."');" ?>