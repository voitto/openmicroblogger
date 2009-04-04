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
</div>

<div id="wrapper">
	
	<?php get_sidebar( ); ?>