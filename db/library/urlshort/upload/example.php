<pre>
$longurl = 'http://urlshort.sourceforge.net/download/?version111-zip';

$fh = fopen("<?php include 'install_path.php'; echo $install_path; ?>api.php">api.php?url=$longurl", "r");

while(!feof($fh))
{
     echo $output = htmlspecialchars(fgets($fh, 1024));
  
}

fclose($fh);


THIS CODE PRODUCES THIS:
</pre>
<?php
$longurl = 'http://urlshort.sourceforge.net/download/?version111-zip';

$fh = fopen("<?php include 'install_path.php'; echo $install_path; ?>api.php">api.php?url=$longurl", "r");

while(!feof($fh))
{
     echo $output = '<b>'.htmlspecialchars(fgets($fh, 1024)) .'</b>';
  
}

fclose($fh);
?>
<pre>
AND
$shorturl = '<?php include 'install_path.php'; echo $install_path; ?>api.php">1';

$fh = fopen("<?php include 'install_path.php'; echo $install_path; ?>api.php">api.php?short=$longurl", "r");

while(!feof($fh))
{
     echo $output = htmlspecialchars(fgets($fh, 1024));
  
}

fclose($fh);


THIS CODE PRODUCES THIS:
</pre>
<?php
$shorturl = '<?php include 'install_path.php'; echo $install_path; ?>api.php">1';

$fh = fopen("<?php include 'install_path.php'; echo $install_path; ?>api.php">api.php?short=$shorturl", "r");

while(!feof($fh))
{
     echo $output = htmlspecialchars(fgets($fh, 1024));
  
}

fclose($fh);
?>