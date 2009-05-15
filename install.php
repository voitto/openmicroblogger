<html>
<head>
<title>openmicroblogger - installation</title>
</head>
<body>

<?php

echo "<H1>openmicroblogger - Installation</H1><br />";
$checkfile = "config/config.php";
$checkfile2 = "config/.htaccess";

// Checks if connection to database works.
if (file_exists($checkfile)) {
include "config/config.php";

// If connection works setup stops and sends an information-message. Also checks if folder config has CHMOD 755
$db_check = @new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	if (!mysqli_connect_errno()) {
echo "<p style='color:green; font-weight:bold'>Congratulations! Established the connection to your database successfully.<br />Wrote config.php to /config/config.php.</p><br />";
if ($pretty_urls != 0 OR file_exists($checkfile2)) {
		echo "<p style='color:orange; font-weight:bold'>You enabled Pretty URLs:<br /><ul><li>You have to move the just automatically generated file '.htaccess' from folder /config/ to your main directory!</li><li>You have to edit /app/config/config.yml. Search for 'pretty_urls: false' and change to 'pretty_urls: true'.</li></ul></p>";
}
echo "<p style='color:orange; font-weight:bold'>Some other changes to do<ul><li>Edit /app/config/config.yml and search for '# environment settings'.<br /> There you can change some important site-settings like standard-mailadress, site-name, site-subtitle, site-description and theme.</li></ul></p>";

	if (is_writable ('config')) {
		echo "<p style='color:red; font-weight:bold'>For your own security:<ul><li>Set CHMOD of folder /config/ back to 755!!</li></ul></p>";
                             }
	if (!is_writable ('config')) {
		echo "<p style='color:green; font-weight:bold'>Security-Check: You already set CHMOD of folder /config/ back to 755. Great job!<br /></p>";
                              }
echo "<p style='color:green; font-weight:bold'>Thats it. You can now visit your installation of openmicroblogger. Have fun! :-)</p>";
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

	if (!isset($_POST['db_name'])) {
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
	$pretty_urls_sub = trim($_POST['pretty_urls_sub']);
	if ($pretty_urls_sub == TRUE) { $pretty_urls_sub = "1";} else { $pretty_urls_sub = "0";}
	$http_host = trim($_POST['http_host']);
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
\$pretty_url_base = \"$pretty_urls_base\";\n\n\n
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
chmod ("config/config.php", 0666);
//

///////////////////////////////////////////////////////////////////////////////////////////////////
// .htaccess file (if pretty urls are activated)
///////////////////////////////////////////////////////////////////////////////////////////////////
if ($pretty_urls != 0 AND $pretty_urls_sub == 0) {
$content2 = "RewriteEngine on
RewriteCond %{HTTP_HOST} ^$http_host$ [NC]
RewriteRule ^(.*)$ $pretty_urls_base/$1 [R=301,L]
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.+)$ ?$1 [PT,L,QSA]
";
}
else if ($pretty_urls != 0 AND $pretty_urls_sub != 0) {
$content2 = "RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.+)$ ?$1 [PT,L,QSA]
";
}
///////////////////////////////////////////////////////////////////////////////////////////////////

//save .htaccess to /config/_.htaccess
if ($pretty_urls != 0) {
$editfile = fopen ("config/.htaccess", "w");
	fwrite($editfile, $content2);
	fclose($editfile);
chmod ("config/.htaccess", 0666);
}
//


// If installation complete you get an affirmation
echo "<p style='color:green; font-weight:bold'>Congratulations! Established the connection to your database successfully.<br />Wrote config.php to /config/config.php.</p><br />";
if ($pretty_urls != 0 OR file_exists($checkfile2)) {
		echo "<p style='color:orange; font-weight:bold'>You enabled Pretty URLs:<br /><ul><li>You have to move the just automatically generated file '.htaccess' from folder /config/ to your main directory!</li><li>You have to edit /app/config/config.yml. Search for 'pretty_urls: false' and change to 'pretty_urls: true'.</li></ul></p>";
}
echo "<p style='color:orange; font-weight:bold'>Some other changes to do<ul><li>Edit /app/config/config.yml and search for '# environment settings'.<br /> There you can change some important site-settings like standard-mailadress, site-name, site-subtitle, site-description and theme.</li></ul></p>";

	if (is_writable ('config')) {
		echo "<p style='color:red; font-weight:bold'>For your own security:<ul><li>Set CHMOD of folder /config/ back to 755!!</li></ul></p>";
                             }
	if (!is_writable ('config')) {
		echo "<p style='color:green; font-weight:bold'>Security-Check: You already set CHMOD of folder /config/ back to 755. Great job!<br /></p>";
                              }
echo "<p style='color:green; font-weight:bold'>Thats it. You can now visit your installation of openmicroblogger. Have fun! :-)</p>";
exit;
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
	if ($pretty_urls_tmp == TRUE) { $pretty_urls = "1"; $pretty_urls_checked = "checked";} else { $pretty_urls = "0"; $pretty_urls_checked = "";}
	$pretty_urls_base_tmp = trim($_POST['pretty_urls_base']);
	$pretty_urls_sub_tmp = trim($_POST['pretty_urls_sub']);
	if ($pretty_urls_sub_tmp == TRUE) { $pretty_urls_sub = "1"; $pretty_urls_sub_checked = "checked";} else { $pretty_urls_sub = "0"; $pretty_urls_sub_checked = "";}
	$http_host_tmp = trim($_POST['http_host']);
	$intranet_tmp = trim($_POST['intranet']);
	if ($intranet_tmp == TRUE) { $intranet = "1"; $intranet_checked = "checked";} else { $intranet = "0"; $intranet_checked = "";}
	$ping_tmp = trim($_POST['ping']);
	if ($ping_tmp == TRUE) { $ping = "1"; $ping_checked = "checked";} else { $ping = "0"; $ping_checked = "";}
	$cometpush_host_tmp = trim($_POST['cometpush_host']);
	$cometpush_port_tmp = trim($_POST['cometpush_port']);
	$standard_lang = trim($_POST['standard_lang']);
	if ($standard_lang == 'german') { $standard_lang = "ger"; $ger_selected = "selected";} else if ($standard_lang == 'english') { $standard_lang = "eng"; $eng_selected = "selected";}

echo "<br /><h2>Please enter your Database Information:</h2>
            <p style='color:orange; font-weight:bold'>Connection to Database failed.<br />Maybe you haven't filled out name, username, password correctly? Check your settings again.</p>
            
          <form action=\"install.php\" method=\"post\">
            <strong>Obligatory information:</strong><br />
            <ul>
            <li>Name of Database <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_name\" value=\"$db_name_tmp\"/></li>
            <li>Database Username: <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_user\" value=\"$db_user_tmp\" /></li>
            <li>Database password: <input type=\"password\" size=\"20\" maxlength=\"30\" name=\"db_pw\" value=\"$db_pw_tmp\" /></li>
            </ul><br />
            
            <strong>More database settings (should be right for most of the users)</strong><br />
            <ul>
            <li>Database host <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_host\" value=\"$db_host_tmp\" /></li>
            <li>Database charset <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_charset\" value=\"$db_charset_tmp\" /></li>
            <li>Database Collate <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_collate\" value=\"$db_collate_tmp\" /></li>
            </ul><br />
            
            <strong>Pretty URLs</strong><br />
            Pretty URLs do look much smarter than standard URLs. Example: 'example.com/user/profil/settings' instead of 'example.com/?user.php=profil&settings'<br />
            If you enable pretty URLs this installer will generate a file named '.htaccess' in folder /config/. You have to move .htaccess to the main directory.<br />
            <ul>
            <li>Do you want to use pretty URLs? <input type=\"checkbox\" name=\"pretty_urls\" value=\"TRUE\" $pretty_urls_checked></li>
            <li>If you want to use pretty URLs, do you want to use pretty URLs on a subdomain like 'test.exampe.com' ? <input type=\"checkbox\" name=\"pretty_urls_sub\" value=\"TRUE\" $pretty_urls_sub_checked ></li>
            <li>If you want to use pretty URLs please enter your HTTP-HOST (without http://) <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"http_host\" value=\"$http_host_tmp\" /></li>
            <li>If you want to use pretty URLs please enter your url base <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"pretty_urls_base\" value=\"$pretty_urls_base_tmp\" /></li>
            </ul><br />

            <strong>Advanced Options (you don't have to edit this unless you want to change standard-settings)</strong><br />
            <ul>
            <li>Do you want to use openmicroblogger on intranet (password protected)? <input type=\"checkbox\" name=\"intranet\" value=\"TRUE\" $intranet_checked></li>
            <li>Ping? Uncheck for silent operation <input type=\"checkbox\" name=\"ping\" value=\"TRUE\" $ping_checked></li>
            <li>Host for comet push <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"cometpush_host\" value=\"$cometpush_host_tmp\" /></li>
            <li>Port for comet push <input type=\"text\" size=\"5\" maxlength=\"5\" name=\"cometpush_port\" value=\"$cometpush_port_tmp\" /></li>
            </ul><br />
            
            <strong>Standard language:</strong><br />
            Here you have to select the language which will be shown as standard. Guests and users who haven't set a individual language in their profile will see your website in this language.<br />
            <ul>
            <li>Select your standard language: <select name=\"standard_lang\" size=\"1\"><option $ger_selected>german</option><option $eng_selected>english</option></select><br /><br />
            
          <input type=\"submit\" value=\"Write to config\" class=\"button\" /></li>
          </ul>
           </form><br /><br />
           ";
}

// this is the standard formular to enter database-settings
else if ((!isset($_POST['db_name'])) AND (!isset($_POST['db_user'])) AND (!isset($_POST['db_pw']))){

     echo "<br /><h2>Please enter your Database Information:</h2>
          <form action=\"install.php\" method=\"post\">
            <strong>Obligatory information:</strong><br />
            <ul>
            <li>Name of Database <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_name\" value=\"\"/></li>
            <li>Database Username: <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_user\" value=\"\" /></li>
            <li>Database password: <input type=\"password\" size=\"20\" maxlength=\"30\" name=\"db_pw\" value=\"\" /></li>
            </ul>
            
            <strong>More database settings (should be right for most of the users)</strong><br />
            <ul>
            <li>Database host <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_host\" value=\"localhost\" /></li>
            <li>Database charset <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_charset\" value=\"utf8\" /></li>
            <li>Database Collate <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"db_collate\" /></li>
            </ul><br />
            
            <strong>Pretty URLs</strong><br />
            Pretty URLs do look much smarter than standard URLs. Example: 'example.com/user/profil/settings' instead of 'example.com/?user.php=profil&settings'<br />
            If you enable pretty URLs this installer will generate a file named '.htaccess' in folder /config/. You have to move .htaccess to the main directory.<br />
            <ul>
            <li>Do you want to use pretty URLs? <input type=\"checkbox\" name=\"pretty_urls\" value=\"TRUE\" ></li>
            <li>If you want to use pretty URLs, do you want to use pretty URLs on a subdomain like 'test.exampe.com' ? <input type=\"checkbox\" name=\"pretty_urls_sub\" value=\"TRUE\" ></li>
            <li>If you want to use pretty URLs please enter your HTTP-HOST (without http://) <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"http_host\" value=\"www.yourdomain.tld\" /></li>
            <li>If you want to use pretty URLs please enter your url base <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"pretty_urls_base\" value=\"http://yourdomain.tld\" /></li>
            </ul><br />

            <strong>Advanced Options (you don't have to edit this unless you want to change standard-settings)</strong><br />
            <ul>
            <li>Do you want to use openmicroblogger on intranet? <input type=\"checkbox\" name=\"intranet\" value=\"TRUE\"></li>
            <li>Ping? Uncheck for silent operation <input type=\"checkbox\" name=\"ping\" value=\"TRUE\" checked></li>
            <li>Host for comet push <input type=\"text\" size=\"20\" maxlength=\"30\" name=\"cometpush_host\" /></li>
            <li>Port for comet push <input type=\"text\" size=\"5\" maxlength=\"5\" name=\"cometpush_port\" /></li>
            </ul><br />
            
            <strong>Standard language:</strong><br />
            Here you have to select the language which will be shown as standard. Guests and users who haven't set a individual language in their profile will see your website in this language.<br />
            <ul>
            <li>Select your standard language: <select name=\"standard_lang\" size=\"1\"><option>german</option><option selected>english</option></select></li>
            </ul><br />
          <input type=\"submit\" value=\"Write to config\" class=\"button\" />
           </form><br /><br />";

                                                                                                     }
                                                           }

?>
</body>
</html>