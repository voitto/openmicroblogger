<?php

error_reporting(1);

/* urlshort / index.php */
/* landing page */
/* updated may 29 2009 by matt */

require_once 'includes/config.php'; // settings
require_once 'includes/gen.php'; // url generation and location

$perma = parse_url( $_SERVER['REQUEST_URI'] );
$_PERMA = explode( "/", $perma['path'] );
@array_shift( $_PERMA );

$url = new url();
$msg = '';

// if the form has been submitted
if ( isset($_POST['longurl']) )
{
	// escape bad characters from the users url
	$longurl = trim(mysql_escape_string($_POST['longurl']));
	
	//See if they put something in the text field
	$plain = trim(mysql_escape_string($_POST['plain']));
	
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

	$plaincheck = check_plain($plain);
			
	// Build link or error message
	if ( $protocol_ok && $url->add_url($longurl, $plain) && $plaincheck)
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
		$msg = '<br/><div class=success-display id=success-display style=\"display:block;\" \">Here you go: <input type=text size=30 name=directurl value='.$url.' class=finaltextbox  onclick="select_text();"></div>';
	}
	elseif ( !$protocol_ok )
	{
		$msg = '<p class="error"></p>';
	}
	elseif(!$plaincheck) {
		$msg = '<br/><div class=error-display id=error-display style=\"display:block;\" \">Sorry. That custom name is already in use.</div>';
	}
	else // something broken
	{
		$msg = '<br/><div class=error-display id=error-display style=\"display:block;\" \">Something broke. Try again?</div>';
	}
}
else // if just linked, no submission, look for an id to redirect to
{
	if ( isset($_PERMA[0]) ) // check GET first
	{
		$id = mysql_escape_string($_PERMA[0]);
	}
	/*elseif ( REWRITE ) // check the URI if we're using mod_rewrite
	{
		$explodo = explode('/', $_SERVER['REQUEST_URI']);
		$id = mysql_escape_string($explodo[count($explodo)-1]);
	}*/
	else // otherwise, just make it empty
	{
		$id = '';
	}
		
	// if the id isnt empty and its not this file, redirect to its url
	if ( $id != '' && $id != basename($_SERVER['PHP_SELF']) )
	{
		$location = $url->get_url($id);
		
		if ( $location != -1 )
		{
			header('Location: '.$location, TRUE, 301);
		}
		else // failure to find url output 404
		{
			$msg = '<br/><div class=error-display id=error-display style=\"display:block;\" \">That URL does not exist. Try again?</div>';
		}
	}
}

// print the form

?>

<?php include 'includes/header-one.php'; ?>
	
	<body onload="document.getElementById('longurl').focus()">
		
<?php include 'includes/header-two.php'; ?>
		
		<?php echo $msg; ?>
		
		<p><form action="<?php echo $_SERVER['PHP_SELF']?>" method="post">
		
			<fieldset>
				<label for="longurl">Enter a URL:</label>
				<input type="text" name="longurl" id="longurl" size="25" class="textbox"/><br />
<a href="#" id="open" onclick="show(this.id)">Choose a custom name<br /></a>
<div id="custom" style="display:none;">
<label for="plain"><?php include 'install_path.php'; echo $install_path; ?></label><input type="text" name="plain" id="plain" size="16" class="textbox-custom"/></div>
<div class="buttons"><button type="submit" class="positive" onclick="javascript:document.getElementById('once').disabled=true">Shorten</button><button type="reset" class="negative">Clear</button></div></fieldset>
<br/><div class="example"><?php include 'includes/random.php'; ?></div><br/><br/><b><?php include 'includes/counter.php'; ?></b> URLs shortened
		
		</form></p>

<?php include 'includes/footer.php'; ?>