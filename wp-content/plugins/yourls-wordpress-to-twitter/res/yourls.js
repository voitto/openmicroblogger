// Stuff that happens on the plugin option page
jQuery(document).ready(function($){

	// stuff for the divs that have to toggle with their select element.
    $('.y_toggle').each(function(){
		$(this).change(function(){
			var source = $(this).attr('id');
			if ( $(this).attr('type') == 'checkbox' ) {
				if ($(this).attr('checked') == true) {
					$('.'+source).fadeIn(100);
				} else {
					$('.'+source).fadeOut(100).find(':checkbox').attr('checked',false);
				}
			} else {
			  if ($(this).val() == 'rply') {
			    $('#twitter_settings').hide();
		    } else {
			    $('#twitter_settings').fadeIn(300);
	      }
				var target = $(this).val();
				$('.'+source).hide();
				$('#y_show_'+target).fadeIn(300);
			}
		});
	})	
	
	// Password reveal: create the checkboxes
	$('input:password').each(function(){
		var target = $(this).attr('id');
		$(this).after('&nbsp;<label><input type="checkbox" class="y_reveal" id="y_reveal_'+target+'> Show letters</label>');
		$('#y_reveal_'+target).data('target', target)
	});
	
	// Password reveal: checkboxes behavior
	$('.y_reveal').change(function(){
		var target = $(this).data('target');
		password_toggle(target, $(this).attr('checked'));
		return;
	});
	
	// Twitter sample copy
	$('.tw_msg_sample').click(function(){
		$('#tw_msg').val($(this).html());
	});
	
	// Toggle display between password and text fields
	function password_toggle(target, display) {
		if (display) {
			var pw = $('#'+target).val();
			$('#'+target).hide().after('<input type="text" name="'+target+'_text__" value="'+pw+'" id="'+target+'_text__"/>');
		} else {
			var pw = $('#'+target+'_text__').val();
			$('#'+target).show().val(pw);
			$('#'+target+'_text__').remove();
		}
		// No, you can't change $('#tw_passwd').attr('type') on the fly, in case you're wondering
	}
	
	// Reset all password fields (make them passwords, not texts)
	function password_hide_all() {
		$('input:password').each(function(){
			password_toggle( $(this).attr('id'), false );
		});
	}
	
	// On form submit, first reset all pwd fields
	$('.y_submit').click(function(){
		password_hide_all();
	});
	
	// Sanitize Windows paths
	$('#y_path').keyup(function(){
		$(this).val( $(this).val().replace(/\\/g, '/') );
	});
});

