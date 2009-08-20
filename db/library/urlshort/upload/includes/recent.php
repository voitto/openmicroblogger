<?php

/* urlshort / includes / recent.php */
/* display an example using a real shortened url */
/* written april 27 2009 by matt */
/* updated may 29 2009 by matt */

error_reporting(0);

require_once 'config.php'; // settings
require_once 'install_path.php'; // installation path

mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die('');
		mysql_select_db(MYSQL_DB) or die('');	

$query = "SELECT id, url
FROM `urls`
ORDER BY `urls`.`date` DESC
LIMIT 1";

$result = mysql_query("$query");
while ($r = mysql_fetch_array($result)) {
$id = $r["id"];
$url = $r["url"];
} 

echo "<small><a href=\"$url\">$url</a></small><br/>was just shortened to <small><a href=\"$install_path$id\">$install_path$id</a></small>";

?>