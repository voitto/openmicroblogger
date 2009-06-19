<?php
$user      = get_userdata( $current_user->ID );
$first_name    = attribute_escape( $user->first_name );
?>

<div id="postbox">
  <form class="a_form" enctype="multipart/form-data" id="new_post" name="new_post" method="post" action="<?php bloginfo( 'url' ); ?>">
    <input type="hidden" name="action" value="post" />
    <input type="hidden" name="profile_id" value="<?php echo get_profile_id(); ?>" />
    <?php wp_nonce_field( 'new-post' ); ?>
    
    <?php echo prologue_get_avatar( $user->ID, $user->user_email, 48 ); ?>
    
    <label for="posttext">Hi, <?php echo $first_name; ?>. Whatcha up to?</label>
    <textarea name="posttext" id="posttext" rows="3" cols="60" maxlength="140"></textarea>
    <?php if (environment('uploads')) : ?>
    <label for="postfile">File <span style="font-size: .8em;">(optional)</span></label>
    <input name="MAX_FILE_SIZE" value="65536000" type="hidden" />
    <input name="postfile" id="postfile" type="file" />
    <?php endif; ?>
    <label for="link">Link <span style="font-size: .8em;">(optional)</span></label>
    <input id="link" name="link[href]" />
    
    <label for="tags">Tag it <span style="font-size: .8em;">(optional)</span></label>
    <input type="text" name="tags" id="tags" autocomplete="off" />
    <input type="hidden" name="post[local]" value="1" />
    
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input id="submit" type="submit" value="Post it" />
  </form>
</div> <!-- // postbox -->
