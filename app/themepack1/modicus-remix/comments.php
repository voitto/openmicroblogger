<?php // Do not delete these lines
	if ('comments.php' == basename($_SERVER['SCRIPT_FILENAME']))
		die ('Please do not load this page directly. Thanks!');

	if (!empty($post->post_password)) { // if there's a password
		if ($_COOKIE['wp-postpass_' . COOKIEHASH] != $post->post_password) {  // and it doesn't match the cookie
			?>

<p class="nocomments">This post is password protected. Enter the password to view comments.
<p>
  <?php
			return;
		}
	}

	/* This variable is for alternating comment background */
	$oddcomment = 'alt';
?>
  <!-- You can start editing here. -->
  <?php if ($comments) : ?>
<h2>Comments (
  <?php comments_number('Be the First', '1 Comment', '%' );?>
  )</h2>
<?php foreach ($comments as $comment) : ?>
<?php if (get_comment_type() != "comment"){ ?>
<div class="<?php echo $oddcomment; ?> bubble" id="comment-<?php comment_ID() ?>">
  <?php comment_text() ?>
  <cite>
  <?php comment_author_link() ?>
  added these pithy words on <a href="#comment-<?php comment_ID() ?>" title="">
  <?php comment_date('M d y') ?>
  at
  <?php comment_time() ?>
  </a>
  <?php edit_comment_link('e','',''); ?>
  </cite>
  <?php if ($comment->comment_approved == '0') : ?>
  <em>Your comment is awaiting moderation.</em>
  <?php endif; ?>
</div>
<?php } ?>
<?php /* Changes every other comment to a different class */
		if ('alt' == $oddcomment) $oddcomment = '';
		else $oddcomment = 'alt';
	?>
<?php endforeach; /* end for each comment */ ?>
<?php foreach ($comments as $comment) : ?>
<?php if (get_comment_type() == "comment"){ ?>
<div class="<?php echo $oddcomment; ?> bubble" id="comment-<?php comment_ID() ?>">
  <blockquote>
    <?php comment_text() ?>
  </blockquote>
  <cite>
  <?php comment_author_link() ?>
  added these pithy words on <a href="#comment-<?php comment_ID() ?>" title="">
  <?php comment_date('M d y') ?>
  at
  <?php comment_time() ?>
  </a>
  <?php edit_comment_link('e','',''); ?>
  </cite>
  <?php if ($comment->comment_approved == '0') : ?>
  <em>Your comment is awaiting moderation.</em>
  <?php endif; ?>
</div>
<?php } ?>
<?php /* Changes every other comment to a different class */
		if ('alt' == $oddcomment) $oddcomment = '';
		else $oddcomment = 'alt';
	?>
<?php endforeach; /* end for each comment */ ?>
<?php else : // this is displayed if there are no comments so far ?>
<?php if ('open' == $post->comment_status) : ?>
<!-- If comments are open, but there are no comments. -->
<?php else : // comments are closed ?>
<!-- If comments are closed. -->
<p class="nocomments">Comments are closed.</p>
<?php endif; ?>
<?php endif; ?>
<?php if ('open' == $post->comment_status) : ?>
<a name="respond"></a>
<h2>Add a Comment</h2>
<div class="bluebox">
  <?php if ( get_option('comment_registration') && !$user_ID ) : ?>
  <p>You must be <a href="<?php echo get_option('siteurl'); ?>/wp-login.php?redirect_to=<?php the_permalink(); ?>">logged in</a> to post a comment. </p>
</div>
<?php else : ?>
<form action="<?php echo get_option('siteurl'); ?>/wp-comments-post.php" method="post" id="commentform">
  <?php if ( $user_ID ) : ?>
  <p>Logged in as <a href="<?php echo get_option('siteurl'); ?>/wp-admin/profile.php"><?php echo $user_identity; ?></a>. <a href="<?php echo get_option('siteurl'); ?>/wp-login.php?action=logout" title="Log out of this account">Logout &raquo;</a> </p>
  <?php else : ?>
  <p>
    <input type="text" class="textinput" name="author" id="author" value="<?php echo $comment_author; ?>" size="22" tabindex="1" />
    <label for="author"><small>Name
    <?php if ($req) echo "(required)"; ?>
    </small></label>
  </p>
  <p>
    <input type="text" class="textinput" name="email" id="email" value="<?php echo $comment_author_email; ?>" size="22" tabindex="2" />
    <label for="email"><small>Mail (will not be published)
    <?php if ($req) echo "(required)"; ?>
    </small></label>
  </p>
  <p>
    <input type="text" class="textinput" name="url" id="url" value="<?php echo $comment_author_url; ?>" size="22" tabindex="3" />
    <label for="url"><small>Website</small></label>
  </p>
  <?php endif; ?>
  <textarea name="comment" class="textinput" id="comment" tabindex="4"></textarea>
  <br />
  <small><strong>XHTML:</strong> You can use these tags: <?php echo allowed_tags(); ?></small><br />
  <br />
  <input name="submit" type="image" src="<?php bloginfo('template_url'); ?>/images/speak.png" id="submit" tabindex="5" value="Submit Comment" />
  <input type="hidden" name="comment_post_ID" value="<?php echo $id; ?>" />
  <?php do_action('comment_form', $post->ID); ?>
</form>
</div>
<?php endif; // If registration required and not logged in ?>
<?php endif; // if you delete this the sky will fall on your head ?>
