<?php

if (!(isset($_GET['redirect_to'])))
  trigger_error('sorry, there was an error forwarding to the login page', E_USER_ERROR );

$url = parse_url($_GET['redirect_to']);

$login = 'Location: '.$url['scheme']."://".$url['host'].$url['path']."?email_login";

header( $login );

exit;
