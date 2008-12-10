<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head profile="http://gmpg.org/xfn/11">

	 <title>
		<?php bloginfo('name'); ?>
		<?php if(is_home()) { ?>
		- <?php bloginfo('description'); ?>
		<?php } ?>
		<?php if(is_single()) { ?>
		<?php } ?> <?php wp_title(); ?>
		<?php if(is_404()) { ?>
		- 404 Error! Page Not Found
		<?php } ?>
		<?php if(is_search()) { ?>
		- Search Results for: <?php echo wp_specialchars($s, 1); ?>
		<?php } ?>
	 </title>
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
	<style type="text/css" media="screen">
		@import url( <?php bloginfo('stylesheet_url'); ?> );
	</style>
	<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/javascript/p7exp.js"></script>
	<!--[if lte IE 7]> <style>#menuwrapper, #p7menubar ul a {height: 1%;} a:active {width: auto;} </style> <![endif]-->
	<?php if(!is_home() ) { ?> <link rel="stylesheet" type="text/css" media="print" href="<?php bloginfo('template_url'); ?>/print.css" /> <?php } ?>
	<link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="<?php bloginfo('rss2_url'); ?>" />	
	<link rel="alternate" type="text/xml" title="RSS .92" href="<?php bloginfo('rss_url'); ?>" />	
	<link rel="alternate" type="application/atom+xml" title="Atom 0.3" href="<?php bloginfo('atom_url'); ?>" />	
	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />	
	<?php wp_get_archives('type=monthly&format=link'); ?>	
	<?php wp_head(); ?>
</head>
<body>

<div id="wrapper">

	<!-- Header -->
	<div id="header">

		<!-- Logo -->
		<div id="headerleft">
			<h1><a href="<?php bloginfo('url'); ?>" title="<?php bloginfo('description'); ?>"><?php bloginfo('name'); ?></a></h1>
		</div>
		
		<!-- Searchbox and date -->
		<div id="headerright">
			<script src="<?php bloginfo('template_url'); ?>/javascript/date.js" type="text/javascript"></script>
			<?php include (TEMPLATEPATH . '/searchform.php'); ?>
		</div>
		
		<!-- Header Navigation -->
		<div id="navigation">
			<ul id="p7menubar">
				<li<?php if(is_home() || is_404() || is_single() || is_category() || is_day() || is_month() || is_year() || is_search() ) { ?> class="current_page_item"<?php } ?>><a href="<?php bloginfo('home'); ?>">Home</a></li>
				<?php wp_list_pages('sort_column=menu_order&title_li='); ?>
			</ul>
			<ul id="feeds">
					<li><a href="feed:<?php bloginfo('rss2_url'); ?>">Feed <span class="rss">(RSS)</span></a></li>
			</ul>
			
			<div style="clear:both"></div>
		</div>
		
	</div>
	<!-- /Header -->