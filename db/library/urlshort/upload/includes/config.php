<?php

/* urlshort / includes / config.php */
/* configuration and settings */
/* written june 24 2008 by adam */
/* updated june 24 2008 by adam */

// mysql connection info
global $db;
define('MYSQL_USER', $db->user);
define('MYSQL_PASS', $db->pass);
define('MYSQL_DB', $db->dbname);
define('MYSQL_HOST', $db->host);

// table
global $prefix;
define('URL_TABLE', $prefix.'urls');

// true = use mod rewrite
// default = true
define('REWRITE', true);

// allowed url prefixes
 $allowed_protocols = array('http:', 'https:', 'mailto:');

// uncomment to skip the protocol check
// $allowed_procotols = array();

?>