<?php
include 'wp-content/language/lang_chooser.php'; //Loads the language-file
$user			= get_userdata( $current_user->ID );
$first_name		= attribute_escape( $user->first_name );
?>

<script src="<?php base_path(); ?>resource/jquery.charcounter.js" type="text/javascript"></script>

<script type="text/javascript">
// <![CDATA[
function do_ajax_post(){
	var someurl = "<?php url_for(array('resource'=>'posts')); ?>";
  var submitdata = {};
  submitdata['post[title]'] = $("#posttext").val();
  submitdata['post[body]'] = '';
  submitdata['method'] = 'post';
  $("#auction").html("<img src='<?php base_path(); ?>resource/jeditable/indicator.gif'>");

  $("#postarea").hide();
  $("#postsubmit").hide();
  $("#busy").html("<img src='<?php base_path(); ?>resource/jeditable/indicator.gif'>");
	$.post(someurl, submitdata, function(str) {
    $("#posttext").attr("value", '');
	  $("#postarea").fadeIn("slow");
	  $("#postsubmit").fadeIn("slow");
  	$("#busy").html('');
//	  $("#postarea").html("<textarea name=\"posttext\" id=\"posttext\" rows=\"2\" cols=\"60\" tabindex=\"0\"></textarea>");
//	  $("#postsubmit").html("<input id=\"submit\" type=\"submit\" value=\"<?php _e($txt['postform_postit']); ?>\" />");
  });
	return false;
}

$(document).ready(function() {

	$("#posttext").charCounter(140, {
		container: "#tweetcounter",
		format: "%1",
		pulse: false
	});

});


// ]]>
</script>

<div id="postbox"><div id="busy"></div>
	<form onsubmit="return do_ajax_post()" id="new_post" name="new_post" method="post" action="<?php bloginfo( 'url' ); ?>">
		<input type="hidden" name="action" value="post" />
		<?php wp_nonce_field( 'new-post' ); ?>
		<div class="avatar"><?php echo prologue_get_avatar( $user->ID, $user->user_email, 48 ); ?></div>
		<div class="inputarea">
			<label for="posttext"><?php _e($txt['postform_greeting']); ?>, <?php echo $first_name; ?>. <?php _e($txt['postform_whatsup']); ?></label>
			<div id="postarea">
				<textarea style="
					padding: 1px;
					font-family: Tahoma, sans-serif;
					font-size: 16px;
				 name="posttext" id="posttext" rows="2" cols="60" tabindex="0"><?php if (isset($_GET['status'])) echo $_GET['status']; ?></textarea>
			</div>
			<label class="post-error" for="posttext" id="posttext_error"></label>  
			<div class="postrow">

			<?php if (environment('categories')) : ?>
			<input type="text" name="tags" id="tags" tagindex="1" autocomplete="off" value="<?php _e($txt['postform_tagit']); ?>" onfocus="this.value=(this.value=='<?php _e( $txt['postform_tagit'] ); ?>') ? '' : this.value;" onblur="this.value=(this.value=='') ? '<?php _e( $txt['postform_tagit'] ); ?>' : this.value;"/><?php else : ?>
			<input type="hidden" name="tags" id="tags" />
			<?php endif; ?>
			<div id="postsubmit"><div style="float:left;	font-family: Tahoma, sans-serif;
				font-size: 18px;" id="tweetcounter">
				</div>	 <div id="shorturl" 	style="float:left;	font-family: Tahoma, sans-serif;
						font-size: 14px;"><p>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Add:&nbsp; <a href="JavaScript:inline_shorturl();">Link</a>
					<?php if (environment('uploads')) : ?>
					&nbsp; <a href="<?php url_for(array('resource'=>'posts','action'=>'upload')); ?>">File</a>
			  	<?php endif; ?>
					</p></div>
				<input id="submit" type="submit" value="<?php _e($txt['postform_postit']); ?>" />
			</div>
			</div>
			<span class="progress" id="ajaxActivity"><img src="<?php bloginfo('template_directory'); ?>/i/indicator.gif" alt="Loading..." /></span>
		</div>
	</form>
		<div class="clear"></div>
	<?php if (environment('uploads')) : ?>	
		<?php if (environment('use_uploadify')) : ?>
		<form action="<?php bloginfo( 'url' ); ?>">
		<div id="fileUpload">You have a problem with your javascript</div>

	</form>
	<?php else : ?>
	
  <?php endif; ?>
   
  <?php endif; ?>
		 
		
</div> <!-- // postbox -->




