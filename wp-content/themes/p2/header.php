<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?><?php global $request; if (environment('facebookKey') && $request->action == 'email') echo 'xmlns:fb="http://www.facebook.com/2008/fbml"'; ?>>
<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
<title><?php wp_title(); ?> <?php bloginfo('name'); ?></title>
<meta name="generator" content="WordPress.com" />
<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
<?php if (profile_setting('background_image')) : ?>
<style type="text/css">
  body {
    background: url('<?php echo profile_setting('background_image'); ?>') fixed <?php if (!profile_setting('background_tile')) echo 'no-'; ?>repeat top left;
  }
</style>
<?php endif; ?>
<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
<?php if ($request->action == 'email' && environment('facebookKey')) : ?>
<script type="text/javascript">
  function facebook_onlogin() {
    window.location='<?php url_for('facebook_login'); ?>';
  }
</script>
<?php endif; ?>
<?php 
wp_head(); 
include 'wp-content/language/lang_chooser.php'; //Loads the language-file
?>


<?php if (defined('REALTIME_HOST') && REALTIME_HOST ) : ?>
  <script type="text/javascript" src="http://<?php echo REALTIME_HOST; ?>/meteor.js"></script>
<?php endif; ?>


<?php if (environment('use_uploadify')) : ?>
<link rel="stylesheet" href="<?php base_path(); ?>resource/uploadify.css" type="text/css" />
<script type="text/javascript" src="<?php base_path(); ?>resource/jquery.uploadify.js"></script>
<script type="text/javascript">
$(document).ready(function() {
		$("#fileUpload").fileUpload({
		'uploader': '<?php base_path(); ?>resource/uploader.swf',
		'cancelImg': '<?php base_path(); ?>resource/cancel.png',
		'script': $("#fileUpload").parents("form").attr("action"),
		'folder': 'cache',
		'multi': false,
		'displayData': 'speed'
	});
});
</script>
<?php endif; ?>

<script src="<?php base_path(); ?>resource/jeditable/jquery.jeditable.js" type="text/javascript"></script>
  <script type="text/javascript">

  var lasttweet = '';

  function content_changed(data){
    if (lasttweet == data['html']){
      return false;
		}
    lasttweet = data['html'];
    return true;
  }

	function render_a_tweet(data){
		var tweet = '';
		var avsize="48";
		if (data['comment'] == 1){
			avsize="32";
			tweet = tweet + '	<li style="clear:both;margin-left:40px;padding:0px;">';
		} else {
	  	tweet = tweet + '<hr />';
  	}
		tweet = tweet + '	<div>';
		tweet = tweet + '	<div class="tweet_avatar">';
		tweet = tweet + '		<a href="'+data['profile_url']+'">';
		tweet = tweet + '		  <img src="'+data['avatar']+'" height="'+avsize+'" width="'+avsize+'" border="0">';
		tweet = tweet + '		</a>';
		tweet = tweet + '	</div>';

		tweet = tweet + '	<div class="tweet_content">';
		tweet = tweet + '	<a href="'+data['profile_url']+'" title="'+data['name']+'">'+data['nickname']+'</a>';
		tweet = tweet + '	<span>';
		tweet = tweet + '		'+data['tweet'];
		tweet = tweet + '	</span>';
		tweet = tweet + '	<div class="tweet_info">';
		tweet = tweet + '		<a href="'+data['link']+'">';
		tweet = tweet + '			<span>'+data['created']+'</span>';
		tweet = tweet + '		</a>';
		tweet = tweet + '		<span>from ';
		tweet = tweet + '			<a href="">web</a>';
		tweet = tweet + '		</span>';
		tweet = tweet + '	</div>';
		tweet = tweet + '</div>';
		tweet = tweet + '<div class="tweet_actions">';
		tweet = tweet + '	<div>';
		tweet = tweet + '		<a id="favorite" title="favorite this tweet">&nbsp;&nbsp;</a>';
		//tweet = tweet + '		'; in reply to
		tweet = tweet + '</div>';
		tweet = tweet + '</div>';
		if (data['comment'] == 1) {
			tweet = tweet + '</li>';
		} else {

			tweet = tweet + '<ul id="commentcontent-'+data['id']+'" style="list-style:none;">';
			tweet = tweet + '</ul>';

		}
		return tweet;
	}

    function inline_comment(postid,parentid) {
      var cdiv = '#commentcontent-'+postid;
      var submit_to = '<?php echo $request->url_for(array(
  'resource'  =>  'posts'
)); ?>';
      $(cdiv).append('<p class="editable_comment" id="'+postid+'editable"></p>');
      $('#'+postid+'editable').editable(submit_to, { 
          indicator   : "<img src=\''. base_path(true).'resource/jeditable/indicator.gif\'>",
          submitdata  : function() {
            return {"method":"post","parent_id":parentid};
          },
          name        : "post[title]",
          type        : "textarea",
          noappend    : "true",
          submit      : "OK",
          tooltip     : "Click to edit...",
          cancel      : "Cancel",
          callback  : function(value, settings) {
	          $('#'+postid+'editable').remove();
            return(value);
          }
      });
      $('#'+postid+'editable').trigger('click');
    }
    function inline_shorturl() {
      var submit_to = '<?php echo $request->base; ?>';
      $("#shorturl").html('<p class="editable_comment" id="shorten"></p>');
      $("#shorten").editable(submit_to, { 
          indicator   : "<img src='<?php base_path(); ?>resource/jeditable/indicator.gif'>",
          submitdata  : function() {
            return {};
          },
          name        : "ajax_shorten",
          type        : "textarea",
          noappend    : "true",
          submit      : "OK",
          tooltip     : "",
          cancel      : "Cancel",
          callback  : function(value, settings) {
            $("#shorturl").html('<p>Add:&nbsp; <a href="JavaScript:inline_shorturl();">Link</a></p>');
            $('#posttext').text(value);
            return(value);
          }
      });
      $("#shorten").trigger('click');
    }
  </script>
