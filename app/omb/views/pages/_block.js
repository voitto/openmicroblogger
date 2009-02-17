<?php ob_start(); ?>

  <li class="pagenav">
    <h2>Pages</h2>
      <ul>
  
  <?php $links = get_nav_links(); ?>
  
  <?php foreach ($links as $page=>$url) : ?>

     <li class="page_item">
        <a href="<?php echo $url; ?>"><?php echo $page; ?></a>
    </li>
    
  <?php endforeach; ?>
  
  <?php while( $Member = $collection->MoveNext() ) : ?>
    
     <li class="page_item">
        <a href="<?php if (!empty($Member->url)) eval($Member->url); else url_for( array( 'resource'=>'pages', 'id'=>$Member->id )); ?>"><?php print $Member->title; ?></a>
    </li>

    <?php endwhile; ?>
    
    
    
    
  </ul>
</li>

<?php $content = ereg_replace("'","\'",ereg_replace("\n","",ob_get_contents())); ob_end_clean(); ?>

<?php print "document.write('".$content."');" ?>