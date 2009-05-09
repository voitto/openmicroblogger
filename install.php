<html>
<head>
<title>openmicroblogger - installation</title>
</head>
<body>

<?php

echo "<H1>openmicroblogger - Installation</H1><br />";
$checkfile = "config/config.php";

// Checks if connection to database works.
if (file_exists($checkfile)) {
include "config/config.php";

// If connection works setup stops and sends an information-message. Also checks if folder config has CHMOD 755
$db_check = @new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	if (!mysqli_connect_errno()) {
		echo "<p style='color:green; font-weight:bold'>Congratulations! Established connection to your database successfully.<br />You can now visit your openmicroblogger. Have fun! :-)</p><br />";
	if (is_writable ('config')) {
		echo "<p style='color:orange; font-weight:bold'>For your own security:<br />Set CHMOD of folder /config/ back to 755!!<br /></p>";
                              }
	if (!is_writable ('config')) {
		echo "<p style='color:green; font-weight:bold'>Security-Check: You already set CHMOD of folder /config/ back to 755. Great job!<br /></p>";
                              }
	exit;
                              }
// If there is a config.php in config folder but connection to database fails, this message will appear.
else {
	echo "<br /><p style='color:red; font-weight:bold'>Warning:<br />There is already a config.php in folder /config but connecting to your database failed.<br />If you want to reconfigure your config.php do it manually or delete config.php in folder /config and reload this script (install.php) to setup your database-settings again.</p><br />";
                exit;
      }
                              }


if (file_exists($checkfile)) {
	echo "<br /><p style='color:red; font-weight:bold'>Warning:<br />There is already a config.php in folder /config <br />If your installation works fine, please delete the install.php on your server.<br />If you want to reconfigure your config.php do it manually or delete config.php in folder /config and reload this script.</p><br />";
                              }
//                                                              }

else {
	if (!file_exists($checkfile)) {
// Checks if folders config and cache are writable
		echo "<strong>The following folders have to be writable to install openmicroblogger:</strong><br />";
	if (is_writable ('config')) {
		echo "<p style='color:green; font-weight:bold'>GOOD: /config/ is <strong>writable</strong></p>";
                             }
	else echo "<p style='color:orange; font-weight:bold'>BAD: /config/ is <strong>not writable</strong>. Please set CHMOD to 777</p>";
		if (is_writable ('cache')) {
			echo "<p style='color:green; font-weight:bold'>GOOD: /cache/ is <strong>writable</strong></p>";
                            }
			else echo "<p style='color:orange; font-weight:bold'>BAD: /cache/ is <strong>not writable</strong>. Please set CHMOD to 777</p>";
                             }
      }

