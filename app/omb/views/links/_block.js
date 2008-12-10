<?php ob_start(); ?>

  <li class="linkcat">
    <h2>Links</h2>
      <ul>
  <?php while( $Member = $collection->MoveNext() ) : ?>
    
     <li>
        <a href="<?php if (!empty($Member->url)) eval($Member->url); else url_for( array( 'resource'=>'links', 'id'=>$Member->id )); ?>"><?php print $Member->title; ?></a>
    </li>

    <?php endwhile; ?>
    
  </ul>
</li>

<?php $content = ereg_replace("'","\'",ereg_replace("\n","",ob_get_contents())); ob_end_clean(); ?>

<?php print "document.write('".$content."');" ?>

