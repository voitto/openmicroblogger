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
<li id="nav_find">
<a href="<?php echo ''; ?>" title="<?php echo $txt['header_find_people']; ?>"><?php echo $txt['header_find_people']; ?></a>
</li>
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



<div id="wrapper">
	
	<?php get_sidebar( ); ?>