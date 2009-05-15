<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
<title><?php wp_title(); ?> <?php bloginfo('name'); ?></title>
<meta name="generator" content="WordPress.com" /> 
<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

<?php 
wp_head(); 
include 'wp-content/language/lang_chooser.php'; //Loads the language-file
?>


<?php if (REALTIME_HOST) : ?>
  <script type="text/javascript" src="http://<?php echo REALTIME_HOST; ?>/meteor.js"></script>
<?php endif; ?>


	</head>
<body onLoad="JavaScript:setMaxLength();"<?php if(is_single()) echo ' class="single"'; ?>>

<div id="notify"></div>

<div id="help">
	<dl class="directions">
		<dt>c</dt><dd><?php echo $txt['header_compose_new_post']; ?></dd>
		<dt>j</dt><dd><?php echo $txt['header_next_post_comment']; ?></dd>
		<dt>k</dt> <dd><?php echo $txt['header_previous_post_comment']; ?></dd>
		<dt>r</dt> <dd><?php echo $txt['header_reply']; ?></dd>
		<dt>e</dt> <dd><?php echo $txt['header_edit']; ?></dd>
		<dt>o</dt> <dd><?php echo $txt['header_show_hide_comments']; ?></dd>
		<dt>t</dt> <dd><?php echo $txt['header_go_to_top']; ?></dd>
		<dt>esc</dt> <dd><?php echo $txt['header_cancel']; ?></dd>
	</dl>
</div>

<div id="header">
	<div class="sleeve">
		<h1><a href="<?php bloginfo( 'url' ); ?>"><?php bloginfo( 'name' ); ?></a></h1>
			<?php if(get_bloginfo('description')) : ?><small><?php bloginfo( 'description' ); ?></small><?php endif; ?>

	</div>
<ul class="omb_nav" style="float:right;padding:10px;margin:0;color:#fff;font-size:14px;font-family: arial, helvetica, sans-serif;white-space:nowrap;list-style-type:none">
<li id="nav_home" style="display:inline;padding:5px;">
<a href="<?php base_url(); ?>" title="Home"><?php echo $txt['header_home']; ?></a>
</li>
<?php if (signed_in()) : ?>

<?php
$profile= get_profile();
?>



<li id="nav_profile" style="display:inline;padding:5px;">
<a href="<?php echo $request->url_for(array("resource"=>$profile->nickname)); ?>" title="Profile"><?php echo $txt['header_profile']; ?></a>
</li>
<li id="nav_find" style="display:inline;padding:5px;">
<a href="<?php echo ''; ?>" title="Find People"><?php echo $txt['header_find_people']; ?></a>
</li>
<li id="nav_settings" style="display:inline;padding:5px;">
<a href="<?php echo $request->url_for(array("resource"=>$profile->nickname))."/settings"; ?>" title="Settings"><?php echo $txt['header_settings']; ?></a>
</li>
<li id="nav_help" style="display:inline;padding:5px;">
<a href="<?php echo ''; ?>" title="Help"><?php echo $txt['header_help']; ?></a>
</li>
<li id="nav_logout" style="display:inline;padding:5px;">
<a href="<?php url_for(array('resource'=>'openid_logout')); ?>" title="Sign out"><?php echo $txt['header_sign_out']; ?></a>
</li>

<?php else : ?>

<li id="nav_help" style="display:inline;padding:5px;">
<a href="<?php echo ''; ?>" title="Help"><?php echo $txt['header_help']; ?></a>
</li>
<li id="nav_login" style="display:inline;padding:5px;">
<a href="<?php url_for(array('resource'=>'email_login')); ?>" title="Sign in"><?php echo $txt['header_sign_in']; ?></a>
</li>
<li id="nav_reg" style="display:inline;padding:5px;">
<a href="<?php url_for(array('resource'=>'register')); ?>" title="Register"><?php echo $txt['header_register']; ?></a>
</li>
  
<?php endif; ?>
</ul>

</div>



<div id="wrapper">
	
	<?php get_sidebar( ); ?>