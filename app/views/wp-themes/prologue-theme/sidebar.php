
<div id="sidebar">

<?php global $request; ?>

<?php if (get_profile_id() && $request->resource == 'identities' && in_array($request->action,array('edit','entry'))) {render_partial('admin');}?>

<?php if ($request->action == 'index') : ?>
  <ul>

<?php 
if( !function_exists('dynamic_sidebar') || !dynamic_sidebar() ) { 
  $before = "<li><h2>Recent Projects</h2>\n";
  $after = "</li>\n";

  $num_to_show = 35;

  echo prologue_recent_projects( $num_to_show, $before, $after );
} // if dynamic_sidebar
?>
  
    <li class="credits">
      <p>Powered by <a href="http://dbscript.net/">dbscript</a></p>
      <p>Theme by <a href="http://automattic.com/">Automattic</a></p>
    </li>
  </ul>

<?php endif; ?>

<?php 

//include('resource/dingshow_plainphp/index.php');

?>

</div> <!-- // sidebar -->

