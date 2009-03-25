<?php
	if ( get_comments_number() > 0 ) {
		echo "<ul class=\"commentlist inlinecomments\">\n";
		wp_list_comments(array('callback' => 'prologue_comment_frontpage'));
		echo "</ul>\n";	
	}
	
?>