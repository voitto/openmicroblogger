// Stuff that happen on the post page
jQuery(document).ready(function(){
	
	// Add the character count
	jQuery('#titlewrap #title').after('<div id="yourls_count" title="Number of chars remaining in a Twitter environment">000</div>').keyup(function(e){
		yourls_update_count();
	});
	yourls_update_count();
	
	
});

function yourls_update_count() {
	var len = 140 - jQuery('#titlewrap #title').val().length;
	jQuery('#yourls_count').html(len);
	jQuery('#yourls_count').removeClass();
	if (len < 60) {jQuery('#yourls_count').removeClass().addClass('len60');}
	if (len < 30) {jQuery('#yourls_count').removeClass().addClass('len30');}
	if (len < 15) {jQuery('#yourls_count').removeClass().addClass('len15');}
	if (len < 0) {jQuery('#yourls_count').removeClass().addClass('len0');}
}


