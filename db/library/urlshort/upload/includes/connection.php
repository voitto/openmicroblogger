<?php

/* urlshort / includes / connection.php */
/* form a database connection */

error_reporting(0);
require_once 'config.php';

$conn = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS);

if(!$conn){
  // header(s) to send
  header('HTTP/1.1 500 Internal Server Error');
  mysql_close($conn); 
  
  // html or other output to flush to browser
  die("error");
  mysql_close($conn); 
}
?>