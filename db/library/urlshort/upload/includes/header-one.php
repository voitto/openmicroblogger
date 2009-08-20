<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>

	<head>
		<title>urlShort</title>
		<link rel="icon" href="<?php include 'install_path.php'; echo $install_path; ?>favicon.ico">
		<link rel=StyleSheet href="<?php include 'install_path.php'; echo $install_path; ?>includes/style.css">
<script language="JavaScript">
function validate(field) {
var valid = "abcdefghijklmnopqrstuvwxyz0123456789"
var ok = "yes";
var temp;
for (var i=0; i<field.value.length; i++) {
temp = "" + field.value.substring(i, i+1);
if (valid.indexOf(temp) == "-1") ok = "no";
}
if (ok == "no") {
alert("You used an invalid character in your custom name. Try again.");
window.location.reload()
field.focus();
field.select();
   }
}
</script>
<script type="text/javascript">
function show(status){
	if(status == "open"){
		document.getElementById("custom").setAttribute("style", "display: ;");
		document.getElementById("open").setAttribute("id", "close");
		document.getElementById("close").setAttribute("style", "display:none ;");
	}else if(status == "close"){
		document.getElementById("custom").setAttribute("style", "display: none;");
		document.getElementById("close").setAttribute("id", "open");
	}
}

</script>
<meta name="keywords" content="url, short, shortening, shortener, url short, urlshort, short url, tinyurl, small url, small, tiny, tiny url, twitter, shorten url, url shortener, url shortening, free, mavrev">
<meta name="description" content="Totally free and permanent shortened URLs by urlShort. A product of Maverick Revolution.">
<meta http-equiv="content-language" content="en">
<meta name="author" content="maverick revolution">
<meta name="robots" content="follow,index">
	</head>