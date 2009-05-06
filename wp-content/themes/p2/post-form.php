<?php
$user			= get_userdata( $current_user->ID );
$first_name		= attribute_escape( $user->first_name );
?>

<div id="postbox">
	<form id="new_post" name="new_post" method="post" action="<?php bloginfo( 'url' ); ?>">
		<input type="hidden" name="action" value="post" />
		<?php wp_nonce_field( 'new-post' ); ?>
		<div class="avatar"><?php echo prologue_get_avatar( $user->ID, $user->user_email, 48 ); ?></div>
		<div class="inputarea">
			<label for="posttext"><?php _e('Hi'); ?>, <?php echo $first_name; ?>. <?php _e('Whatcha up to?'); ?></label>
			<div>
				<textarea name="posttext" id="posttext" rows="3" cols="60" tabindex="0" maxlength="140"></textarea>
			</div>
			<label class="post-error" for="posttext" id="posttext_error"></label>  
			<div class="postrow">
				
				<?php if (environment('use_tags')) : ?>
			<input type="text" name="tags" id="tags" tagindex="1" autocomplete="off" value="<?php _e('Tag it'); ?>" onfocus="this.value=(this.value=='<?php _e( 'Tag it' ); ?>') ? '' : this.value;" onblur="this.value=(this.value=='') ? '<?php _e( 'Tag it' ); ?>' : this.value;"/><?php else : ?>
			 
			 <input type="hidden" name="tags" id="tags" />
			<?php endif; ?>
				<input id="submit" type="submit" value="<?php _e('Post it'); ?>" />
			</div>
			<span class="progress" id="ajaxActivity"><img src="<?php bloginfo('template_directory'); ?>/i/indicator.gif" alt="Loading..." /></span>
		</div>
		<div class="clear"></div>
	</form>
</div> <!-- // postbox -->