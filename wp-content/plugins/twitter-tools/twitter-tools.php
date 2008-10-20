<?php
/*
Plugin Name: Twitter Tools
Plugin URI: http://alexking.org/projects/wordpress
Description: A complete integration between your WordPress blog and <a href="http://twitter.com">Twitter</a>. Bring your tweets into your blog and pass your blog posts to Twitter. <a href="options-general.php?page=twitter-tools.php">Configure your settings here</a>.
Version: 1.1b1
Author: Alex King
Author URI: http://alexking.org
*/

// Copyright (c) 2007 Alex King. All rights reserved.
//
// Released under the GPL license
// http://www.opensource.org/licenses/gpl-license.php
//
// This is an add-on for WordPress
// http://wordpress.org/
//
// Thanks to John Ford ( http://www.aldenta.com ) for his contributions.
// Thanks to Dougal Campbell ( http://dougal.gunters.org ) for his contributions.
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
// **********************************************************************

load_plugin_textdomain('twitter-tools');

if (!function_exists('is_admin_page')) {
	function is_admin_page() {
		if (function_exists('is_admin')) {
			return is_admin();
		}
		if (function_exists('check_admin_referer')) {
			return true;
		}
		else {
			return false;
		}
	}
}

class twitter_tools {
	function twitter_tools() {
		$this->options = array(
			'twitter_username'
			, 'twitter_password'
			, 'create_blog_posts'
			, 'create_digest'
			, 'digest_title'
			, 'blog_post_author'
			, 'blog_post_category'
			, 'notify_twitter'
			, 'sidebar_tweet_count'
			, 'tweet_from_sidebar'
			, 'give_tt_credit'
			, 'last_tweet_download'
			, 'doing_tweet_download'
			, 'doing_digest_post'
		);
		$this->twitter_username = '';
		$this->twitter_password = '';
		$this->create_blog_posts = '0';
		$this->create_digest = '0';
		$this->digest_title = __("Twitter Updates for %s", 'twitter-tools');
		$this->blog_post_author = '1';
		$this->blog_post_category = '1';
		$this->notify_twitter = '0';
		$this->sidebar_tweet_count = '3';
		$this->tweet_from_sidebar = '1';
		$this->give_tt_credit = '1';
		// not included in options
		$this->update_hash = '';
		$this->tweet_prefix = 'New blog post';
		$this->tweet_format = $this->tweet_prefix.': %s %s';
		$this->last_digest_post = '';
		$this->last_tweet_download = '';
		$this->doing_tweet_download = '0';
		$this->doing_digest_post = '0';
		$this->version = '1.0';
	}

