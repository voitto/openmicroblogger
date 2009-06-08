<?php
include 'wp-content/language/lang_chooser.php'; //Loads the language-file
$user			= get_userdata( $current_user->ID );
$first_name		= attribute_escape( $user->first_name );
?>

<div id="postbox">
	<form id="new_post" name="new_post" method="post" action="<?php bloginfo( 'url' ); ?>">
		<input type="hidden" name="action" value="post" />
		<?php wp_nonce_field( 'new-post' ); ?>
		<div class="avatar"><?php echo prologue_get_avatar( $user->ID, $user->user_email, 48 ); ?></div>
		<div class="inputarea">
			<label for="posttext"><?php _e($txt['postform_greeting']); ?>, <?php echo $first_name; ?>. <?php _e($txt['postform_whatsup']); ?></label>
			<div>
				<textarea name="posttext" id="posttext" rows="3" cols="60" tabindex="0" maxlength="140"></textarea>
			</div>
			<label class="post-error" for="posttext" id="posttext_error"></label>  
			<div class="postrow">

			<?php if (environment('categories')) : ?>
			<input type="text" name="tags" id="tags" tagindex="1" autocomplete="off" value="<?php _e($txt['postform_tagit']); ?>" onfocus="this.value=(this.value=='<?php _e( $txt['postform_tagit'] ); ?>') ? '' : this.value;" onblur="this.value=(this.value=='') ? '<?php _e( $txt['postform_tagit'] ); ?>' : this.value;"/><?php else : ?>
			<input type="hidden" name="tags" id="tags" />
			<?php endif; ?>
				<input id="submit" type="submit" value="<?php _e($txt['postform_postit']); ?>" />
			</div>
			<span class="progress" id="ajaxActivity"><img src="<?php bloginfo('template_directory'); ?>/i/indicator.gif" alt="Loading..." /></span>
		</div>
	</form>
		<div class="clear"></div>
		<?php if (environment('use_uploadify')) : ?>
		<form action="<?php bloginfo( 'url' ); ?>">
		<div id="fileUpload">You have a problem with your javascript</div>

	</form>
	<?php else : ?>
		  <p>Add:&nbsp; <a href="<?php url_for(array('resource'=>'posts','action'=>'upload')); ?>">Photos</a></p>
  <?php endif; ?>
</div> <!-- // postbox -->