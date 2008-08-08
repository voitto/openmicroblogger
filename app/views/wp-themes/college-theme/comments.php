<ul class="PostNote">
<?php if ( pings_open() ) : ?><li class="TrackBack">
<a href="<?php trackback_url() ?>" rel="trackback"><?php _e('TrackBack <abbr title="Uniform Resource Identifier">URI</abbr>'); ?></a></li>
<?php endif; ?>
<li class="RSS"><?php comments_rss_link(__('<abbr title="Really Simple Syndication">RSS</abbr> feed for comments on this post')); ?></li>
</ul>
<br />
<br />

<div class="Comments">
<div class="List">
<!-- Start CommentsList-->
<?php // Do not delete these lines
  if ('comments.php' == basename($_SERVER['SCRIPT_FILENAME']))
    die ('Please do not load this page directly. Thanks!');

  if (!empty($post->post_password)) { // if there's a password
    if ($_COOKIE['wp-postpass_' . COOKIEHASH] != $post->post_password) {  // and it doesn't match the cookie
      ?>

      <p class="nocomments">This post is password protected. Enter the password to view comments.<p>

      <?php
      return;
    }
  }

?>

<!-- You can start editing here. -->
<?php if ($comments) : ?>
<h3 id="comments"><?php comments_number('No Response', 'One Response', '% Responses' );?></h3> 

<ol>

<?php foreach ($comments as $comment) : ?>

 <li id="comment-<?php comment_ID() ?>">
 <p class="ListUser"><?php comment_author_link() ?></p>
 <span class="ListDate"><a href="#comment-<?php comment_ID() ?>" title=""><?php comment_time ('d|M|Y') ?></a></span>
 <div class="ListContent">
 <?php comment_text() ?> 
 <?php edit_comment_link('Edit Comment'); ?>
 <?php if ($comment->comment_approved == '0') : ?>
 Your comment is awaiting moderation.
 </div>
 
 <?php endif; ?>  
  
</li>
<?php endforeach; /* end for each comment */ ?>
</ol>

<?php else : // this is displayed if there are no comments so far ?>
<?php if ('open' == $post->comment_status) : ?> 
<!-- If comments are open, but there are no comments. -->
<?php else : // comments are closed ?>
<!-- If comments are closed. -->
<p class="nocomments">Comments are closed.</p>
<?php endif; ?>
<?php endif; ?>
<?php if ('open' == $post->comment_status) : ?><br />
<!-- Ends CommentsList-->
</div>



<!-- Start Comments Form-->
<div class="Form">
<h3 id="respond">Leave a reply</h3> 

<?php if ( get_option('comment_registration') && !$user_ID ) : ?>
<p>You must be <a href="<?php echo get_option('siteurl'); ?>/wp-login.php?redirect_to=<?php the_permalink(); ?>">logged in</a> to post a comment.</p>
<?php else : ?>

<div class="FormTop"></div><form action="<?php echo get_option('siteurl'); ?>/wp-comments-post.php" method="post" id="commentform">
<?php if ( $user_ID ) : ?>
<p>Logged in as <a href="<?php echo get_option('siteurl'); ?>/wp-admin/profile.php"><?php echo $user_identity; ?></a>. <a href="<?php echo get_option('siteurl'); ?>/wp-login.php?action=logout" title="<?php _e('Log out of this account') ?>">Logout &raquo;</a></p>
<?php else : ?>

<p>
<label for="author">
<input type="text" name="author" id="author" value="<?php echo $comment_author; ?>" tabindex="1" class="TextField" style="width: 210px;" />Name <small><?php if ($req) _e('(required)'); ?></small></label>
</p>
    
<p>
<label for="email">
<input type="text" name="email" id="email" value="<?php echo $comment_author_email; ?>" tabindex="2" class="TextField" style="width: 210px;" />E-mail <small><?php if ($req) _e('(required)'); ?></small></label>
</p>
    
<p>
<label for="url">
<input type="text" name="url" id="url" value="<?php echo $comment_author_url; ?>" tabindex="3" class="TextField" style="width: 210px;" /><abbr title="Uniform Resource Identifier">URI</abbr></label>
</p>

<?php endif; ?>
<p>
<textarea name="comment" id="comment" rows="10" tabindex="4" class="TextArea" style="width: 100%;"></textarea>
</p>

<p>
<input name="SubmitComment" type="image" class="SubmitComment"  title="Post Your Comment" src="<?php bloginfo('template_url'); ?>/images/SubmitComment.png" alt="Post Your Comment" style="width: 184px;">
<input type="hidden" name="comment_post_ID" value="<?php echo $id; ?>" >
</p>
<?php do_action('comment_form', $post->ID); ?>
<div class="FormCorner"></div> 
</form>
<?php endif; // If registration required and not logged in ?>
<?php endif; // if you delete this the sky will fall on your head ?>
</div>
</div>
