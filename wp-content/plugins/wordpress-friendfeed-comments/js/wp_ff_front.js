var $j = jQuery.noConflict();

$j(document).ready(function() {
	$j("#wp_ff_likes_link").click(function() { 
		$j("#wp_ff_likes_link").css({"display":"none"});
		$j("#wp_ff_likes").css({"display":"block"}).show("slow");
	});
});

