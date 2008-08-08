<?php ob_start(); ?>

<table cellpadding="1" cellspacing="0" border="0">
  
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
    
    <tr>
      <td>
        <a href="<?php url_for( array( 'resource'=>'categories', 'id'=>$Category->id )); ?>"><?php print $Category->name; ?></a><?php if ($count > 0) print " (".$count.")"; ?>
      </td>
    </tr>
    
    <?php endwhile; ?>
    
</table>

<?php $content = ereg_replace("'","\'",ereg_replace("\n","",ob_get_contents())); ob_end_clean(); ?>

<?php print "document.write('".$content."');" ?>