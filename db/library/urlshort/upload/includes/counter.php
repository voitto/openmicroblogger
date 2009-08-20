<?php

/* urlshort / includes / counter.php */
/* get the number of shortened urls each time */
/* written march 21 2009 by matt */
/* updated may 29 2009 by matt */

error_reporting(0);

require_once 'config.php'; // settings

$conn = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS);

		mysql_select_db(MYSQL_DB);	

$result = mysql_query("SELECT * FROM urls");
$num_rows = mysql_num_rows($result);
$number = $num_rows;
$english_format_number = number_format($number);
echo $english_format_number;

mysql_close($conn); 
?>