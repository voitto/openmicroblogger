jQuery(document).ready( function() {
	jQuery('#open_options').toggle( function(){
		jQuery('#options').css({display:'block'});
		jQuery("#open_options h2").addClass("tiklandi").removeClass("normal");
	}, function() { 
		jQuery('#options').css({display:'none'});
		jQuery("#open_options h2").addClass("normal").removeClass("tiklandi");
	});
});