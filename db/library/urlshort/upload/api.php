<?php

/* urlshort / api.php */
/* api for creation and lookup*/
/* written june 24 2008 by adam */
/* updated may 29 2009 by matt */

error_reporting(0);

require_once 'includes/config.php'; // settings
require_once 'includes/gen.php'; // url generation and location
require_once 'includes/install_path.php'; 
global $request;
$url = new shorturl();
$msg = '';
 
		header('HTTP/1.1 500 Internal Server Error'); 

// if the url has been sent to this script
if ( isset($request->url) && strlen(trim($request->url)) )
{
	// escape bad characters from the users url
	$longurl = trim(mysql_escape_string($request->url));

	// set the protocol to not ok by default
	$protocol_ok = false;
	
	// if there's a list of allowed protocols, 
	// check to make sure its all cool
	if ( count($allowed_protocols) )
	{
		foreach ( $allowed_protocols as $ap )
		{
			if ( strtolower(substr($longurl, 0, strlen($ap))) == strtolower($ap) )
			{
				$protocol_ok = true;
				break;
			}
		}
	}
	else // if there's no protocol list, fuck all that
	{
		$protocol_ok = true;
	}
		
	// add the url to the database
	if ( $protocol_ok && $url->add_url($longurl) )
	{
		if ( REWRITE ) // mod_rewrite style link
		{
			$url = 'http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['PHP_SELF']).''.$url->get_id($longurl);
		}
		else // regular GET style link
		{
			$url = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?id='.$url->get_id($longurl);
		}
 		// if good output url 
		header('HTTP/1.1 200 OK'); 
		$msg = $url;
		mysql_close($conn); 
	}
	elseif ( !$protocol_ok )
	{
		header('HTTP/1.1 500 Internal Server Error'); 
		$msg = 'error - invalid protocol';
		mysql_close($conn); 
	}
	else // something broken
	{
		header('HTTP/1.1 500 Internal Server Error'); 
		$msg = 'error';
		mysql_close($conn); 
	}
}
else 
{
		header('HTTP/1.1 500 Internal Server Error'); 
		$msg = 'error - invalid long url';
		mysql_close($conn); 
}

// if the id has been sent to this script

if ( isset($request->custom) && strlen(trim($request->custom)) )
{
	// escape bad characters from the users url
	$shorturl = trim(mysql_escape_string($request->custom));

		$string = "$shorturl";

		list($string1,$string2) = explode("$install_path",$string); 

		$shortid = $string1.$string2;

	// return the url for given id (or -1 if the id doesnt exist)

		$q2 = 'SELECT url FROM `urls` WHERE `id` LIKE CONVERT(_utf8 \''.$shortid.'\' USING latin1)'; 

		$result2 = mysql_query($q2);

		while ($row = mysql_fetch_array($result2, MYSQL_ASSOC)) {
			printf($row["url"]);

exit();

		}

if ( mysql_num_rows( $result2 ) == $result2 ) {
$fullurl = mysql_result($result2, 1);
}

else{
		header('HTTP/1.1 500 Internal Server Error'); 
		$fullurl = 'error - invalid short url';
		mysql_close($conn); 
}

		header('HTTP/1.1 200 OK');
		$msg = $fullurl;
		mysql_close($conn); 

}

	/***************************/
	// echo the url or error 
	/***************************/
	echo $msg;
	mysql_close($conn); 
?>