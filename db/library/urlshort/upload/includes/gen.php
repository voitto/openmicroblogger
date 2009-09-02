<?php

/* urlshort / includes / gen.php */
/* url generation and location */
/* version 1.1.0 */
/* urlshort.sourceforge.net */

class shorturl
{
	// constructor
	function shorturl()
	{
		// open mysql connection
$conn = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS);
if(!$conn){
  // header(s) to send
  header('HTTP/1.1 500 Internal Server Error');
  
  // html or other output to flush to browser
  die("urlShort database connection error");
}
		mysql_select_db(MYSQL_DB) or die('urlShort database selection error');	

	}

	// return the id for given url (or -1 if the url doesnt exist)
	function get_id($url)
	{
		$q = 'SELECT id FROM '.URL_TABLE.' WHERE (url="'.$url.'")';
		$result = mysql_query($q);
		if ($err = mysql_error()){
 			
 			###  add code to email someone the error
 			$body = 'get_id query failed' . $q . ' ' . $err;
 			###
 			

			// exit gracefully
			mysql_close($conn); 
			return -1;
 		}
 		else if ( mysql_num_rows($result) )
		{
			$row = mysql_fetch_array($result);
			return $row['id'];
			mysql_close($conn);
		}
		else
		{
			mysql_close($conn);
			return -1;
		}
	}

	// return the url for given id (or -1 if the id doesnt exist)
	function get_url($id)
	{
		$q = 'SELECT url FROM '.URL_TABLE.' WHERE (id="'.$id.'")';
		$result = mysql_query($q);

		if ($err = mysql_error()){
 			
 			###  add code to email someone the error
 			$body = 'get_url query failed' . $q . ' ' . $err;
 			###
 			
			mysql_close($conn);
			return -1;
 		}
 		else if ( mysql_num_rows($result) )
		{
			$row = mysql_fetch_array($result);
			return $row['url'];
			mysql_close($conn);
		}
		else
		{
			mysql_close($conn);
			return -1;
		}
	}
	
	// add url to the database
	function add_url($url,$plain)
	{
		// check to see if the urls already in there
		$id = $this->get_id($url);
		
		// if it is, return true
		if ( $id != -1 )
		{
			return true;
		}
		
		else // otherwise, put it in
		{
			if (!empty($plain)) {
				$q = 'INSERT INTO '.URL_TABLE.' (id, url, date, text) VALUES ("'.$plain.'", "'.$url.'", NOW(), 1)';
			}
			else {
				$id = $this->get_next_id($this->get_last_id());
				$q = 'INSERT INTO '.URL_TABLE.' (id, url, date) VALUES ("'.$id.'", "'.$url.'", NOW())';
			}
			return mysql_query($q);
			mysql_close($conn);
		}
	}

	// return most recent id (or -1 if no ids exist)
	function get_last_id()
	{	
		$q = 'SELECT id FROM '.URL_TABLE.' WHERE text = 0 ORDER BY date DESC LIMIT 1';
		$result = mysql_query($q);
		if ($err = mysql_error()){
 			
 			###  add code to email someone the error
 			$body = 'get_last_id query failed' . $q . ' ' . $err;
 			###
 			
			return -1;
 		}
 		else if ( mysql_num_rows($result) )
		{
			$row = mysql_fetch_array($result);
			return $row['id'];
		}
		else
		{
			return -1;
		}
	}	

	// return next id
	function get_next_id($last_id)
	{ 
	
		// if the last id is -1 (non-existant), start at the begining with 0
		if ( $last_id == -1 )
		{
			$next_id = 0;
		}
		else
		{
			// loop through the id string until we find a character to increment
			for ( $x = 1; $x <= strlen($last_id); $x++ )
			{
				$pos = strlen($last_id) - $x;

				if ( $last_id[$pos] != 'Z' )
				{
					$next_id = $this->increment_id($last_id, $pos);
					break; // <- kill the for loop once it finds a character
				}
			}

			// if every character was already at its max value (z),
			// append another character to the shortened string
			if ( !isSet($next_id) )
			{
				$next_id = $this->append_id($last_id);
			}
		}

		// check to see if the $next_id we made already exists, and if it does, 
		// loop the function until we find one that doesnt
		//
		// (this is basically a failsafe to get around the potential dangers of
		//  bad use of a timestamp to pick the most recent id)
		$q = 'SELECT id FROM '.URL_TABLE.' WHERE (id="'.$next_id.'")';
		$result = mysql_query($q);
		if ($err = mysql_error()){
 			
 			###  add code to email someone the error
 			$body = 'get_last_id query failed' . $q . ' ' . $err;
 			###
 			
			return -1;
 		}
 		else if ( mysql_num_rows($result) )
		{
			$next_id = $this->get_next_id($next_id);
		}

		return $next_id;
	}

	// make every character in the string 0, and then add an additional 0 to that
	function append_id($id)
	{
		for ( $x = 0; $x < strlen($id); $x++ )
		{
			$id[$x] = 0;
		}

		$id .= 0;

		return $id;
	}

	// increment a character to the next alphanumeric value and return the modified id
	function increment_id($id, $pos)
	{		
		$char = $id[$pos];

		// add 1 to numeric values
		if ( is_numeric($char) )
		{
			if ( $char < 9 )
			{
				$new_char = $char + 1;
			}
			else // if we're at z, it's time to move to upper case
			{
				$new_char = 'a';
			}
		}
		else // move it up the alphabet
		{
		  if ( $char == 'z' )
			{
				$new_char = 'A';
			}
			else
			{
  			$new_char = chr(ord($char) + 1);
			}
		}

		$id[$pos] = $new_char;
		
		// set all characters after the one we're modifying to 0
		if ( $pos != (strlen($id) - 1) )
		{
			for ( $x = ($pos + 1); $x < strlen($id); $x++ )
			{
				$id[$x] = 0;
			}
		}

		return $id;
	}

}

function check_plain ($plain) {
	if (empty($plain)) return TRUE;
	$q = 'SELECT id FROM '.URL_TABLE.' WHERE (id="'.$plain.'")';
	$result = mysql_query($q);
	$count = mysql_num_rows($result);
	if ($count) return false;
	mysql_close($conn);
	return true;

}

?>