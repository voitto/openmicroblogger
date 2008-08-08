<?php
require_once dirname(__FILE__).'/common.inc.php';

$store = new OAuthWordpressStore();
$server = new OAuthServer($store);
$sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
$plaintext_method = new OAuthSignatureMethod_PLAINTEXT();
$server->add_signature_method($sha1_method);
$server->add_signature_method($plaintext_method);

try {
  $req = OAuthRequest::from_request();
  $token = $server->fetch_access_token($req);
  print $token.'&xoauth_token_expires='.urlencode($store->token_expires($token));
} catch (OAuthException $e) {
  header('Content-type: text/plain;', true, 400);
  print($e->getMessage() . "\n\n");
  var_dump($req);
  die;
}

?>
