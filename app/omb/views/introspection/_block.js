<?php ob_start(); ?>

<?php if (member_of('administrators')) : ?>
      
      <table align="center" >
      <tr><tr><p><b>Manage:</b></p></td></tr>
      <?php $tabs = introspect_tables(); ?>
      <?php foreach ( $tabs as $resource ) : ?>
        <tr>
          <td>
            <a href="<?php url_for( $resource ); ?>">
             <p><?php print ucwords($resource) . ""; ?></p>
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
      </table>
      
<?php endif; ?>

<?php
  
  $content = ereg_replace("'","\'",ereg_replace("\n","",ob_get_contents()));
  ob_end_clean();
  
?>

<?php print "document.write('".$content."');" ?>