	function install() {
		global $wpdb;

		$charset_collate = '';
		if ( version_compare(mysql_get_server_info(), '4.1.0', '>=') ) {
			if (!empty($wpdb->charset)) {
				$charset_collate .= " DEFAULT CHARACTER SET $wpdb->charset";
			}
			if (!empty($wpdb->collate)) {
				$charset_collate .= " COLLATE $wpdb->collate";
			}
		}
		$result = $wpdb->query("
			CREATE TABLE `$wpdb->aktt` (
			`id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`tw_id` VARCHAR( 255 ) NOT NULL ,
			`tw_text` VARCHAR( 255 ) NOT NULL ,
			`tw_created_at` DATETIME NOT NULL ,
			`modified` DATETIME NOT NULL ,
			INDEX ( `tw_id` )
			) $charset_collate
		");
		foreach ($this->options as $option) {
			add_option('aktt_'.$option, $this->$option);
		}
		add_option('aktt_update_hash', '');
	}

	function get_settings() {
		foreach ($this->options as $option) {
			$this->$option = get_option('aktt_'.$option);
		}
	}

	function update_settings() {
		if (current_user_can('manage_options')) {
			if (get_option('aktt_create_digest') != '1' && $this->create_digest == 1) {
				$this->initiate_digests();
			}
			$this->sidebar_tweet_count = intval($this->sidebar_tweet_count);
			if ($this->sidebar_tweet_count == 0) {
				$this->sidebar_tweet_count = '3';
			}
			foreach ($this->options as $option) {
				update_option('aktt_'.$option, $this->$option);
			}
		}
	}

	function populate_settings() {
		foreach ($this->options as $option) {
			if (isset($_POST['aktt_'.$option])) {
				$this->$option = stripslashes($_POST['aktt_'.$option]);
			}
		}
	}
	
	function initiate_digests() {
		$this->last_digest_post = date('Y-m-d 00:00:00', strtotime('-1 day'));
		if (get_option('aktt_last_digest_post') == '') {
			add_option('aktt_last_digest_post', $this->last_digest_post);
		}
		else {
			update_option('aktt_last_digest_post', $this->last_digest_post);
		}
	}

	function tweet_download_interval() {
		return 1800;
	}
	
	function do_tweet($tweet = '') {
		if (empty($this->twitter_username) 
			|| empty($this->twitter_password) 
			|| empty($tweet)
			|| empty($tweet->tw_text)
		) {
			return;
		}
		require_once(ABSPATH.WPINC.'/class-snoopy.php');
		$snoop = new Snoopy;
		$snoop->agent = 'Twitter Tools http://alexking.org/projects/wordpress';
		$snoop->rawheaders = array(
			'X-Twitter-Client' => 'Twitter Tools'
			, 'X-Twitter-Client-Version' => $this->version
			, 'X-Twitter-Client-URL' => 'http://alexking.org/projects/wordpress/twitter-tools.xml'
		);
		$snoop->user = $this->twitter_username;
		$snoop->pass = $this->twitter_password;
		$snoop->submit(
			'http://twitter.com/statuses/update.json'
			, array(
				'status' => $tweet->tw_text
				, 'source' => 'twittertools'
			)
		);
		if (strpos($snoop->response_code, '200')) {
			update_option('aktt_last_tweet_download', strtotime('-28 minutes'));
			return true;
		}
		return false;
	}
	
	function do_blog_post_tweet($post_id = 0) {
		if ($this->notify_twitter == '0'
			|| $post_id == 0
			|| get_post_meta($post_id, 'aktt_tweeted', true) == '1'
		) {
			return;
		}
		$post = get_post($post_id);
		$tweet = new aktt_tweet;
		$tweet->tw_text = sprintf(__($this->tweet_format, 'twitter-tools'), $post->post_title, get_permalink($post_id));
		$this->do_tweet($tweet);
		add_post_meta($post_id, 'aktt_tweeted', '1', true);
	}
	
	function do_tweet_post($tweet) {
		global $wpdb;
		remove_action('publish_post', 'aktt_notify_twitter');
		$data = array(
			'post_content' => $wpdb->escape($tweet->tw_text)
			, 'post_title' => $wpdb->escape(trim_add_elipsis($tweet->tw_text, 30))
			, 'post_date' => get_date_from_gmt(date('Y-m-d H:i:s', $tweet->tw_created_at))
			, 'post_category' => array($this->blog_post_category)
			, 'post_status' => 'publish'
			, 'post_author' => $wpdb->escape($this->blog_post_author)
		);
		$post_id = wp_insert_post($data);
		add_post_meta($post_id, 'aktt_twitter_id', $tweet->tw_id, true);
		add_action('publish_post', 'aktt_notify_twitter');
	}
	
	function do_digest_post() {
		global $wpdb;
		if ($this->create_digest != '1' || get_option('aktt_doing_digest_post') == '1') {
			return;
		}
		update_option('aktt_doing_digest_post', '1');
		remove_action('publish_post', 'aktt_notify_twitter');

		$now = ak_gmmktime();
		$yesterday = strtotime('-1 day', $now);
		$last_post = get_option('aktt_last_digest_post');
		
		if ($last_post != date('Y-m-d 00:00:00', $yesterday)) {
			$days = ceil((strtotime(date('Y-m-d 00:00:00', $yesterday)) - strtotime($last_post)) / (3600 * 24));
		}
		else {
			$days = 1;
		}
		for ($i = 0; $i < $days; $i++) {
			$n = $days - $i;
			$digest_day = strtotime('-'.$n.' days', $now);
			$tweets = $wpdb->get_results("
				SELECT *
				FROM $wpdb->aktt
				WHERE tw_created_at >= '".date('Y-m-d 00:00:00', $digest_day)."'
				AND tw_created_at <= '".date('Y-m-d 23:59:59', $digest_day)."'
				GROUP BY tw_id
				ORDER BY tw_created_at
			");
			if (count($tweets) > 0) {
				$tweets_to_post = array();
				foreach ($tweets as $data) {
					$tweet = new aktt_tweet;
					$tweet->tw_text = $data->tw_text;
					if (!$tweet->tweet_is_post_notification()) {
						$tweets_to_post[] = $data;
					}
				}
				if (count($tweets_to_post) > 0) {
					$content = '<ul class="aktt_tweet_digest">'."\n";
					foreach ($tweets_to_post as $tweet) {
						$content .= '	<li>'.make_clickable($tweet->tw_text).' <a href="http://twitter.com/'.$this->twitter_username.'/statuses/'.$tweet->tw_id.'">#</a></li>'."\n";
					}
					$content .= '</ul>'."\n";
					if ($this->give_tt_credit == '1') {
						$content .= '<p class="aktt_credit">Powered by <a href="http://alexking.org/projects/wordpress">Twitter Tools</a>.</p>';
					}
					$data = array(
						'post_content' => $wpdb->escape($content)
						, 'post_title' => $wpdb->escape(sprintf($this->digest_title, date('Y-m-d', $digest_day)))
						, 'post_date' => date('Y-m-d 23:59:59', $digest_day)
						, 'post_category' => array($this->blog_post_category)
						, 'post_status' => 'publish'
						, 'post_author' => $wpdb->escape($this->blog_post_author)
					);
					$post_id = wp_insert_post($data);
					add_post_meta($post_id, 'aktt_tweeted', '1', true);
				}
			}
		}
		$this->last_digest_post = date('Y-m-d 00:00:00', $now);
		update_option('aktt_last_digest_post', $this->last_digest_post);
		add_action('publish_post', 'aktt_notify_twitter');
		update_option('aktt_doing_digest_post', '0');
	}
}

class aktt_tweet {
	function aktt_tweet(
		$tw_id = ''
		, $tw_text = ''
		, $tw_created_at = ''
	) {
		$this->id = '';
		$this->modified = '';
		$this->tw_created_at = $tw_created_at;
		$this->tw_text = $tw_text;
		$this->tw_id = $tw_id;
	}
	
	function twdate_to_time($date) {
		$parts = explode(' ', $date);
		$date = strtotime($parts[1].' '.$parts[2].', '.$parts[5].' '.$parts[3]);
		return $date;
	}
	
	function tweet_post_exists() {
		global $wpdb;
		$test = $wpdb->get_results("
			SELECT *
			FROM $wpdb->postmeta
			WHERE meta_key = 'aktt_twitter_id'
			AND meta_value = '$this->tw_id'
		");
		if (count($test) > 0) {
			return true;
		}
		return false;
	}
	
	function tweet_is_post_notification() {
		global $aktt;
		if (substr($this->tw_text, 0, strlen($aktt->tweet_prefix)) == $aktt->tweet_prefix) {
			return true;
		}
		return false;
	}
	
	function add() {
		global $wpdb, $aktt;
		$wpdb->query("
			INSERT
			INTO $wpdb->aktt
			( tw_id
			, tw_text
			, tw_created_at
			, modified
			)
			VALUES
			( '".$wpdb->escape($this->tw_id)."'
			, '".$wpdb->escape($this->tw_text)."'
			, '".date('Y-m-d H:i:s', $this->tw_created_at)."'
			, NOW()
			)
		");
		do_action('aktt_add_tweet', $this);
		if ($aktt->create_blog_posts == '1' && !$this->tweet_post_exists() && !$this->tweet_is_post_notification()) {
			$aktt->do_tweet_post($this);
		}
	}
}

function aktt_login_test($username, $password) {
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	$snoop = new Snoopy;
	$snoop->agent = 'Twitter Tools http://alexking.org/projects/wordpress';
	$snoop->user = $username;
	$snoop->pass = $password;
	$snoop->fetch('http://twitter.com/statuses/user_timeline.json');
	if (strpos($snoop->response_code, '200')) {
		return true;
	}
	else {
		return false;
	}
}

function aktt_update_tweets() {
	// let the last update run for 10 minutes
	if (time() - intval(get_option('aktt_doing_tweet_download')) < 600) {
		return;
	}
	update_option('aktt_doing_tweet_download', time());
	global $wpdb, $aktt;
	if (empty($aktt->twitter_username) || empty($aktt->twitter_password)) {
		update_option('aktt_doing_tweet_download', '0');
		die();
	}
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	$snoop = new Snoopy;
	$snoop->agent = 'Twitter Tools http://alexking.org/projects/wordpress';
	$snoop->user = $aktt->twitter_username;
	$snoop->pass = $aktt->twitter_password;
	$snoop->fetch('http://twitter.com/statuses/user_timeline.json');

	if (!strpos($snoop->response_code, '200')) {
		update_option('aktt_doing_tweet_download', '0');
		return;
	}

	$data = $snoop->results;

	$hash = md5($data);
	if ($hash == get_option('aktt_update_hash')) {
		update_option('aktt_doing_tweet_download', '0');
		return;
	}
	$json = new Services_JSON();
	$tweets = $json->decode($data);
	if (is_array($tweets) && count($tweets) > 0) {
		$tweet_ids = array();
		foreach ($tweets as $tweet) {
			$tweet_ids[] = $wpdb->escape($tweet->id);
		}
		$existing_ids = $wpdb->get_col("
			SELECT tw_id
			FROM $wpdb->aktt
			WHERE tw_id
			IN ('".implode("', '", $tweet_ids)."')
		");
		$new_tweets = array();
		foreach ($tweets as $tw_data) {
			if (!$existing_ids || !in_array($tw_data->id, $existing_ids)) {
				$tweet = new aktt_tweet(
					$tw_data->id
					, $tw_data->text
				);
				$tweet->tw_created_at = $tweet->twdate_to_time($tw_data->created_at);
				$new_tweets[] = $tweet;
			}
		}
		foreach ($new_tweets as $tweet) {
			$tweet->add();
		}
	}
	update_option('aktt_update_hash', $hash);
	update_option('aktt_last_tweet_download', time());
	update_option('aktt_doing_tweet_download', '0');
	if ($aktt->create_digest == '1' && strtotime(get_option('aktt_last_digest_post')) < strtotime(date('Y-m-d 00:00:00', ak_gmmktime()))) {
		$aktt->do_digest_post();
	}
}

function aktt_notify_twitter($post_id) {
	global $aktt;
	$aktt->do_blog_post_tweet($post_id);
}
add_action('publish_post', 'aktt_notify_twitter');

function aktt_sidebar_tweets() {
	global $wpdb, $aktt;
	$tweets = $wpdb->get_results("
		SELECT *
		FROM $wpdb->aktt
		GROUP BY tw_id
		ORDER BY tw_created_at DESC
		LIMIT $aktt->sidebar_tweet_count
	");
	$output = '<div class="aktt_tweets">'."\n"
		.'	<ul>'."\n";
	if (count($tweets) > 0) {
		foreach ($tweets as $tweet) {
			$output .= '		<li>'.aktt_make_clickable(wp_specialchars($tweet->tw_text)).' <a href="http://twitter.com/'.$aktt->twitter_username.'/statuses/'.$tweet->tw_id.'">'.aktt_relativeTime($tweet->tw_created_at, 3).'</a></li>'."\n";
		}
	}
	else {
		$output .= '		<li>'.__('No tweets available at the moment.', 'twitter-tools').'</li>'."\n";
	}
	$output .= '		<li class="aktt_more_updates"><a href="http://twitter.com/'.$aktt->twitter_username.'">More updates...</a></li>'."\n"
		.'</ul>';
	if ($aktt->tweet_from_sidebar == '1') {
		$output .= aktt_tweet_form('input', 'onsubmit="akttPostTweet(); return false;"');
		$output .= '	<p id="aktt_tweet_posted_msg">'.__('Posting tweet...', 'twitter-tools').'</p>';
	}
	if ($aktt->give_tt_credit == '1') {
		$output .= '<p class="aktt_credit">Powered by <a href="http://alexking.org/projects/wordpress">Twitter Tools</a>.</p>';
	}
	$output .= '</div>';
	print($output);
}

function aktt_latest_tweet() {
	global $wpdb, $aktt;
	$tweets = $wpdb->get_results("
		SELECT *
		FROM $wpdb->aktt
		GROUP BY tw_id
		ORDER BY tw_created_at DESC
		LIMIT 1
	");
	if (count($tweets) == 1) {
		foreach ($tweets as $tweet) {
			$output = aktt_make_clickable(wp_specialchars($tweet->tw_text)).' <a href="http://twitter.com/'.$aktt->twitter_username.'/statuses/'.$tweet->tw_id.'">'.aktt_relativeTime($tweet->tw_created_at, 3).'</a>';
		}
	}
	else {
		$output = __('No tweets available at the moment.', 'twitter-tools');
	}
	print($output);
}

function aktt_make_clickable($tweet) {
	if (substr($tweet, 0, 1) == '@' && substr($tweet, 1, 1) != ' ') {
		$space = strpos($tweet, ' ');
		$username = substr($tweet, 1, $space - 1);
		$tweet = '<a href="http://twitter.com/'.$username.'">@'.$username.'</a>'.substr($tweet, $space);
	}
	if (function_exists('make_chunky')) {
		return make_chunky($tweet);
	}
	else {
		return make_clickable($tweet);
	}
}

function aktt_tweet_form($type = 'input', $extra = '') {
	$output = '';
	if (current_user_can('publish_posts')) {
		$output .= '
<form action="'.get_bloginfo('wpurl').'/index.php" method="post" id="aktt_tweet_form" '.$extra.'>
	<fieldset>
		';
		switch ($type) {
			case 'input':
				$output .= '
		<p><input type="text" size="20" maxlength="140" id="aktt_tweet_text" name="aktt_tweet_text" onkeyup="akttCharCount();" /></p>
		<input type="hidden" name="ak_action" value="aktt_post_tweet_sidebar" />
		<script type="text/javascript">
		//<![CDATA[
		function akttCharCount() {
			var count = document.getElementById("aktt_tweet_text").value.length;
			if (count > 0) {
				document.getElementById("aktt_char_count").innerHTML = 140 - count;
			}
			else {
				document.getElementById("aktt_char_count").innerHTML = "";
			}
		}
		setTimeout("akttCharCount();", 500);
		document.getElementById("aktt_tweet_form").setAttribute("autocomplete", "off");
		//]]>
		</script>
				';
				break;
			case 'textarea':
				$output .= '
		<p><textarea type="text" cols="60" rows="5" maxlength="140" id="aktt_tweet_text" name="aktt_tweet_text" onkeyup="akttCharCount();"></textarea></p>
		<input type="hidden" name="ak_action" value="aktt_post_tweet_admin" />
		<script type="text/javascript">
		//<![CDATA[
		function akttCharCount() {
			var count = document.getElementById("aktt_tweet_text").value.length;
			if (count > 0) {
				document.getElementById("aktt_char_count").innerHTML = (140 - count) + "'.__(' characters remaining', 'twitter-tools').'";
			}
			else {
				document.getElementById("aktt_char_count").innerHTML = "";
			}
		}
		setTimeout("akttCharCount();", 500);
		document.getElementById("aktt_tweet_form").setAttribute("autocomplete", "off");
		//]]>
		</script>
				';
				break;
		}
		$output .= '
		<p>
			<input type="submit" id="aktt_tweet_submit" name="aktt_tweet_submit" value="'.__('Post Tweet!', 'twitter-tools').'" />
			<span id="aktt_char_count"></span>
		</p>
		<div class="clear"></div>
	</fieldset>
</form>
		';
	}
	return $output;
}

function aktt_widget_init() {
	if (!function_exists('register_sidebar_widget')) {
		return;
	}
	function aktt_widget($args) {
		extract($args);
		$options = get_option('aktt_widget');
		$title = $options['title'];
		if (empty($title)) {
		}
		echo $before_widget . $before_title . $title . $after_title;
		aktt_sidebar_tweets();
		echo $after_widget;
	}
	register_sidebar_widget(array(__('Twitter Tools', 'twitter-tools'), 'widgets'), 'aktt_widget');
	
	function aktt_widget_control() {
		$options = get_option('aktt_widget');
		if (!is_array($options)) {
			$options = array(
				'title' => __("What I'm Doing...", 'twitter-tools')
			);
		}
		if (isset($_POST['ak_action']) && $_POST['ak_action'] == 'aktt_update_widget_options') {
			$options['title'] = strip_tags(stripslashes($_POST['aktt_widget_title']));
			update_option('aktt_widget', $options);
		}

		// Be sure you format your options to be valid HTML attributes.
		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		
		// Here is our little form segment. Notice that we don't need a
		// complete form. This will be embedded into the existing form.
		print('
			<p style="text-align:right;"><label for="aktt_widget_title">' . __('Title:') . ' <input style="width: 200px;" id="aktt_widget_title" name="aktt_widget_title" type="text" value="'.$title.'" /></label></p>
			<p>'.__('Find additional Twitter Tools options on the <a href="options-general.php?page=twitter-tools.php">Twitter Tools Options page</a>.', 'twitter-tools').'
			<input type="hidden" id="ak_action" name="ak_action" value="aktt_update_widget_options" />
		');
	}
	register_widget_control(array(__('Twitter Tools', 'twitter-tools'), 'widgets'), 'aktt_widget_control', 300, 100);

}
add_action('widgets_init', 'aktt_widget_init');

function aktt_init() {
	global $wpdb, $aktt;
	$aktt = new twitter_tools;
	$wpdb->aktt = $wpdb->prefix.'ak_twitter';
	if (isset($_GET['activate']) && $_GET['activate'] == 'true') {
		$tables = $wpdb->get_col("
			SHOW TABLES
		");
		if (!in_array($wpdb->aktt, $tables)) {
			$aktt->install();
		}
	}
	$aktt->get_settings();
	if (($aktt->last_tweet_download + $aktt->tweet_download_interval()) < time()) {
		add_action('shutdown', 'aktt_update_tweets');
	}
	if (is_admin() || $aktt->tweet_from_sidebar) {
		wp_enqueue_script('prototype');
	}
}
add_action('init', 'aktt_init');

function aktt_head() {
	global $aktt;
	if ($aktt->tweet_from_sidebar) {
		print('
			<link rel="stylesheet" type="text/css" href="'.get_bloginfo('wpurl').'/index.php?ak_action=aktt_css" />
			<script type="text/javascript" src="'.get_bloginfo('wpurl').'/index.php?ak_action=aktt_js"></script>
		');
	}
}
add_action('wp_head', 'aktt_head');

function aktt_head_admin() {
	print('
		<link rel="stylesheet" type="text/css" href="'.get_bloginfo('wpurl').'/index.php?ak_action=aktt_css_admin" />
		<script type="text/javascript" src="'.get_bloginfo('wpurl').'/index.php?ak_action=aktt_js_admin"></script>
	');
}
add_action('admin_head', 'aktt_head_admin');

function aktt_request_handler() {
	global $wpdb, $aktt;
	if (!empty($_GET['ak_action'])) {
		switch($_GET['ak_action']) {
			case 'aktt_update_tweets':
				aktt_update_tweets();
				header('Location: '.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=twitter-tools.php&tweets-updated=true');
				die();
				break;
			case 'aktt_js':
				header("Content-type: text/javascript");
?>
function akttPostTweet() {
	var tweet_field = $('aktt_tweet_text');
	var tweet_text = tweet_field.value;
	if (tweet_text == '') {
		return;
	}
	var tweet_msg = $("aktt_tweet_posted_msg");
	var akttAjax = new Ajax.Updater(
		tweet_msg,
		"<?php bloginfo('wpurl'); ?>/index.php",
		{
			method: "post",
			parameters: "ak_action=aktt_post_tweet_sidebar&aktt_tweet_text=" + tweet_text,
			onComplete: akttSetReset
		}
	);
	tweet_field.value = '';
	tweet_field.focus();
	$('aktt_char_count').innerHTML = '';
	tweet_msg.style.display = 'block';
}
function akttSetReset() {
	setTimeout('akttReset();', 2000);
}
function akttReset() {
	$('aktt_tweet_posted_msg').style.display = 'none';
}
<?php
				die();
				break;
			case 'aktt_css':
				header("Content-type: text/css");
?>
#aktt_tweet_form {
	margin: 0;
	padding: 5px 0;
}
#aktt_tweet_form fieldset {
	border: 0;
}
#aktt_tweet_form fieldset #aktt_tweet_submit {
	float: right;
	margin-right: 10px;
}
#aktt_tweet_form fieldset #aktt_char_count {
	color: #666;
}
#aktt_tweet_posted_msg {
	background: #ffc;
	display: none;
	margin: 0 0 5px 0;
	padding: 5px;
}
#aktt_tweet_form div.clear {
	clear: both;
	float: none;
}
<?php
				die();
				break;
			case 'aktt_js_admin':
				header("Content-type: text/javascript");
?>
function akttTestLogin() {
	var username = encodeURIComponent($('aktt_twitter_username').value);
	var password = encodeURIComponent($('aktt_twitter_password').value);
	var result = $('aktt_login_test_result');
	result.className = 'aktt_login_result_wait';
	result.innerHTML = '<?php _e('Testing...', 'twitter-tools'); ?>';
	var akttAjax = new Ajax.Updater(
		result,
		"<?php bloginfo('wpurl'); ?>/index.php",
		{
			method: "post",
			parameters: "ak_action=aktt_login_test&aktt_twitter_username=" + username + "&aktt_twitter_password=" + password,
			onComplete: akttTestLoginResult
		}
	);
}
function akttTestLoginResult() {
	$('aktt_login_test_result').className = 'aktt_login_result';
	Fat.fade_element('aktt_login_test_result');
}
<?php
				die();
				break;
			case 'aktt_css_admin':
				header("Content-type: text/css");
?>
#aktt_tweet_form {
	margin: 0;
	padding: 5px 0;
}
#aktt_tweet_form fieldset {
	border: 0;
}
#aktt_tweet_form fieldset textarea {
	width: 95%;
}
#aktt_tweet_form fieldset #aktt_tweet_submit {
	float: right;
	margin-right: 50px;
}
#aktt_tweet_form fieldset #aktt_char_count {
	color: #666;
}
#ak_twittertools fieldset.options p span {
	color: #666;
	display: block;
}
#ak_readme {
	height: 300px;
	width: 95%;
}
#ak_twittertools #aktt_login_test_result {
	display: inline;
	padding: 3px;
}
#ak_twittertools fieldset.options p span.aktt_login_result_wait {
	background: #ffc;
}
#ak_twittertools fieldset.options p span.aktt_login_result {
	background: #CFEBF7;
	color: #000;
}
<?php
				die();
				break;
		}
	}
	if (!empty($_POST['ak_action'])) {
		switch($_POST['ak_action']) {
			case 'aktt_update_settings':
				$aktt->populate_settings();
				$aktt->update_settings();
				header('Location: '.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=twitter-tools.php&updated=true');
				die();
				break;
			case 'aktt_post_tweet_sidebar':
				if (!empty($_POST['aktt_tweet_text']) && current_user_can('publish_posts')) {
					$tweet = new aktt_tweet();
					$tweet->tw_text = stripslashes($_POST['aktt_tweet_text']);
					if ($aktt->do_tweet($tweet)) {
						die(__('Tweet posted.', 'twitter-tools'));
					}
					else {
						die(__('Tweet post failed.', 'twitter-tools'));
					}
				}
				break;
			case 'aktt_post_tweet_admin':
				if (!empty($_POST['aktt_tweet_text']) && current_user_can('publish_posts')) {
					$tweet = new aktt_tweet();
					$tweet->tw_text = stripslashes($_POST['aktt_tweet_text']);
					if ($aktt->do_tweet($tweet)) {
						header('Location: '.get_bloginfo('wpurl').'/wp-admin/post-new.php?page=twitter-tools.php&tweet-posted=true');
					}
					else {
						wp_die(__('Oops, your tweet was not posted. Please check your username and password and that Twitter is up and running happily.', 'twitter-tools'));
					}
					die();
				}
				break;
			case 'aktt_login_test':
				$test = @aktt_login_test(
					@stripslashes($_POST['aktt_twitter_username'])
					, @stripslashes($_POST['aktt_twitter_password'])
				);
				if ($test) {
					die(__("Login succeeded, you're good to go.", 'twitter-tools'));
				}
				else {
					die(__("Login failed, double-check that username and password.", 'twitter-tools'));
				}
				break;
		}
	}
}
add_action('init', 'aktt_request_handler', 10);

function aktt_admin_tweet_form() {
	global $aktt;
	if ( $_GET['tweet-posted'] ) {
		print('
			<div id="message" class="updated fade">
				<p>'.__('Tweet posted.', 'twitter-tools').'</p>
			</div>
		');
	}
	if (empty($aktt->twitter_username) || empty($aktt->twitter_password)) {
		print('
<p>Please enter your <a href="http://twitter.com">Twitter</a> account information in your <a href="options-general.php?page=twitter-tools.php">Twitter Tools Options</a>.</p>		
		');
	}
	print('
		<div class="wrap">
			<h2>'.__('Write Tweet', 'twitter-tools').'</h2>
			<p>This will create a new \'tweet\' in <a href="http://twitter.com">Twitter</a> using the account information in your <a href="options-general.php?page=twitter-tools.php">Twitter Tools Options</a>.</p>
			'.aktt_tweet_form('textarea').'
		</div>
	');
}

function aktt_options_form() {
	global $wpdb, $aktt;

	$categories = get_categories('hide_empty=0');
	$cat_options = '';
	foreach ($categories as $category) {
		if ($category->term_id == $aktt->blog_post_category) {
			$selected = 'selected="selected"';
		}
		else {
			$selected = '';
		}
		$cat_options .= "\n\t<option value='$category->term_id' $selected>$category->name</option>";
	}

	$authors = get_users_of_blog();
	$author_options = '';
	foreach ($authors as $user) {
		$usero = new WP_User($user->user_id);
		$author = $usero->data;
		// Only list users who are allowed to publish
		if (! $usero->has_cap('publish_posts')) {
			continue;
		}
		if ($author->ID == $aktt->blog_post_author) {
			$selected = 'selected="selected"';
		}
		else {
			$selected = '';
		}
		$author_options .= "\n\t<option value='$author->ID' $selected>$author->user_nicename</option>";
	}
	$yes_no = array(
		'create_blog_posts'
		, 'create_digest'
		, 'notify_twitter'
		, 'tweet_from_sidebar'
		, 'give_tt_credit'
	);
	foreach ($yes_no as $key) {
		$var = $key.'_options';
		if ($aktt->$key == '0') {
			$$var = '
				<option value="0" selected="selected">'.__('No', 'twitter-tools').'</option>
				<option value="1">'.__('Yes', 'twitter-tools').'</option>
			';
		}
		else {
			$$var = '
				<option value="0">'.__('No', 'twitter-tools').'</option>
				<option value="1" selected="selected">'.__('Yes', 'twitter-tools').'</option>
			';
		}
	}
	if ( $_GET['tweets-updated'] ) {
		print('
			<div id="message" class="updated fade">
				<p>'.__('Tweets updated.', 'twitter-tools').'</p>
			</div>
		');
	}
	print('
			<div class="wrap">
				<h2>'.__('Twitter Tools Options', 'twitter-tools').'</h2>
				<form id="ak_twittertools" name="ak_twittertools" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php" method="post">
					<fieldset class="options">
						<p>
							<label for="aktt_twitter_username">'.__('Twitter Username:', 'twitter-tools').'</label>
							<input type="text" size="25" name="aktt_twitter_username" id="aktt_twitter_username" value="'.$aktt->twitter_username.'" />
						</p>
						<p>
							<label for="aktt_twitter_password">'.__('Twitter Password:', 'twitter-tools').'</label>
							<input type="password" size="25" name="aktt_twitter_password" id="aktt_twitter_password" value="'.$aktt->twitter_password.'" />
						</p>
						<p>
							<input type="button" name="aktt_login_test" id="aktt_login_test" value="'.__('Test Login Info', 'twitter-tools').'" onclick="akttTestLogin(); return false;" />
							<span id="aktt_login_test_result"></span>
						</p>
						<p>
							<label for="aktt_notify_twitter">'.__('Create a tweet when you post in your blog?', 'twitter-tools').'</label>
							<select name="aktt_notify_twitter" id="aktt_notify_twitter">'.$notify_twitter_options.'</select>
						</p>
						<p>
							<label for="aktt_create_blog_posts">'.__('Create a blog post from each of your tweets?', 'twitter-tools').'</label>
							<select name="aktt_create_blog_posts" id="aktt_create_blog_posts">'.$create_blog_posts_options.'</select>
						</p>
						<p>
							<label for="aktt_create_digest">'.__('Create a daily digest blog post from your tweets?', 'twitter-tools').'</label>
							<select name="aktt_create_digest" id="aktt_create_digest">'.$create_digest_options.'</select>
						</p>
						<p>
							<label for="aktt_digest_title">'.__('Title for digest posts:', 'twitter-tools').'</label>
							<input type="text" size="30" name="aktt_digest_title" id="aktt_digest_title" value="'.$aktt->digest_title.'" />
							<span>'.__('Include %s where you want the date. Example: Tweets on %s', 'twitter-tools').'</span>
						</p>
						<p>
							<label for="aktt_blog_post_category">'.__('Select a category for your tweet posts:', 'twitter-tools').'</label>
							<select name="aktt_blog_post_category" id="aktt_blog_post_category">'.$cat_options.'</select>
						</p>
						<p>
							<label for="aktt_blog_post_author">'.__('Select an author for your tweet posts:', 'twitter-tools').'</label>
							<select name="aktt_blog_post_author" id="aktt_blog_post_author">'.$author_options.'</select>
						</p>
						<p>
							<label for="aktt_sidebar_tweet_count">'.__('Tweets to show in sidebar:', 'twitter-tools').'</label>
							<input type="text" size="3" name="aktt_sidebar_tweet_count" id="aktt_sidebar_tweet_count" value="'.$aktt->sidebar_tweet_count.'" />
							<span>'.__('Numbers only please.', 'twitter-tools').'</span>
						</p>
						<p>
							<label for="aktt_tweet_from_sidebar">'.__('Create tweets from your sidebar?', 'twitter-tools').'</label>
							<select name="aktt_tweet_from_sidebar" id="aktt_tweet_from_sidebar">'.$tweet_from_sidebar_options.'</select>
						</p>
						<p>
							<label for="aktt_give_tt_credit">'.__('Give Twitter Tools credit?', 'twitter-tools').'</label>
							<select name="aktt_give_tt_credit" id="aktt_give_tt_credit">'.$give_tt_credit_options.'</select>
						</p>
						<input type="hidden" name="ak_action" value="aktt_update_settings" />
					</fieldset>
					<p class="submit">
						<input type="submit" name="submit" value="'.__('Update Twitter Tools Options', 'twitter-tools').'" />
					</p>
				</form>
				<h2>'.__('Update Tweets', 'twitter-tools').'</h2>
				<form name="ak_twittertools_updatetweets" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php" method="get">
					<p>'.__('Use this button to manually update your tweets.', 'twitter-tools').'</p>
					<p class="submit">
						<input type="submit" name="submit" value="'.__('Update Tweets', 'twitter-tools').'" />
						<input type="hidden" name="ak_action" value="aktt_update_tweets" />
					</p>
				</form>
				<h2>'.__('README', 'twitter-tools').'</h2>
				<p>'.__('Find answers to common questions here.', 'twitter-tools').'</p>
				<iframe id="ak_readme" src="http://alexking.org/projects/wordpress/readme?project=twitter-tools"></iframe>
			</div>
	');
}

function aktt_menu_items() {
	if (current_user_can('manage_options')) {
		add_options_page(
			__('Twitter Tools Options', 'twitter-tools')
			, __('Twitter Tools', 'twitter-tools')
			, 10
			, basename(__FILE__)
			, 'aktt_options_form'
		);
	}
	if (current_user_can('publish_posts')) {
		add_submenu_page(
			'post-new.php'
			, __('Write Tweet', 'twitter-tools')
			, __('Write Tweet', 'twitter-tools')
			, 10
			, basename(__FILE__)
			, 'aktt_admin_tweet_form'
		);
	}
}
add_action('admin_menu', 'aktt_menu_items');

if (!function_exists('trim_add_elipsis')) {
	function trim_add_elipsis($string, $limit = 100) {
		if (strlen($string) > $limit) {
			$string = substr($string, 0, $limit)."...";
		}
		return $string;
	}
}

if (!function_exists('ak_gmmktime')) {
	function ak_gmmktime() {
		return gmmktime() - get_option('gmt_offset') * 3600;
	}
}

/**

based on: http://www.gyford.com/phil/writing/2006/12/02/quick_twitter.php

	 * Returns a relative date, eg "4 hrs ago".
	 *
	 * Assumes the passed-in can be parsed by strtotime.
	 * Precision could be one of:
	 * 	1	5 hours, 3 minutes, 2 seconds ago (not yet implemented).
	 * 	2	5 hours, 3 minutes
	 * 	3	5 hours
	 *
	 * This is all a little overkill, but copied from other places I've used it.
	 * Also superfluous, now I've noticed that the Twitter API includes something
	 * similar, but this version is more accurate and less verbose.
	 *
	 * @access private.
	 * @param string date In a format parseable by strtotime().
	 * @param integer precision
	 * @return string
	 */
function aktt_relativeTime ($date, $precision=2)
{

	$now = time();

	$time = gmmktime(
		substr($date, 11, 2)
		, substr($date, 14, 2)
		, substr($date, 17, 2)
		, substr($date, 5, 2)
		, substr($date, 8, 2)
		, substr($date, 0, 4)
	);

	$time = strtotime(date('Y-m-d H:i:s', $time));

	$diff 	=  $now - $time;

	$months	=  floor($diff/2419200);
	$diff 	-= $months * 2419200;
	$weeks 	=  floor($diff/604800);
	$diff	-= $weeks*604800;
	$days 	=  floor($diff/86400);
	$diff 	-= $days * 86400;
	$hours 	=  floor($diff/3600);
	$diff 	-= $hours * 3600;
	$minutes = floor($diff/60);
	$diff 	-= $minutes * 60;
	$seconds = $diff;

	if ($months > 0) {
		return date('Y-m-d', $time);
	} else {
		$relative_date = '';
		if ($weeks > 0) {
			// Weeks and days
			$relative_date .= ($relative_date?', ':'').$weeks.' week'.($weeks>1?'s':'');
			if ($precision <= 2) {
				$relative_date .= $days>0?($relative_date?', ':'').$days.' day'.($days>1?'s':''):'';
				if ($precision == 1) {
					$relative_date .= $hours>0?($relative_date?', ':'').$hours.' hr'.($hours>1?'s':''):'';
				}
			}
		} elseif ($days > 0) {
			// days and hours
			$relative_date .= ($relative_date?', ':'').$days.' day'.($days>1?'s':'');
			if ($precision <= 2) {
				$relative_date .= $hours>0?($relative_date?', ':'').$hours.' hr'.($hours>1?'s':''):'';
				if ($precision == 1) {
					$relative_date .= $minutes>0?($relative_date?', ':'').$minutes.' min'.($minutes>1?'s':''):'';
				}
			}
		} elseif ($hours > 0) {
			// hours and minutes
			$relative_date .= ($relative_date?', ':'').$hours.' hr'.($hours>1?'s':'');
			if ($precision <= 2) {
				$relative_date .= $minutes>0?($relative_date?', ':'').$minutes.' min'.($minutes>1?'s':''):'';
				if ($precision == 1) {
					$relative_date .= $seconds>0?($relative_date?', ':'').$seconds.' sec'.($seconds>1?'s':''):'';
				}
			}
		} elseif ($minutes > 0) {
			// minutes only
			$relative_date .= ($relative_date?', ':'').$minutes.' min'.($minutes>1?'s':'');
			if ($precision == 1) {
				$relative_date .= $seconds>0?($relative_date?', ':'').$seconds.' sec'.($seconds>1?'s':''):'';
			}
		} else {
			// seconds only
			$relative_date .= ($relative_date?', ':'').$seconds.' sec'.($seconds>1?'s':'');
		}
	}

	// Return relative date and add proper verbiage
	return sprintf(__('%s ago', 'twitter-tools'), $relative_date);
}
if (!class_exists('Services_JSON')) {

// PEAR JSON class

/**
* Converts to and from JSON format.
*
* JSON (JavaScript Object Notation) is a lightweight data-interchange
* format. It is easy for humans to read and write. It is easy for machines
* to parse and generate. It is based on a subset of the JavaScript
* Programming Language, Standard ECMA-262 3rd Edition - December 1999.
* This feature can also be found in  Python. JSON is a text format that is
* completely language independent but uses conventions that are familiar
* to programmers of the C-family of languages, including C, C++, C#, Java,
* JavaScript, Perl, TCL, and many others. These properties make JSON an
* ideal data-interchange language.
*
* This package provides a simple encoder and decoder for JSON notation. It
* is intended for use with client-side Javascript applications that make
* use of HTTPRequest to perform server communication functions - data can
* be encoded into JSON notation for use in a client-side javascript, or
* decoded from incoming Javascript requests. JSON format is native to
* Javascript, and can be directly eval()'ed with no further parsing
* overhead
*
* All strings should be in ASCII or UTF-8 format!
*
* LICENSE: Redistribution and use in source and binary forms, with or
* without modification, are permitted provided that the following
* conditions are met: Redistributions of source code must retain the
* above copyright notice, this list of conditions and the following
* disclaimer. Redistributions in binary form must reproduce the above
* copyright notice, this list of conditions and the following disclaimer
* in the documentation and/or other materials provided with the
* distribution.
*
* THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED
* WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
* MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN
* NO EVENT SHALL CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
* INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
* BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
* OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
* TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
* USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
* DAMAGE.
*
* @category
* @package     Services_JSON
* @author      Michal Migurski <mike-json@teczno.com>
* @author      Matt Knapp <mdknapp[at]gmail[dot]com>
* @author      Brett Stimmerman <brettstimmerman[at]gmail[dot]com>
* @copyright   2005 Michal Migurski
* @version     CVS: $Id: JSON.php,v 1.31 2006/06/28 05:54:17 migurski Exp $
* @license     http://www.opensource.org/licenses/bsd-license.php
* @link        http://pear.php.net/pepr/pepr-proposal-show.php?id=198
*/

/**
* Marker constant for Services_JSON::decode(), used to flag stack state
*/
define('SERVICES_JSON_SLICE',   1);

/**
* Marker constant for Services_JSON::decode(), used to flag stack state
*/
define('SERVICES_JSON_IN_STR',  2);

/**
* Marker constant for Services_JSON::decode(), used to flag stack state
*/
define('SERVICES_JSON_IN_ARR',  3);

/**
* Marker constant for Services_JSON::decode(), used to flag stack state
*/
define('SERVICES_JSON_IN_OBJ',  4);

/**
* Marker constant for Services_JSON::decode(), used to flag stack state
*/
define('SERVICES_JSON_IN_CMT', 5);

/**
* Behavior switch for Services_JSON::decode()
*/
define('SERVICES_JSON_LOOSE_TYPE', 16);

/**
* Behavior switch for Services_JSON::decode()
*/
define('SERVICES_JSON_SUPPRESS_ERRORS', 32);

/**
* Converts to and from JSON format.
*
* Brief example of use:
*
* <code>
* // create a new instance of Services_JSON
* $json = new Services_JSON();
*
* // convert a complexe value to JSON notation, and send it to the browser
* $value = array('foo', 'bar', array(1, 2, 'baz'), array(3, array(4)));
* $output = $json->encode($value);
*
* print($output);
* // prints: ["foo","bar",[1,2,"baz"],[3,[4]]]
*
* // accept incoming POST data, assumed to be in JSON notation
* $input = file_get_contents('php://input', 1000000);
* $value = $json->decode($input);
* </code>
*/
class Services_JSON
{
   /**
    * constructs a new JSON instance
    *
    * @param    int     $use    object behavior flags; combine with boolean-OR
    *
    *                           possible values:
    *                           - SERVICES_JSON_LOOSE_TYPE:  loose typing.
    *                                   "{...}" syntax creates associative arrays
    *                                   instead of objects in decode().
    *                           - SERVICES_JSON_SUPPRESS_ERRORS:  error suppression.
    *                                   Values which can't be encoded (e.g. resources)
    *                                   appear as NULL instead of throwing errors.
    *                                   By default, a deeply-nested resource will
    *                                   bubble up with an error, so all return values
    *                                   from encode() should be checked with isError()
    */
    function Services_JSON($use = 0)
    {
        $this->use = $use;
    }

   /**
    * convert a string from one UTF-16 char to one UTF-8 char
    *
    * Normally should be handled by mb_convert_encoding, but
    * provides a slower PHP-only method for installations
    * that lack the multibye string extension.
    *
    * @param    string  $utf16  UTF-16 character
    * @return   string  UTF-8 character
    * @access   private
    */
    function utf162utf8($utf16)
    {
        // oh please oh please oh please oh please oh please
        if(function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($utf16, 'UTF-8', 'UTF-16');
        }

        $bytes = (ord($utf16{0}) << 8) | ord($utf16{1});

        switch(true) {
            case ((0x7F & $bytes) == $bytes):
                // this case should never be reached, because we are in ASCII range
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0x7F & $bytes);

            case (0x07FF & $bytes) == $bytes:
                // return a 2-byte UTF-8 character
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0xC0 | (($bytes >> 6) & 0x1F))
                     . chr(0x80 | ($bytes & 0x3F));

            case (0xFFFF & $bytes) == $bytes:
                // return a 3-byte UTF-8 character
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0xE0 | (($bytes >> 12) & 0x0F))
                     . chr(0x80 | (($bytes >> 6) & 0x3F))
                     . chr(0x80 | ($bytes & 0x3F));
        }

        // ignoring UTF-32 for now, sorry
        return '';
    }

   /**
    * convert a string from one UTF-8 char to one UTF-16 char
    *
    * Normally should be handled by mb_convert_encoding, but
    * provides a slower PHP-only method for installations
    * that lack the multibye string extension.
    *
    * @param    string  $utf8   UTF-8 character
    * @return   string  UTF-16 character
    * @access   private
    */
    function utf82utf16($utf8)
    {
        // oh please oh please oh please oh please oh please
        if(function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($utf8, 'UTF-16', 'UTF-8');
        }

        switch(strlen($utf8)) {
            case 1:
                // this case should never be reached, because we are in ASCII range
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return $utf8;

            case 2:
                // return a UTF-16 character from a 2-byte UTF-8 char
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0x07 & (ord($utf8{0}) >> 2))
                     . chr((0xC0 & (ord($utf8{0}) << 6))
                         | (0x3F & ord($utf8{1})));

            case 3:
                // return a UTF-16 character from a 3-byte UTF-8 char
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr((0xF0 & (ord($utf8{0}) << 4))
                         | (0x0F & (ord($utf8{1}) >> 2)))
                     . chr((0xC0 & (ord($utf8{1}) << 6))
                         | (0x7F & ord($utf8{2})));
        }

        // ignoring UTF-32 for now, sorry
        return '';
    }

   /**
    * encodes an arbitrary variable into JSON format
    *
    * @param    mixed   $var    any number, boolean, string, array, or object to be encoded.
    *                           see argument 1 to Services_JSON() above for array-parsing behavior.
    *                           if var is a strng, note that encode() always expects it
    *                           to be in ASCII or UTF-8 format!
    *
    * @return   mixed   JSON string representation of input var or an error if a problem occurs
    * @access   public
    */
    function encode($var)
    {
        switch (gettype($var)) {
            case 'boolean':
                return $var ? 'true' : 'false';

            case 'NULL':
                return 'null';

            case 'integer':
                return (int) $var;

            case 'double':
            case 'float':
                return (float) $var;

            case 'string':
                // STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT
                $ascii = '';
                $strlen_var = strlen($var);

               /*
                * Iterate over every character in the string,
                * escaping with a slash or encoding to UTF-8 where necessary
                */
                for ($c = 0; $c < $strlen_var; ++$c) {

                    $ord_var_c = ord($var{$c});

                    switch (true) {
                        case $ord_var_c == 0x08:
                            $ascii .= '\b';
                            break;
                        case $ord_var_c == 0x09:
                            $ascii .= '\t';
                            break;
                        case $ord_var_c == 0x0A:
                            $ascii .= '\n';
                            break;
                        case $ord_var_c == 0x0C:
                            $ascii .= '\f';
                            break;
                        case $ord_var_c == 0x0D:
                            $ascii .= '\r';
                            break;

                        case $ord_var_c == 0x22:
                        case $ord_var_c == 0x2F:
                        case $ord_var_c == 0x5C:
                            // double quote, slash, slosh
                            $ascii .= '\\'.$var{$c};
                            break;

                        case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
                            // characters U-00000000 - U-0000007F (same as ASCII)
                            $ascii .= $var{$c};
                            break;

                        case (($ord_var_c & 0xE0) == 0xC0):
                            // characters U-00000080 - U-000007FF, mask 110XXXXX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c, ord($var{$c + 1}));
                            $c += 1;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;

                        case (($ord_var_c & 0xF0) == 0xE0):
                            // characters U-00000800 - U-0000FFFF, mask 1110XXXX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}));
                            $c += 2;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;

                        case (($ord_var_c & 0xF8) == 0xF0):
                            // characters U-00010000 - U-001FFFFF, mask 11110XXX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}),
                                         ord($var{$c + 3}));
                            $c += 3;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;

                        case (($ord_var_c & 0xFC) == 0xF8):
                            // characters U-00200000 - U-03FFFFFF, mask 111110XX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}),
                                         ord($var{$c + 3}),
                                         ord($var{$c + 4}));
                            $c += 4;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;

                        case (($ord_var_c & 0xFE) == 0xFC):
                            // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}),
                                         ord($var{$c + 3}),
                                         ord($var{$c + 4}),
                                         ord($var{$c + 5}));
                            $c += 5;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;
                    }
                }

                return '"'.$ascii.'"';

            case 'array':
               /*
                * As per JSON spec if any array key is not an integer
                * we must treat the the whole array as an object. We
                * also try to catch a sparsely populated associative
                * array with numeric keys here because some JS engines
                * will create an array with empty indexes up to
                * max_index which can cause memory issues and because
                * the keys, which may be relevant, will be remapped
                * otherwise.
                *
                * As per the ECMA and JSON specification an object may
                * have any string as a property. Unfortunately due to
                * a hole in the ECMA specification if the key is a
                * ECMA reserved word or starts with a digit the
                * parameter is only accessible using ECMAScript's
                * bracket notation.
                */

                // treat as a JSON object
                if (is_array($var) && count($var) && (array_keys($var) !== range(0, sizeof($var) - 1))) {
                    $properties = array_map(array($this, 'name_value'),
                                            array_keys($var),
                                            array_values($var));

                    foreach($properties as $property) {
                        if(Services_JSON::isError($property)) {
                            return $property;
                        }
                    }

                    return '{' . join(',', $properties) . '}';
                }

                // treat it like a regular array
                $elements = array_map(array($this, 'encode'), $var);

                foreach($elements as $element) {
                    if(Services_JSON::isError($element)) {
                        return $element;
                    }
                }

                return '[' . join(',', $elements) . ']';

            case 'object':
                $vars = get_object_vars($var);

                $properties = array_map(array($this, 'name_value'),
                                        array_keys($vars),
                                        array_values($vars));

                foreach($properties as $property) {
                    if(Services_JSON::isError($property)) {
                        return $property;
                    }
                }

                return '{' . join(',', $properties) . '}';

            default:
                return ($this->use & SERVICES_JSON_SUPPRESS_ERRORS)
                    ? 'null'
                    : new Services_JSON_Error(gettype($var)." can not be encoded as JSON string");
        }
    }

   /**
    * array-walking function for use in generating JSON-formatted name-value pairs
    *
    * @param    string  $name   name of key to use
    * @param    mixed   $value  reference to an array element to be encoded
    *
    * @return   string  JSON-formatted name-value pair, like '"name":value'
    * @access   private
    */
    function name_value($name, $value)
    {
        $encoded_value = $this->encode($value);

        if(Services_JSON::isError($encoded_value)) {
            return $encoded_value;
        }

        return $this->encode(strval($name)) . ':' . $encoded_value;
    }

   /**
    * reduce a string by removing leading and trailing comments and whitespace
    *
    * @param    $str    string      string value to strip of comments and whitespace
    *
    * @return   string  string value stripped of comments and whitespace
    * @access   private
    */
    function reduce_string($str)
    {
        $str = preg_replace(array(

                // eliminate single line comments in '// ...' form
                '#^\s*//(.+)$#m',

                // eliminate multi-line comments in '/* ... */' form, at start of string
                '#^\s*/\*(.+)\*/#Us',

                // eliminate multi-line comments in '/* ... */' form, at end of string
                '#/\*(.+)\*/\s*$#Us'

            ), '', $str);

        // eliminate extraneous space
        return trim($str);
    }

   /**
    * decodes a JSON string into appropriate variable
    *
    * @param    string  $str    JSON-formatted string
    *
    * @return   mixed   number, boolean, string, array, or object
    *                   corresponding to given JSON input string.
    *                   See argument 1 to Services_JSON() above for object-output behavior.
    *                   Note that decode() always returns strings
    *                   in ASCII or UTF-8 format!
    * @access   public
    */
    function decode($str)
    {
        $str = $this->reduce_string($str);

        switch (strtolower($str)) {
            case 'true':
                return true;

            case 'false':
                return false;

            case 'null':
                return null;

            default:
                $m = array();

                if (is_numeric($str)) {
                    // Lookie-loo, it's a number

                    // This would work on its own, but I'm trying to be
                    // good about returning integers where appropriate:
                    // return (float)$str;

                    // Return float or int, as appropriate
                    return ((float)$str == (integer)$str)
                        ? (integer)$str
                        : (float)$str;

                } elseif (preg_match('/^("|\').*(\1)$/s', $str, $m) && $m[1] == $m[2]) {
                    // STRINGS RETURNED IN UTF-8 FORMAT
                    $delim = substr($str, 0, 1);
                    $chrs = substr($str, 1, -1);
                    $utf8 = '';
                    $strlen_chrs = strlen($chrs);

                    for ($c = 0; $c < $strlen_chrs; ++$c) {

                        $substr_chrs_c_2 = substr($chrs, $c, 2);
                        $ord_chrs_c = ord($chrs{$c});

                        switch (true) {
                            case $substr_chrs_c_2 == '\b':
                                $utf8 .= chr(0x08);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\t':
                                $utf8 .= chr(0x09);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\n':
                                $utf8 .= chr(0x0A);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\f':
                                $utf8 .= chr(0x0C);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\r':
                                $utf8 .= chr(0x0D);
                                ++$c;
                                break;

                            case $substr_chrs_c_2 == '\\"':
                            case $substr_chrs_c_2 == '\\\'':
                            case $substr_chrs_c_2 == '\\\\':
                            case $substr_chrs_c_2 == '\\/':
                                if (($delim == '"' && $substr_chrs_c_2 != '\\\'') ||
                                   ($delim == "'" && $substr_chrs_c_2 != '\\"')) {
                                    $utf8 .= $chrs{++$c};
                                }
                                break;

                            case preg_match('/\\\u[0-9A-F]{4}/i', substr($chrs, $c, 6)):
                                // single, escaped unicode character
                                $utf16 = chr(hexdec(substr($chrs, ($c + 2), 2)))
                                       . chr(hexdec(substr($chrs, ($c + 4), 2)));
                                $utf8 .= $this->utf162utf8($utf16);
                                $c += 5;
                                break;

                            case ($ord_chrs_c >= 0x20) && ($ord_chrs_c <= 0x7F):
                                $utf8 .= $chrs{$c};
                                break;

                            case ($ord_chrs_c & 0xE0) == 0xC0:
                                // characters U-00000080 - U-000007FF, mask 110XXXXX
                                //see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 2);
                                ++$c;
                                break;

                            case ($ord_chrs_c & 0xF0) == 0xE0:
                                // characters U-00000800 - U-0000FFFF, mask 1110XXXX
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 3);
                                $c += 2;
                                break;

                            case ($ord_chrs_c & 0xF8) == 0xF0:
                                // characters U-00010000 - U-001FFFFF, mask 11110XXX
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 4);
                                $c += 3;
                                break;

                            case ($ord_chrs_c & 0xFC) == 0xF8:
                                // characters U-00200000 - U-03FFFFFF, mask 111110XX
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 5);
                                $c += 4;
                                break;

                            case ($ord_chrs_c & 0xFE) == 0xFC:
                                // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 6);
                                $c += 5;
                                break;

                        }

                    }

                    return $utf8;

                } elseif (preg_match('/^\[.*\]$/s', $str) || preg_match('/^\{.*\}$/s', $str)) {
                    // array, or object notation

                    if ($str{0} == '[') {
                        $stk = array(SERVICES_JSON_IN_ARR);
                        $arr = array();
                    } else {
                        if ($this->use & SERVICES_JSON_LOOSE_TYPE) {
                            $stk = array(SERVICES_JSON_IN_OBJ);
                            $obj = array();
                        } else {
                            $stk = array(SERVICES_JSON_IN_OBJ);
                            $obj = new stdClass();
                        }
                    }

                    array_push($stk, array('what'  => SERVICES_JSON_SLICE,
                                           'where' => 0,
                                           'delim' => false));

                    $chrs = substr($str, 1, -1);
                    $chrs = $this->reduce_string($chrs);

                    if ($chrs == '') {
                        if (reset($stk) == SERVICES_JSON_IN_ARR) {
                            return $arr;

                        } else {
                            return $obj;

                        }
                    }

                    //print("\nparsing {$chrs}\n");

                    $strlen_chrs = strlen($chrs);

                    for ($c = 0; $c <= $strlen_chrs; ++$c) {

                        $top = end($stk);
                        $substr_chrs_c_2 = substr($chrs, $c, 2);

                        if (($c == $strlen_chrs) || (($chrs{$c} == ',') && ($top['what'] == SERVICES_JSON_SLICE))) {
                            // found a comma that is not inside a string, array, etc.,
                            // OR we've reached the end of the character list
                            $slice = substr($chrs, $top['where'], ($c - $top['where']));
                            array_push($stk, array('what' => SERVICES_JSON_SLICE, 'where' => ($c + 1), 'delim' => false));
                            //print("Found split at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                            if (reset($stk) == SERVICES_JSON_IN_ARR) {
                                // we are in an array, so just push an element onto the stack
                                array_push($arr, $this->decode($slice));

                            } elseif (reset($stk) == SERVICES_JSON_IN_OBJ) {
                                // we are in an object, so figure
                                // out the property name and set an
                                // element in an associative array,
                                // for now
                                $parts = array();
                                
                                if (preg_match('/^\s*(["\'].*[^\\\]["\'])\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
                                    // "name":value pair
                                    $key = $this->decode($parts[1]);
                                    $val = $this->decode($parts[2]);

                                    if ($this->use & SERVICES_JSON_LOOSE_TYPE) {
                                        $obj[$key] = $val;
                                    } else {
                                        $obj->$key = $val;
                                    }
                                } elseif (preg_match('/^\s*(\w+)\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
                                    // name:value pair, where name is unquoted
                                    $key = $parts[1];
                                    $val = $this->decode($parts[2]);

                                    if ($this->use & SERVICES_JSON_LOOSE_TYPE) {
                                        $obj[$key] = $val;
                                    } else {
                                        $obj->$key = $val;
                                    }
                                }

                            }

                        } elseif ((($chrs{$c} == '"') || ($chrs{$c} == "'")) && ($top['what'] != SERVICES_JSON_IN_STR)) {
                            // found a quote, and we are not inside a string
                            array_push($stk, array('what' => SERVICES_JSON_IN_STR, 'where' => $c, 'delim' => $chrs{$c}));
                            //print("Found start of string at {$c}\n");

                        } elseif (($chrs{$c} == $top['delim']) &&
                                 ($top['what'] == SERVICES_JSON_IN_STR) &&
                                 ((strlen(substr($chrs, 0, $c)) - strlen(rtrim(substr($chrs, 0, $c), '\\'))) % 2 != 1)) {
                            // found a quote, we're in a string, and it's not escaped
                            // we know that it's not escaped becase there is _not_ an
                            // odd number of backslashes at the end of the string so far
                            array_pop($stk);
                            //print("Found end of string at {$c}: ".substr($chrs, $top['where'], (1 + 1 + $c - $top['where']))."\n");

                        } elseif (($chrs{$c} == '[') &&
                                 in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
                            // found a left-bracket, and we are in an array, object, or slice
                            array_push($stk, array('what' => SERVICES_JSON_IN_ARR, 'where' => $c, 'delim' => false));
                            //print("Found start of array at {$c}\n");

                        } elseif (($chrs{$c} == ']') && ($top['what'] == SERVICES_JSON_IN_ARR)) {
                            // found a right-bracket, and we're in an array
                            array_pop($stk);
                            //print("Found end of array at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                        } elseif (($chrs{$c} == '{') &&
                                 in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
                            // found a left-brace, and we are in an array, object, or slice
                            array_push($stk, array('what' => SERVICES_JSON_IN_OBJ, 'where' => $c, 'delim' => false));
                            //print("Found start of object at {$c}\n");

                        } elseif (($chrs{$c} == '}') && ($top['what'] == SERVICES_JSON_IN_OBJ)) {
                            // found a right-brace, and we're in an object
                            array_pop($stk);
                            //print("Found end of object at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                        } elseif (($substr_chrs_c_2 == '/*') &&
                                 in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
                            // found a comment start, and we are in an array, object, or slice
                            array_push($stk, array('what' => SERVICES_JSON_IN_CMT, 'where' => $c, 'delim' => false));
                            $c++;
                            //print("Found start of comment at {$c}\n");

                        } elseif (($substr_chrs_c_2 == '*/') && ($top['what'] == SERVICES_JSON_IN_CMT)) {
                            // found a comment end, and we're in one now
                            array_pop($stk);
                            $c++;

                            for ($i = $top['where']; $i <= $c; ++$i)
                                $chrs = substr_replace($chrs, ' ', $i, 1);

                            //print("Found end of comment at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                        }

                    }

                    if (reset($stk) == SERVICES_JSON_IN_ARR) {
                        return $arr;

                    } elseif (reset($stk) == SERVICES_JSON_IN_OBJ) {
                        return $obj;

                    }

                }
        }
    }

    /**
     * @todo Ultimately, this should just call PEAR::isError()
     */
    function isError($data, $code = null)
    {
        if (class_exists('pear')) {
            return PEAR::isError($data, $code);
        } elseif (is_object($data) && (get_class($data) == 'services_json_error' ||
                                 is_subclass_of($data, 'services_json_error'))) {
            return true;
        }

        return false;
    }
}

if (class_exists('PEAR_Error')) {

    class Services_JSON_Error extends PEAR_Error
    {
        function Services_JSON_Error($message = 'unknown error', $code = null,
                                     $mode = null, $options = null, $userinfo = null)
        {
            parent::PEAR_Error($message, $code, $mode, $options, $userinfo);
        }
    }

} else {

    /**
     * @todo Ultimately, this class shall be descended from PEAR_Error
     */
    class Services_JSON_Error
    {
        function Services_JSON_Error($message = 'unknown error', $code = null,
                                     $mode = null, $options = null, $userinfo = null)
        {

        }
    }

}

}

?>