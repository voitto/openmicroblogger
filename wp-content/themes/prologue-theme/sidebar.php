
<div id="sidebar">


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
      <p>Powered by <a href="http://structal.net/">Structal</a></p>
      <p>Theme by <a href="http://automattic.com/">Automattic</a></p>
    </li>
  </ul>


<?php 

//include('resource/dingshow_plainphp/index.php');

?>

</div> <!-- // sidebar -->

