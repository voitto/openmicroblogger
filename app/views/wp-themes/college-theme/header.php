<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head profile="http://gmpg.org/xfn/11">
<?php
$theTitle=wp_title(" - ", false);
if($theTitle != "") {
?>
<title><?php echo wp_title("",false); ?></title>
<?php }else{ ?>
<title><?php bloginfo('name'); ?></title>
<?php } ?>

<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<meta http-equiv="pragma" content="no-cache" />
<meta http-equiv="cache-control" content="no-cache" />
<link rel="stylesheet" type="text/css" href="<?php bloginfo('stylesheet_url'); ?>" />
<link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="<?php bloginfo('rss2_url'); ?>" />
<link rel="alternate" type="text/xml" title="RSS .92" href="<?php bloginfo('rss_url'); ?>" />
<link rel="alternate" type="application/atom+xml" title="Atom 0.3" href="<?php bloginfo('atom_url'); ?>" />
<link rel="shortcut icon" href="<?php bloginfo('template_url'); ?>/favicon.ico" type="image/x-icon" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/imghover.js"> </script>
<script type="text/javascript" src="<?php bloginfo('template_url'); ?>/tabs.js"> </script>
<?php wp_head(); ?>
</head>

<body>
<div id="wrapper">
  <h1><a href="<?php echo get_option('home'); ?>/"><?php bloginfo('name'); ?></a></h1>
  <p class="Desc"><?php bloginfo('description'); ?></p>
  <div id="main-content">
    <div id="content">
      <ul id="navigation">
        <li>| <a href="<?php echo get_option('home'); ?>/">home</a> |</li>
        <li><a href="<?php echo get_option('home'); ?>/about/">about</a> |</li>
        <li><a href="<?php bloginfo('rss_url'); ?>">rss</a> |</li>
      </ul>
