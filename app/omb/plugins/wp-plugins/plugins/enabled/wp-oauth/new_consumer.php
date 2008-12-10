<?php
require_once dirname(__FILE__).'/common.inc.php';

$store = new OAuthWordpressStore();

$consumer = $store->new_consumer($_REQUEST['description']);
echo 'oauth_consumer_key='.urlencode($consumer->key).'&xoauth_consumer_secret='.urlencode($consumer->secret);

?>
