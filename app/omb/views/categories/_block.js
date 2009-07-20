<?php 
ob_start();
$category_limit = 10;
include 'wp-content/language/lang_chooser.php'; //Loads the language-file
?>

  <p class="liother-b-cat">
    <?php echo $txt['categories_categories']; ?>
  </p>
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
    
     <p class="liother-cat">
        <a href="<?php url_for( array( 'resource'=>'categories', 'id'=>$Category->id )); ?>"><?php print $Category->name; ?></a><?php if ($count > 0) print " (".$count.")"; ?>

    </p>
    
    <?php if ($collection->_currentRow >= $category_limit) : ?>
      <a href="<?php url_for('categories'); ?>">more...</a>
      <?php break; ?>
    <?php endif; ?>
    
    <?php endwhile; ?>

<?php $content = ereg_replace("'","\'",ereg_replace("\n","",ob_get_contents())); ob_end_clean(); ?>

<?php print "document.write('".$content."');" ?>