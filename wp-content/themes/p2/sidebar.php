
<div id="sidebar">
<ul>
<?php 
if( !function_exists('dynamic_sidebar') || !dynamic_sidebar() ) { 

	echo prologue_widget_recent_comments_avatar(array('before_widget' => ' <li id="recent-comments" class="widget widget_recent_comments"> ', 'after_widget' => '</li>', 'before_title' =>'<h2>', 'after_title' => '</h2>'  ));

	$before = "<li><h2>Recent Projects</h2>\n";
	$after = "</li>\n";
	$num_to_show = 35;
	echo prologue_recent_projects( $num_to_show, $before, $after );
} // if dynamic_sidebar
?>
	</ul>
<div style="clear: both;"></div>
</div> <!-- // sidebar -->
