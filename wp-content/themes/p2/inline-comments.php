<?php
	if ( get_comments_number() > 0 ) {
		wp_list_comments(array('callback' => 'prologue_comment_frontpage'));
	}
	
?>