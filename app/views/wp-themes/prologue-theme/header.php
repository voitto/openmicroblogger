<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
  <head profile="http://gmpg.org/xfn/11">
    
    <script type="text/javascript" src="<?php base_url(); ?>resource/jquery-1.2.6.min.js"></script>
    <script type="text/javascript" src="<?php base_url(); ?>resource/jquery.corner.js"></script>
    <script type="text/javascript" src="<?php base_url(); ?>resource/jquery.jqUploader.js"></script>
    <script type="text/javascript" src="<?php base_url(); ?>resource/jquery.flash.js"></script>
    <script type="text/javascript">
$(document).ready(function(){
	$('#postfile').jqUploader({
	  background:'FFFFFF',
	  barColor:'336699',
	  allowedExt:'*.avi; *.jpg; *.jpeg; *.mp3; *.mov',
	  allowedExtDescr: 'Movies, Photos and Songs',
	  validFileMessage: 'Click [Upload]',
	  endMessage: '',
	  hideSubmit: false
	});
});
</script>
    <meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
    <title><?php wp_title(); ?> <?php bloginfo('name'); ?></title>
    <meta name="generator" content="WordPress.com" /> 
    <link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
    <link rel="stylesheet" type="text/css" href="<?php bloginfo('theme_url'); ?>menu.css" />
    <script src="<?php bloginfo('theme_url'); ?>stuHover.js" type="text/javascript"></script>
    <link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>" />
    <link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
    <?php wp_head(); ?>

<?php if ( strstr( $_SERVER['HTTP_USER_AGENT'], 'iPhone' ) ) { ?>
<meta name="viewport" content="width=320" />
<style type="text/css">
#header_img, #sidebar, #postbox .avatar {
  display: none;
}

#wrapper, #main {
  width: 320px;
  padding: 0;
  float: none;
  margin-left: 3px;
}

h1 {
  font-size: 2em;
  font-family: Georgia, "Times New Roman", serif;
  margin-left: 0;
  margin-top: 5px;
  margin-bottom: 10px;
}

h2 {
  font-size: 1.2em;
  font-weight: bold;
  color: #555;
}

#postbox form {
  padding: 5px;
}

#postbox textarea#posttext {
  width: 300px;
  height: 50px;
  border: 1px solid #c6d9e9;
  margin-bottom: 10px;
  padding: 2px;
  font: 1.4em/1.2em "Lucida Grande",Verdana,"Bitstream Vera Sans",Arial,sans-serif;
}
#postbox input#tags,  #postbox input#links,  #commentform #comment {
  font-size: 1.2em;
  padding: 2px;
  border: 1px solid #c6d9e9;
  width: 300px;
  margin-left: 0;
}
#postbox label {
  color: #333;
  display: block;
  font-size: 1.2em;
  margin-bottom: 4px;
  margin-left: 0;
  font-weight: bold;
}

#postbox input#submit {
  font-size: 1.2em;
  margin-left: 250px;
  margin-top: 5px;
}

#main ul {
  list-style: none;
  margin-top: 16px;
  margin-left: 0;
}

#wpcombar {
  display: none;
}
body {
  padding-top: 0;
}
</style>
<?php } ?>



<script type="text/javascript">

  
  function show_page(url) {
    
    $("#main").html("<img src='resource/jeditable/indicator.gif'>");
    
    $.get(url, function(str) {
      $("#main").hide();
      $("#main").html(str);
      $("#main").slideDown("fast");
    });
    
    
    
    
  }
  
</script>

<?php if (get_profile_id() ) : ?>



 <script type="text/javascript">
   
    
function setMaxLength() {
	var x = document.getElementsByTagName('textarea');
	var counter = document.createElement('div');
	counter.className = 'counter';
	for (var i=0;i<x.length;i++) {
		if (x[i].getAttribute('maxlength')) {
			var counterClone = counter.cloneNode(true);
			counterClone.relatedElement = x[i];
			counterClone.innerHTML = '<span>0</span>/'+x[i].getAttribute('maxlength');
			x[i].parentNode.insertBefore(counterClone,x[i].nextSibling);
			x[i].relatedElement = counterClone.getElementsByTagName('span')[0];

			x[i].onkeyup = x[i].onchange = checkMaxLength;
			x[i].onkeyup();
		}
	}
}

function checkMaxLength() {
	var maxLength = this.getAttribute('maxlength');
	var currentLength = this.value.length;
	if (currentLength > maxLength)
		this.relatedElement.className = 'toomuch';
	else
		this.relatedElement.className = '';
	this.relatedElement.firstChild.nodeValue = currentLength;
	// not innerHTML
}


    </script>
    
  </head>
    <body onLoad="JavaScript:setMaxLength();">



<?php else : ?>
  </head>
    <body>
<?php endif; ?>




<div id="wrapper">

<h1><a href="<?php bloginfo( 'url' ); ?>"><?php //bloginfo( 'name' ); ?></a></h1>

<?php
$image = get_header_image( );
if( preg_match( '|there-is-no-image.jpg$|', $image ) !== 1 ) {
?>

<div id="header_img">
<img src="<?php echo $image; ?>" width="726" height="150" alt="" />
</div>

<?php
} // if header image
?>
