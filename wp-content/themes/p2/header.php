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

<?php wp_head(); ?>


<?php if (REALTIME_HOST) : ?>
  <script type="text/javascript" src="http://<?php echo REALTIME_HOST; ?>/meteor.js"></script>
<?php endif; ?>


	</head>
<body onLoad="JavaScript:setMaxLength();"<?php if(is_single()) echo ' class="single"'; ?>>

<div id="notify"></div>

<div id="help">
	<dl class="directions">
		<dt>c</dt><dd> compose new post</dd>
		<dt>j</dt><dd>next post/next comment</dd>
		<dt>k</dt> <dd>previous post/previous comment</dd>
		<dt>r</dt> <dd>reply</dd>
		<dt>e</dt> <dd>edit</dd>
		<dt>o</dt> <dd>show/hide comments</dd>
		<dt>t</dt> <dd>go to top</dd>
		<dt>esc</dt> <dd>cancel</dd>
	</dl>
</div>

<div id="header">
	<div class="sleeve">
		<h1><a href="<?php bloginfo( 'url' ); ?>"><?php bloginfo( 'name' ); ?></a></h1>
			<?php if(get_bloginfo('description')) : ?><small><?php bloginfo( 'description' ); ?></small><?php endif; ?>

	</div>
<ul class="omb_nav" style="float:right;padding:10px;margin:0;color:#fff;font-size:14px;font-family: arial, helvetica, sans-serif;white-space:nowrap;list-style-type:none">
<li id="nav_home" style="display:inline;padding:5px;">
<a href="<?php base_url(); ?>" title="Home">Home</a>
</li>
<?php if (signed_in()) : ?>

<?php
$profile= get_profile();
?>



<li id="nav_profile" style="display:inline;padding:5px;">
<a href="<?php echo $profile->profile_url; ?>" title="Profile">Profile</a>
</li>
<li id="nav_find" style="display:inline;padding:5px;">
<a href="<?php echo ''; ?>" title="Find People">Find People</a>
</li>
<li id="nav_settings" style="display:inline;padding:5px;">
<a href="<?php echo $request->url_for(array("resource"=>$profile->nickname))."/settings"; ?>" title="Settings">Settings</a>
</li>
<li id="nav_help" style="display:inline;padding:5px;">
<a href="<?php echo ''; ?>" title="Help">Help</a>
</li>
<li id="nav_logout" style="display:inline;padding:5px;">
<a href="<?php url_for(array('resource'=>'openid_logout')); ?>" title="Sign out">Sign out</a>
</li>

<?php else : ?>

<li id="nav_help" style="display:inline;padding:5px;">
<a href="<?php echo ''; ?>" title="Help">Help</a>
</li>
<li id="nav_logout" style="display:inline;padding:5px;">
<a href="<?php url_for(array('resource'=>'email_login')); ?>" title="Sign in">Sign in</a>
</li>
  
<?php endif; ?>
</ul>

</div>



<div id="wrapper">
	
	<?php get_sidebar( ); ?>