// only if folder config and cache are writable setup starts
if ((is_writable ('config')) AND (is_writable ('cache'))) {

// if connection to database works and all required fields are filled out config.php will be written to folder
$db_send_check = @new mysqli(trim($_POST['db_host']), trim($_POST['db_user']), trim($_POST['db_pw']), trim($_POST['db_name']));
if (!mysqli_connect_errno() AND (isset($_POST['db_name'])) AND (isset($_POST['db_user'])) AND (isset($_POST['db_pw']))){

	$db_name = trim($_POST['db_name']);
	$db_user = trim($_POST['db_user']);
	$db_pw = trim($_POST['db_pw']);
	$db_host = trim($_POST['db_host']);
	$db_charset = trim($_POST['db_charset']);
	$db_collate = trim($_POST['db_collate']);
	$pretty_urls = trim($_POST['pretty_urls']);
	if ($pretty_urls == TRUE) { $pretty_urls = "1";} else { $pretty_urls = "0";}
	$pretty_urls_base = trim($_POST['pretty_urls_base']);
	$intranet = trim($_POST['intranet']);
	if ($intranet == TRUE) { $intranet = "1";} else { $intranet = "0";}
	$ping = trim($_POST['ping']);
	if ($ping == FALSE) { $ping = "0";} else { $ping = "1";}
	$cometpush_host = trim($_POST['cometpush_host']);
	$cometpush_port = trim($_POST['cometpush_port']);
	$standard_lang = trim($_POST['standard_lang']);
	if ($standard_lang == 'german') { $standard_lang = "ger";} else if ($standard_lang == 'english') { $standard_lang = "eng";}


///////////////////////////////////////////////////////////////////////////////////////////////////
// content of config.php
///////////////////////////////////////////////////////////////////////////////////////////////////
     $content = "<?php

// database settings \n\n

define(       \"DB_NAME\", \"$db_name\"      ); // name of database \n
define(       \"DB_USER\", \"$db_user\"      ); // user name \n
define(   \"DB_PASSWORD\", \"$db_pw\"      ); // user password \n\n\n

// options\n\n

define(      \"INTRANET\", \"$intranet\"     ); // change to 1 for password login\n
define(          \"PING\", \"$ping\"     ); // change to 0 for silent operation\n
define( \"REALTIME_HOST\", \"$cometpush_host\"      ); // host for comet push\n
define( \"REALTIME_PORT\", \"$cometpush_port\"      ); // port for comet push\n\n\n

// more database settings\n\n

define(       \"DB_HOST\", \"$db_host\"      );\n
define(    \"DB_CHARSET\", \"$db_charset\"  );\n
define(    \"DB_COLLATE\", \"$db_collate\"      );\n\n\n

// pretty URLs setup\n\n
";
if ($pretty_urls == 0) {
     $content .= "
// global \$pretty_url_base;\n
// \$pretty_url_base = \"http://yourwebsite.com\";\n\n\n
";
}
if ($pretty_urls != 0) {
     $content .= "
global \$pretty_url_base;\n
\$pretty_url_base = \"$url_base\";\n\n\n
";
}
     $content .= "
// standard language\n\n
define(    \"STANDARD_LANG\", \"$standard_lang\"      );\n
";
///////////////////////////////////////////////////////////////////////////////////////////////////

// save configuration to config.php
$editfile = fopen ("config/config.php", "w");
	fwrite($editfile, $content);
	fclose($editfile);

// If installation complete you get an affirmation
echo "<p style='color:green; font-weight:bold'>Congratulations! Established the connection to your database successfully.<br />You can now visit your openmicroblogger. Have fun! :-)</p><br />";
	if (is_writable ('config')) {
		echo "<p style='color:orange; font-weight:bold'>For your own security:<br />Set CHMOD of folder /config/ back to 755!!<br /></p>";
                             }
	if (!is_writable ('config')) {
		echo "<p style='color:green; font-weight:bold'>Security-Check: You already set CHMOD of folder /config/ back to 755. Great job!<br /></p>";
                              }
exit;
}

// if connection to database fails or if not entered db_name, db_user and/or db_password formular loads again with prefilled user-settings
else if (((mysqli_connect_errno()) && (isset($_POST['db_name']))) || ((isset($_POST['db_name'])) && ((trim($_POST['db_name']) == "") || (trim($_POST['db_user']) == "") || (trim($_POST['db_pw']) == "")))) {
	$db_name_tmp = trim($_POST['db_name']);
	$db_user_tmp = trim($_POST['db_user']);
	$db_pw_tmp = trim($_POST['db_pw']);
	$db_host_tmp = trim($_POST['db_host']);
	$db_charset_tmp = trim($_POST['db_charset']);
	$db_collate_tmp = trim($_POST['db_collate']);
	$pretty_urls_tmp = trim($_POST['pretty_urls']);
	$pretty_urls_base_tmp = trim($_POST['pretty_urls_base']);
	$intranet_tmp = trim($_POST['intranet']);
	$ping_tmp = trim($_POST['ping']);
	$cometpush_host_tmp = trim($_POST['cometpush_host']);
	$cometpush_port_tmp = trim($_POST['cometpush_port']);

echo "<br /><h2>Please enter your Database Information:</h2>
            <p style='color:orange; font-weight:bold'>Connection to Database failed.<br />Maybe you haven't filled out name, username, password correctly? Check your settings again.</p>
            
          <form action=\"install.php\" method=\"post\">
            <strong>Obligatory information:</strong><br />
            Name of Database <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_name\" value=\"$db_name_tmp\"/><br />
            Database Username: <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_user\" value=\"$db_user_tmp\" /><br />
            Database password: <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_pw\" value=\"$db_pw_tmp\" /> <br /><br />
            
            <strong>More database settings (should be right for most of the users)</strong><br />
            Database host <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_host\" value=\"$db_host_tmp\" /><br />
            Database charset <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_charset\" value=\"$db_charset_tmp\" /><br />
            Database Collate <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_collate\" value=\"$db_collate_tmp\" /><br /><br />
            
            <strong>Options (you don't have to edit this unless you want to change standard-settings)</strong><br />
            Do you want to use pretty URLs? (if enabled you have to move .htaccess from /app/config to your mainfolder and edit the values in .htaccess) <input type=\"checkbox\" name=\"pretty_urls\" value=\"$pretty_urls_tmp\"> <br />
            If you use pretty URLs please enter your url base <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"pretty_urls_base\" value=\"$pretty_urls_base_tmp\" /><br />
            Do you want to use openmicroblogger on intranet (password protected)? <input type=\"checkbox\" name=\"intranet\" value=\"$intranet_tmp\"><br />
            Ping? Uncheck for silent operation <input type=\"checkbox\" name=\"ping\" value=\"$ping_tmp\" checked><br>
            Host for comet push <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"cometpush_host\" value=\"$cometpush_host_tmp\" /><br />
            Port for comet push <input type=\"text\" size=\"5\" maxlength=\"5\" name=\"cometpush_port\" value=\"$cometpush_port_tmp\" /><br /><br />
            
            <strong>Standard language:</strong><br />
            Select your standard language: <select name=\"standard_lang\" size=\"1\"><option>german</option><option selected>english</option></select><br /><br />
          <input type=\"submit\" value=\"Write to config\" class=\"button\" />
           </form><br /><br />
           ";
}

// this is the standard formular to enter database-settings
else if ((!isset($_POST['db_name'])) AND (!isset($_POST['db_user'])) AND (!isset($_POST['db_pw']))){

     echo "<br /><h2>Please enter your Database Information:</h2>
          <form action=\"install.php\" method=\"post\">
            <strong>Obligatory information:</strong><br />
            Name of Database <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_name\" value=\"\"/><br />
            Database Username: <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_user\" value=\"\" /><br />
            Database password: <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_pw\" value=\"\" /> <br /><br />
            
            <strong>More database settings (should be right for most of the users)</strong><br />
            Database host <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_host\" value=\"localhost\" /><br />
            Database charset <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_charset\" value=\"utf8\" /><br />
            Database Collate <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_collate\" /><br /><br />
            
            <strong>Options (you don't have to edit this unless you want to change standard-settings)</strong><br />
            Do you want to use pretty URLs? <input type=\"checkbox\" name=\"pretty_urls\" value=\"TRUE\" > (if enabled you have to move .htaccess from /app/config to your mainfolder and edit the values in .htaccess)<br />
            If you use pretty URLs please enter your url base <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"pretty_urls_base\" value=\"http://yourdomain.tld\" /><br />
            Do you want to use openmicroblogger on intranet? <input type=\"checkbox\" name=\"intranet\" value=\"TRUE\"><br />
            Ping? Uncheck for silent operation <input type=\"checkbox\" name=\"ping\" value=\"TRUE\" checked><br>
            Host for comet push <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"cometpush_host\" /><br />
            Port for comet push <input type=\"text\" size=\"5\" maxlength=\"5\" name=\"cometpush_port\" /><br /><br />
            
            <strong>Standard language:</strong><br />
            Select your standard language: <select name=\"standard_lang\" size=\"1\"><option>german</option><option selected>english</option></select><br /><br />
          <input type=\"submit\" value=\"Write to config\" class=\"button\" />
           </form><br /><br />";

                                                                                                     }
                                                           }

?>
</body>
</html>