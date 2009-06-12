<?php
include 'wp-content/language/lang_chooser.php'; //Loads the language-file
if( 'comments.php' == basename($_SERVER['SCRIPT_FILENAME'] ) )
	die( 'Please do not load this page directly. Thanks!' );

if ( post_password_required() ) { ?>
	<p class="nocomments"><?php echo $txt['comments_post_pw_prot']; ?></p>
<?php
	return;
} // if post_password_required

if ( have_comments ) {
	
	echo "<div class=\"commentlist\">\n";
	wp_list_comments(array('callback' => 'prologue_comment'));
	echo "</div>\n";
	if ( get_option('page_comments') && (get_query_var('cpage') > 1 || get_query_var('cpage') < get_comment_pages_count() ) ) {
		?> <div class="navigation"><p> <?php
		previous_comments_link(); 
		?> | <?php
		next_comments_link();
		?> </p></div><?php
	}
} // if comments

if( 'open' == $post->comment_status ) {
?>
<div id="respond">

<h3><?php echo $txt['comments_reply']; ?><small id="cancel-comment-reply"><?php echo cancel_comment_reply_link() ?></small></h3>

<?php
if ( get_option('comment_registration') && !$user_ID ) {
?>

<p><?php echo $txt['comments_you_must_be']; ?><a href="<?php echo get_option('siteurl'); ?>/wp-login.php?redirect_to=<?php the_permalink(); ?>" title="Log in"><?php echo $txt['comments_logged_in']; ?></a><?php echo $txt['comments_to_post_a_comment']; ?>.</p>

<?php
} // if option comment_registration and not user_ID
else {
?>

<form id="commentform" action="<?php echo get_option( 'siteurl' ); ?>/wp-comments-post.php" method="post">

<div class="form"><textarea id="comment" name="comment" cols ="45" rows="3"></textarea></div>

<?php 
if( $user_ID ) { 
?>

<p><?php echo $txt['comments_logged_in_as']; ?><a href="<?php echo get_option( 'siteurl' ); ?>/wp-admin/profile.php"><?php echo $user_identity; ?></a>.  <a href="<?php echo get_option( 'siteurl' ); ?>/wp-login.php?action=logout" title="Log out"><?php echo $txt['comments_logout']; ?>&rarr;</a></p>

<?php 
} // if user_ID 
else { 
?>

<table>
	<tr>
		<td>

<label for="author"><?php echo $txt['comments_name']; ?><em><?php echo $txt['comments_required']; ?></em></label>
<div class="form"><input id="author" name="author" type="text" value="<?php echo $comment_author; ?>" /></div>

		</td><td>

<label for="email"><?php echo $txt['comments_email']; ?><em><?php echo $txt['comments_required']; ?></em></label>
<div class="form"><input id="email" name="email" type="text" value="<?php echo $comment_author_email; ?>"  /></div>

		</td><td class="last-child">

<label for="url"><?php echo $txt['comments_website']; ?></label>
<div class="form"><input id="url" name="url" type="text" value="<?php echo $comment_author_url; ?>"  /></div>

		</td>
	</tr>
</table>
<?php } // else user_ID ?>

<div><input id="submit" name="submit" type="submit" value="<?php echo $txt['comments_post_comment']; ?>" /><?php comment_id_fields(); ?></div>

</form>
</div>
<?php
} // else option comment_registration and not user_ID
} // if open comment_status