<style type="text/css">
  #shorten {
    width:380px;
    height:20px;
    padding-bottom:35px;
  }
  .editable_comment {
    width:380px;
    height:40px;
    padding-bottom:35px;
  }
</style>

	</head>
<body<?php if(is_single()) echo ' class="single"'; ?>>

<div id="notify"></div>

<div id="header">
	<div class="sleeve">
		<h1><a href="<?php bloginfo( 'url' ); ?>"><?php bloginfo( 'name' ); ?></a></h1>
			<small><?php if(get_bloginfo('description')) : ?><?php bloginfo( 'description' ); ?><?php else : ?>&nbsp;<?php endif; ?></small>

	</div>
<div id="header-round">
<ul class="omb_nav">
<li id="nav_home">
<a href="<?php base_url(); ?>" title="<?php echo $txt['header_home']; ?>"><?php echo $txt['header_home']; ?></a>
</li>
<?php if (signed_in()) : ?>

<?php
$profile= get_profile();
?>



<li id="nav_profile">
<a href="<?php echo $request->url_for(array("resource"=>$profile->nickname)); ?>" title="<?php echo $txt['header_profile']; ?>"><?php echo $txt['header_profile']; ?></a>
</li>
<?php if (environment('findpeople')) : ?>
<li id="nav_find">
<a href="<?php echo ''; ?>" title="<?php echo $txt['header_find_people']; ?>"><?php echo $txt['header_find_people']; ?></a>
</li>
<?php endif; ?>
<li id="nav_settings">
<a href="<?php echo $request->url_for(array("resource"=>$profile->nickname))."/settings"; ?>" title="<?php echo $txt['header_settings']; ?>"><?php echo $txt['header_settings']; ?></a>
</li>
<?php if (member_of('administrators')) : ?>
  <li id="nav_admin">
  <a href="<?php echo $request->url_for(array("resource"=>"admin")); ?>" title="<?php echo $txt['header_admin']; ?>"><?php echo $txt['header_admin']; ?></a>
  </li>
<?php endif; ?>
<li id="nav_help">
<a href="<?php echo ''; ?>" title="<?php echo $txt['header_help']; ?>"><?php echo $txt['header_help']; ?></a>
</li>
<li id="nav_logout">
<a href="<?php url_for(array('resource'=>'openid_logout')); ?>" title="<?php echo $txt['header_sign_out']; ?>"><?php echo $txt['header_sign_out']; ?></a>
</li>

<?php else : ?>

<li id="nav_help">
<a href="<?php echo ''; ?>" title="<?php echo $txt['header_help']; ?>"><?php echo $txt['header_help']; ?></a>
</li>
<li id="nav_login">
<a href="<?php url_for(array('resource'=>'email_login')); ?>" title="<?php echo $txt['header_sign_in']; ?>"><?php echo $txt['header_sign_in']; ?></a>
</li>
<li id="nav_reg">
<a href="<?php url_for(array('resource'=>'register')); ?>" title="<?php echo $txt['header_register']; ?>"><?php echo $txt['header_register']; ?></a>
</li>
  
<?php endif; ?>
</ul>

</div>
</div>


<div id="wrapper">
	
	<?php get_sidebar( ); ?>