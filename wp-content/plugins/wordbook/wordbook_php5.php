<?php

function wordbook_rest_client($secret, $session_key) {
	return new FacebookRestClient(WORDBOOK_FB_APIKEY, $secret,
		$session_key);
}

function wordbook_fbclient_setfbml_impl($fbclient, $text) {
	try {
		$result = $fbclient->profile_setFBML(null, null, $text);
		$error_code = null;
		$error_msg = null;
	} catch (Exception $e) {
		$result = null;
		$error_code = $e->getCode();
		$error_msg = $e->getMessage();
	}
	return array($result, $error_code, $error_msg);
}

function wordbook_fbclient_publishaction_impl($fbclient, $template_id,
		$template_data,
		$storysize = FacebookRestClient::STORY_SIZE_SHORT) {
	try {
		$method = 'feed.publishUserAction';
		$result = $fbclient->feed_publishUserAction($template_id,
			$template_data, '', '', $storysize);
	} catch (Exception $e) {
		$error_code = $e->getCode();
		$error_msg = $e->getMessage();
	}
	return array($result, $error_code, $error_msg, $method);
}

function wordbook_fbclient_getinfo($fbclient, $fields) {
	try {
		$uid = $fbclient->users_getLoggedInUser();
		$users = $fbclient->users_getInfo(array($uid), $fields);
		$error_code = null;
		$error_msg = null;
	} catch (Exception $e) {
		$uid = null;
		$users = null;
		$error_code = $e->getCode();
		$error_msg = $e->getMessage();
	}
	return array($uid, $users, $error_code, $error_msg);
}

function wordbook_fbclient_getsession($fbclient, $token) {
	try {
		$result = $fbclient->auth_getSession($token);
		$error_code = null;
		$error_msg = null;
	} catch (Exception $e) {
		$result = null;
		$error_code = $e->getCode();
		$error_msg = $e->getMessage();
	}
	return array($result, $error_code, $error_msg);
}

?>
