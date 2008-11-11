<?php
/*
Plugin Name: Twitter Tools
Plugin URI: http://alexking.org/projects/wordpress
Description: A complete integration between your WordPress blog and <a href="http://twitter.com">Twitter</a>. Bring your tweets into your blog and pass your blog posts to Twitter. <a href="options-general.php?page=twitter-tools.php">Configure your settings here</a>.
Version: 1.5b3
Author: Alex King
Author URI: http://alexking.org
*/

// Copyright (c) 2007-2008 Alex King. All rights reserved.
//
// Released under the GPL license
// http://www.opensource.org/licenses/gpl-license.php
//
// This is an add-on for WordPress
// http://wordpress.org/
//
// Thanks to John Ford ( http://www.aldenta.com ) for his contributions.
// Thanks to Dougal Campbell ( http://dougal.gunters.org ) for his contributions.
// Thanks to Silas Sewell ( http://silas.sewell.ch ) for his contributions.
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
// **********************************************************************

load_plugin_textdomain('twitter-tools');

if (!function_exists('dbg')) {
	function dbg() {
		return;
	}
}

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

if (!function_exists('wp_prototype_before_jquery')) {
	function wp_prototype_before_jquery( $js_array ) {
		if ( false === $jquery = array_search( 'jquery', $js_array ) )
			return $js_array;
	
		if ( false === $prototype = array_search( 'prototype', $js_array ) )
			return $js_array;
	
		if ( $prototype < $jquery )
			return $js_array;
	
		unset($js_array[$prototype]);
	
		array_splice( $js_array, $jquery, 0, 'prototype' );
	
		return $js_array;
	}
	
	add_filter( 'print_scripts_array', 'wp_prototype_before_jquery' );
}

define('AKTT_API_POST_STATUS', 'http://twitter.com/statuses/update.json');
define('AKTT_API_USER_TIMELINE', 'http://twitter.com/statuses/user_timeline.json');
define('AKTT_API_STATUS_SHOW', 'http://twitter.com/statuses/show/###ID###.json');
define('AKTT_PROFILE_URL', 'http://twitter.com/###USERNAME###');
define('AKTT_STATUS_URL', 'http://twitter.com/###USERNAME###/statuses/###STATUS###');
define('AKTT_HASHTAG_URL', 'http://search.twitter.com/search?q=###HASHTAG###');

class twitter_tools {
	function twitter_tools() {
		$this->options = array(
			'twitter_username'
			, 'twitter_password'
			, 'create_blog_posts'
			, 'create_digest'
			, 'create_digest_weekly'
			, 'digest_daily_time'
			, 'digest_weekly_time'
			, 'digest_weekly_day'
			, 'digest_title'
			, 'digest_title_weekly'
			, 'blog_post_author'
			, 'blog_post_category'
			, 'blog_post_tags'
			, 'notify_twitter'
			, 'sidebar_tweet_count'
			, 'tweet_from_sidebar'
			, 'give_tt_credit'
			, 'exclude_reply_tweets'
			, 'last_tweet_download'
			, 'doing_tweet_download'
			, 'doing_digest_post'
			, 'install_date'
			, 'js_lib'
			, 'digest_tweet_order'
			, 'notify_twitter_default'
		);
		$this->twitter_username = '';
		$this->twitter_password = '';
		$this->create_blog_posts = '0';
		$this->create_digest = '0';
		$this->create_digest_weekly = '0';
		$this->digest_daily_time = null;
		$this->digest_weekly_time = null;
		$this->digest_weekly_day = null;
		$this->digest_title = __("Twitter Updates for %s", 'twitter-tools');
		$this->digest_title_weekly = __("Twitter Weekly Updates for %s", 'twitter-tools');
		$this->blog_post_author = '1';
		$this->blog_post_category = '1';
		$this->blog_post_tags = '';
		$this->notify_twitter = '0';
		$this->notify_twitter_default = '0';
		$this->sidebar_tweet_count = '3';
		$this->tweet_from_sidebar = '1';
		$this->give_tt_credit = '1';
		$this->exclude_reply_tweets = '0';
		$this->install_date = '';
		$this->js_lib = 'jquery';
		$this->digest_tweet_order = 'ASC';
		// not included in options
		$this->update_hash = '';
		$this->tweet_prefix = 'New blog post';
		$this->tweet_format = $this->tweet_prefix.': %s %s';
		$this->last_digest_post = '';
		$this->last_tweet_download = '';
		$this->doing_tweet_download = '0';
		$this->doing_digest_post = '0';
		$this->version = '1.2b1';
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
			`tw_reply_username` VARCHAR( 255 ) DEFAULT NULL ,
			`tw_reply_tweet` VARCHAR( 255 ) DEFAULT NULL ,
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
	
	function upgrade() {
		global $wpdb;
		$col_data = $wpdb->get_results("
			SHOW COLUMNS FROM $wpdb->aktt
		");
		$cols = array();
		foreach ($col_data as $col) {
			$cols[] = $col->Field;
		}
		// 1.2 schema upgrade
		if (!in_array('tw_reply_username', $cols)) {
			$wpdb->query("
				ALTER TABLE `$wpdb->aktt`
				ADD `tw_reply_username` VARCHAR( 255 ) DEFAULT NULL
				AFTER `tw_text`
			");
		}
		if (!in_array('tw_reply_tweet', $cols)) {
			$wpdb->query("
				ALTER TABLE `$wpdb->aktt`
				ADD `tw_reply_tweet` VARCHAR( 255 ) DEFAULT NULL
				AFTER `tw_reply_username`
			");
		}
	}

	function get_settings() {
		foreach ($this->options as $option) {
			$this->$option = get_option('aktt_'.$option);
		}
	}
	
	function settings() {
		$settings = array();
		foreach($this->options as $option) {
			$option_key = 'aktt_'.$option;
			$settings[$option_key] = get_option($option_key);
		}
		if ($settings['aktt_next_daily_digest']) {
			$settings['next_daily_digest'] = $this->date_info( $settings['aktt_next_daily_digest'] );
		}
		if ($settings['aktt_last_digest_post']) {
			$settings['last_digest_post'] = $this->date_info( $settings['aktt_last_digest_post'] );
		}
		if ($settings['aktt_next_weekly_digest']) {
			$settings['next_weekly_digest'] = $this->date_info( $settings['aktt_next_weekly_digest'] );
		}
		if ($settings['aktt_last_digest_post_weekly']) {
			$settings['last_digest_post_weekly'] = $this->date_info( $settings['aktt_last_digest_post_weekly'] );
		}
		return $settings;
	}
	
	function date_info($d) {
		return array(
			'raw' => $d,
			'gmt' => gmdate("Y-m-d H:i:s", $d),
			'local' => date("Y-m-d H:i:s", $d),
			'relative' => $d - time()
		);
	}
	
	// puts post fields into object propps
	function populate_settings() {
		foreach ($this->options as $option) {
			if (isset($_POST['aktt_'.$option])) {
				$this->$option = stripslashes($_POST['aktt_'.$option]);
			}
		}
	}
	
	// puts object props into wp option storage
	function update_settings() {
		if (current_user_can('manage_options')) {
			$this->sidebar_tweet_count = intval($this->sidebar_tweet_count);
			if ($this->sidebar_tweet_count == 0) {
				$this->sidebar_tweet_count = '3';
			}
			foreach ($this->options as $option) {
				update_option('aktt_'.$option, $this->$option);
			}
			if (empty($this->install_date)) {
				update_option('aktt_install_date', current_time('mysql'));
			}
			$this->initiate_digests();
			$this->upgrade();
		}
	}
	
	// figure out when the next weekly and daily digests will be
	function initiate_digests() {
		$next = ($this->create_digest) ? $this->calculate_next_daily_digest() : null;
		$this->next_daily_digest = $next;
		update_option('aktt_next_daily_digest', $next);
		
		$next = ($this->create_digest_weekly) ? $this->calculate_next_weekly_digest() : null;
		$this->next_weekly_digest = $next;
		update_option('aktt_next_weekly_digest', $next);
	}
	
	function calculate_next_daily_digest() {
		$optionDate = strtotime($this->digest_daily_time);
		$hour_offset = date("G", $optionDate);
		$minute_offset = date("i", $optionDate);
		$next = mktime($hour_offset, $minute_offset, 0);
		
		// may have to move to next day
		$now = time();
		while($next < $now) {
			$next += 60 * 60 * 24;
		}
		return $next;
	}
	
	function calculate_next_weekly_digest() {
		$optionDate = strtotime($this->digest_weekly_time);
		$hour_offset = date("G", $optionDate);
		$minute_offset = date("i", $optionDate);
		
		$current_day_of_month = date("j");
		$current_day_of_week = date("w");
		$current_month = date("n");
		
		// if this week's day is less than today, go for next week
		$nextDay = $current_day_of_month - $current_day_of_week + $this->digest_weekly_day;
	
		// create a time
		$now = time();

		$next = mktime($hour_offset, $minute_offset, 0, $current_month, $nextDay);
		while ($next < $now) {
			$next += 7 * 60 * 60 * 24;
		}
		return $next;
	}
	
	function ping_digests() {
		// still busy
		if (get_option('aktt_doing_digest_post') == '1') {
			return;
		}
		// check all the digest schedules
		if ($this->create_digest == 1) {
			$this->ping_digest('aktt_next_daily_digest', 'aktt_last_digest_post', $this->digest_title, 60 * 60 * 24 * 1);
		}
		if ($this->create_digest_weekly == 1) {
			$this->ping_digest('aktt_next_weekly_digest', 'aktt_last_digest_post_weekly', $this->digest_title_weekly, 60 * 60 * 24 * 7);
		}
		return;
	}
	
	function ping_digest($nextDateField, $lastDateField, $title, $defaultDuration) {

		$next = get_option($nextDateField);
		
		if ($next) {		
			$next = $this->validateDate($next);
			$rightNow = time();
			if ($rightNow >= $next) {
				$start = get_option($lastDateField);
				$start = $this->validateDate($start, $rightNow - $defaultDuration);
				if ($this->do_digest_post($start, $next, $title)) {
					update_option($lastDateField, $rightNow);
					update_option($nextDateField, $next + $defaultDuration);
				} else {
					update_option($lastDateField, null);
				}
			}
		}
	}
	
	function validateDate($in, $default = 0) {
		if (!is_numeric($in)) {
			// try to convert what they gave us into a date
			$out = strtotime($in);
			// if that doesn't work, return the default
			if (!is_numeric($out)) {
				return $default;
			}
			return $out;	
		}
		return $in;
	}

	function do_digest_post($start, $end, $title) {
		
		if (!$start || !$end) return false;

		// success indicator
		$success = false;
		
		// flag us as busy
		update_option('aktt_doing_digest_post', '1');
		remove_action('publish_post', 'aktt_notify_twitter');

//		try {
		
			// see if there's any tweets in the time range
			global $wpdb;
			
			$startGMT = gmdate("Y-m-d H:i:s", $start);
			$endGMT = gmdate("Y-m-d H:i:s", $end);
			
			// build sql
			$conditions = array();
			$conditions[] = "tw_created_at >= '{$startGMT}'";
			$conditions[] = "tw_created_at <= '{$endGMT}'";
			$conditions[] = "tw_text NOT LIKE '$this->tweet_prefix%'";
			if ($this->exclude_reply_tweets) {
				$conditions[] = "tw_text NOT LIKE '@%'";
			}
			$where = implode(' AND ', $conditions);
			
			$sql = "
				SELECT * FROM {$wpdb->aktt}
				WHERE {$where}
				GROUP BY tw_id
				ORDER BY tw_created_at {$this->digest_tweet_order}
			";

			$tweets = $wpdb->get_results($sql);

			if (count($tweets) > 0) {
			
				$tweets_to_post = array();
				foreach ($tweets as $data) {
					$tweet = new aktt_tweet;
					$tweet->tw_text = $data->tw_text;
					$tweet->tw_reply_tweet = $data->tw_reply_tweet;
					if (!$tweet->tweet_is_post_notification() || ($tweet->tweet_is_reply() && $this->exclude_reply_tweets)) {
						$tweets_to_post[] = $data;
					}
				}

				if (count($tweets_to_post) > 0) {
					$content = '<ul class="aktt_tweet_digest">'."\n";
					foreach ($tweets_to_post as $tweet) {
						$content .= '	<li>'.aktt_tweet_display($tweet, 'absolute').'</li>'."\n";
					}
					$content .= '</ul>'."\n";
					if ($this->give_tt_credit == '1') {
						$content .= '<p class="aktt_credit">Powered by <a href="http://alexking.org/projects/wordpress">Twitter Tools</a>.</p>';
					}

					$post_data = array(
						'post_content' => $wpdb->escape($content),
						'post_title' => $wpdb->escape(sprintf($title, date('Y-m-d'))),
						'post_date' => date('Y-m-d 23:59:59'),
						'post_category' => array($this->blog_post_category),
						'post_status' => 'publish',
						'post_author' => $wpdb->escape($this->blog_post_author)
					);

					$post_id = wp_insert_post($post_data);

					add_post_meta($post_id, 'aktt_tweeted', '1', true);
					wp_set_post_tags($post_id, $this->blog_post_tags);
				}

			}
			
			// indicate everything is swell
			$success = true;
			
//		} catch (Exception $e) {
			// dbg('oops', $e->getMessage());
//		}
		
		add_action('publish_post', 'aktt_notify_twitter');
		update_option('aktt_doing_digest_post', '0');
		return $success;
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
			AKTT_API_POST_STATUS
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
			|| get_post_meta($post_id, 'aktt_notify_twitter', true) == 'no'
		) {
			return;
		}
		$post = get_post($post_id);
		// check for an edited post before TT was installed
		if ($post->post_date <= $this->install_date) {
			return;
		}
		// check for private posts
		if ($post->post_status != 'publish') {
			return;
		}
		$tweet = new aktt_tweet;
		$tweet->tw_text = sprintf(__($this->tweet_format, 'twitter-tools'), $post->post_title, get_permalink($post_id));
		$this->do_tweet($tweet);
		add_post_meta($post_id, 'aktt_tweeted', '1', true);
	}
	
	function do_tweet_post($tweet) {
		global $wpdb;
		remove_action('publish_post', 'aktt_notify_twitter');
		$data = array(
			'post_content' => $wpdb->escape(aktt_make_clickable($tweet->tw_text))
			, 'post_title' => $wpdb->escape(trim_add_elipsis($tweet->tw_text, 30))
			, 'post_date' => get_date_from_gmt(date('Y-m-d H:i:s', $tweet->tw_created_at))
			, 'post_category' => array($this->blog_post_category)
			, 'post_status' => 'publish'
			, 'post_author' => $wpdb->escape($this->blog_post_author)
		);
		$post_id = wp_insert_post($data);
		add_post_meta($post_id, 'aktt_twitter_id', $tweet->tw_id, true);
		wp_set_post_tags($post_id, $this->blog_post_tags);
		add_action('publish_post', 'aktt_notify_twitter');
	}
}

class aktt_tweet {
	function aktt_tweet(
		$tw_id = ''
		, $tw_text = ''
		, $tw_created_at = ''
		, $tw_reply_username = null
		, $tw_reply_tweet = null
	) {
		$this->id = '';
		$this->modified = '';
		$this->tw_created_at = $tw_created_at;
		$this->tw_text = $tw_text;
		$this->tw_reply_username = $tw_reply_username;
		$this->tw_reply_tweet = $tw_reply_tweet;
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
	
	function tweet_is_reply() {
		return !empty($this->tw_reply_tweet);
	}
	
	function add() {
		global $wpdb, $aktt;
		$wpdb->query("
			INSERT
			INTO $wpdb->aktt
			( tw_id
			, tw_text
			, tw_reply_username
			, tw_reply_tweet
			, tw_created_at
			, modified
			)
			VALUES
			( '".$wpdb->escape($this->tw_id)."'
			, '".$wpdb->escape($this->tw_text)."'
			, '".$wpdb->escape($this->tw_reply_username)."'
			, '".$wpdb->escape($this->tw_reply_tweet)."'
			, '".date('Y-m-d H:i:s', $this->tw_created_at)."'
			, NOW()
			)
		");
		do_action('aktt_add_tweet', $this);
		if ($aktt->create_blog_posts == '1' && !$this->tweet_post_exists() && !$this->tweet_is_post_notification() && (!$aktt->exclude_reply_tweets || !$this->tweet_is_reply())) {
			$aktt->do_tweet_post($this);
		}
	}
}

function aktt_api_status_show_url($id) {
	return str_replace('###ID###', $id, AKTT_API_STATUS_SHOW);
}

function aktt_profile_url($username) {
	return str_replace('###USERNAME###', $username, AKTT_PROFILE_URL);
}

function aktt_profile_link($username, $prefix = '', $suffix = '') {
	return $prefix.'<a href="'.aktt_profile_url($username).'">'.$username.'</a>'.$suffix;
}

function aktt_hashtag_url($hashtag) {
	$hashtag = urlencode('#'.$hashtag);
	return str_replace('###HASHTAG###', $hashtag, AKTT_HASHTAG_URL);
}

function aktt_hashtag_link($hashtag, $prefix = '', $suffix = '') {
	return $prefix.'<a href="'.aktt_hashtag_url($hashtag).'">'.htmlspecialchars($hashtag).'</a>'.$suffix;
}

function aktt_status_url($username, $status) {
	return str_replace(
		array(
			'###USERNAME###'
			, '###STATUS###'
		)
		, array(
			$username
			, $status
		)
		, AKTT_STATUS_URL
	);
}

function aktt_login_test($username, $password) {
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	$snoop = new Snoopy;
	$snoop->agent = 'Twitter Tools http://alexking.org/projects/wordpress';
	$snoop->user = $username;
	$snoop->pass = $password;
	$snoop->fetch(AKTT_API_USER_TIMELINE);
	if (strpos($snoop->response_code, '200')) {
		return __("Login succeeded, you're good to go.", 'twitter-tools');
	} else {
		$json = new Services_JSON();
		$results = $json->decode($snoop->results);
		return print_r($results);
	}
}


function aktt_ping_digests() {
	global $aktt;
	$aktt->ping_digests();
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
	$snoop->fetch(AKTT_API_USER_TIMELINE);

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
				if (!empty($tw_data->in_reply_to_status_id)) {
					$tweet->tw_reply_tweet = $tw_data->in_reply_to_status_id;
					$url = aktt_api_status_show_url($tw_data->in_reply_to_status_id);
					$snoop->fetch($url);
					if (strpos($snoop->response_code, '200') !== false) {
						$data = $snoop->results;
						$status = $json->decode($data);
						$tweet->tw_reply_username = $status->user->screen_name;
					}
				}
				// make sure we haven't downloaded someone else's tweets - happens sometimes due to Twitter hiccups
				if ($tw_data->user->screen_name == $aktt->twitter_username) {
					$new_tweets[] = $tweet;
				}
			}
		}
		foreach ($new_tweets as $tweet) {
			$tweet->add();
		}
	}
	aktt_reset_tweet_checking($hash, time());
}


function aktt_reset_tweet_checking($hash = '', $time = 0) {
	update_option('aktt_update_hash', $hash);
	update_option('aktt_last_tweet_download', $time);
	update_option('aktt_doing_tweet_download', '0');
}

function aktt_notify_twitter($post_id) {
	global $aktt;
	$aktt->do_blog_post_tweet($post_id);
}
add_action('publish_post', 'aktt_notify_twitter', 99);

function aktt_sidebar_tweets() {
	global $wpdb, $aktt;
	if ($aktt->exclude_reply_tweets) {
		$where = "AND tw_text NOT LIKE '@%' ";
	}
	else {
		$where = '';
	}
	$tweets = $wpdb->get_results("
		SELECT *
		FROM $wpdb->aktt
		WHERE tw_text NOT LIKE '$aktt->tweet_prefix%'
		$where
		GROUP BY tw_id
		ORDER BY tw_created_at DESC
		LIMIT $aktt->sidebar_tweet_count
	");
	$output = '<div class="aktt_tweets">'."\n"
		.'	<ul>'."\n";
	if (count($tweets) > 0) {
		foreach ($tweets as $tweet) {
			$output .= '		<li>'.aktt_tweet_display($tweet).'</li>'."\n";
		}
	}
	else {
		$output .= '		<li>'.__('No tweets available at the moment.', 'twitter-tools').'</li>'."\n";
	}
	if (!empty($aktt->twitter_username)) {
  		$output .= '		<li class="aktt_more_updates"><a href="'.aktt_profile_url($aktt->twitter_username).'">More updates...</a></li>'."\n";
	}
	$output .= '</ul>';
	if ($aktt->tweet_from_sidebar == '1' && !empty($aktt->twitter_username) && !empty($aktt->twitter_password)) {
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
		WHERE tw_text NOT LIKE '$aktt->tweet_prefix%'
		GROUP BY tw_id
		ORDER BY tw_created_at DESC
		LIMIT 1
	");
	if (count($tweets) == 1) {
		foreach ($tweets as $tweet) {
			$output = aktt_tweet_display($tweet);
		}
	}
	else {
		$output = __('No tweets available at the moment.', 'twitter-tools');
	}
	print($output);
}

function aktt_tweet_display($tweet, $time = 'relative') {
	global $aktt;
	$output = aktt_make_clickable(wp_specialchars($tweet->tw_text));
	if (!empty($tweet->tw_reply_username)) {
		$output .= 	' <a href="'.aktt_status_url($tweet->tw_reply_username, $tweet->tw_reply_tweet).'">'.sprintf(__('in reply to %s', 'twitter-tools'), $tweet->tw_reply_username).'</a>';
	}
	switch ($time) {
		case 'relative':
			$time_display = aktt_relativeTime($tweet->tw_created_at, 3);
			break;
		case 'absolute':
			$time_display = '#';
			break;
	}
	$output .= ' <a href="'.aktt_status_url($aktt->twitter_username, $tweet->tw_id).'">'.$time_display.'</a>';
	return $output;
}

function aktt_make_clickable($tweet) {
//	$tweet = preg_replace('/\@([a-zA-Z0-9_]{1,15}) /','@<a href="http://twitter.com/\\1">\\1</a> ', $tweet);
	$tweet .= ' ';
	$tweet = preg_replace_callback(
		'/\@([a-zA-Z0-9_]{1,15}) /'
		, create_function(
			'$matches'
			, 'return aktt_profile_link($matches[1], \'@\', \' \');'
		)
		, $tweet
	);
	$tweet = preg_replace_callback(
		'/\#([a-zA-Z0-9_]{1,15}) /'
		, create_function(
			'$matches'
			, 'return aktt_hashtag_link($matches[1], \'#\', \' \');'
		)
		, $tweet
	);
	
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

function aktt_settings() {
	global $aktt;
	dbg('settings', $aktt->settings());
}

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
		add_action('shutdown', 'aktt_ping_digests');
	}
	if (is_admin() || $aktt->tweet_from_sidebar) {
		switch ($aktt->js_lib) {
			case 'jquery':
				wp_enqueue_script('jquery');
				break;
			case 'prototype':
				wp_enqueue_script('prototype');
				break;
		}
	}
	global $wp_version;
	if (isset($wp_version) && version_compare($wp_version, '2.5', '>=') && empty ($aktt->install_date)) {
		add_action('admin_notices', create_function( '', "echo '<div class=\"error\">Please update your <a href=\"".get_bloginfo('wpurl')."/wp-admin/options-general.php?page=twitter-tools.php\">Twitter Tools settings</a>.</div>';" ) );
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
				wp_redirect(get_bloginfo('wpurl').'/wp-admin/options-general.php?page=twitter-tools.php&tweets-updated=true');
				die();
				break;
			case 'aktt_reset_tweet_checking':
				aktt_reset_tweet_checking();
				wp_redirect(get_bloginfo('wpurl').'/wp-admin/options-general.php?page=twitter-tools.php&tweet-checking-reset=true');
				die();
				break;
			case 'aktt_js':
				header("Content-type: text/javascript");
				switch ($aktt->js_lib) {
					case 'jquery':
?>
function akttPostTweet() {
	var tweet_field = jQuery('#aktt_tweet_text');
	var tweet_text = tweet_field.val();
	if (tweet_text == '') {
		return;
	}
	var tweet_msg = jQuery("#aktt_tweet_posted_msg");
	jQuery.post(
		"<?php bloginfo('wpurl'); ?>/index.php"
		, {
			ak_action: "aktt_post_tweet_sidebar"
			, aktt_tweet_text: tweet_text
		}
		, function(data) {
			tweet_msg.html(data);
			akttSetReset();
		}
	);
	tweet_field.val('').focus();
	jQuery('#aktt_char_count').html('');
	jQuery("#aktt_tweet_posted_msg").show();
}
function akttSetReset() {
	setTimeout('akttReset();', 2000);
}
function akttReset() {
	jQuery('#aktt_tweet_posted_msg').hide();
}
<?php
						break;
					case 'prototype':
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
						break;
				}
				die();
				break;
			case 'aktt_css':
				header("Content-Type: text/css");
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
				header("Content-Type: text/javascript");
				switch ($aktt->js_lib) {
					case 'jquery':
?>
function akttTestLogin() {
	var result = jQuery('#aktt_login_test_result');
	result.show().addClass('aktt_login_result_wait').html('<?php _e('Testing...', 'twitter-tools'); ?>');
	jQuery.post(
		"<?php bloginfo('wpurl'); ?>/index.php"
		, {
			ak_action: "aktt_login_test"
			, aktt_twitter_username: encodeURIComponent(jQuery('#aktt_twitter_username').val())
			, aktt_twitter_password: encodeURIComponent(jQuery('#aktt_twitter_password').val())
		}
		, function(data) {
			result.html(data).removeClass('aktt_login_result_wait');
			setTimeout('akttTestLoginResult();', 2000);
		}
	);
};

function akttTestLoginResult() {
	jQuery('#aktt_login_test_result').fadeOut('slow');
};

(function($){

	jQuery.fn.timepicker = function(){
	
		var hrs = new Array();
		for(var h = 1; h <= 12; hrs.push(h++));

		var mins = new Array();
		for(var m = 0; m < 60; mins.push(m++));

		var ap = new Array('am', 'pm');

		function pad(n) {
			n = n.toString();
			return n.length == 1 ? '0' + n : n;
		}
	
		this.each(function() {

			var v = $(this).val();
			if (!v) v = new Date();

			var d = new Date(v);
			var h = d.getHours();
			var m = d.getMinutes();
			var p = (h >= 12) ? "pm" : "am";
			h = (h > 12) ? h - 12 : h;

			var output = '';

			output += '<select id="h_' + this.id + '" class="timepicker">';				
			for (var hr in hrs){
				output += '<option value="' + pad(hrs[hr]) + '"';
				if(parseInt(hrs[hr]) == h) output += ' selected';
				output += '>' + pad(hrs[hr]) + '</option>';
			}
			output += '</select>';
	
			output += '<select id="m_' + this.id + '" class="timepicker">';				
			for (var mn in mins){
				output += '<option value="' + pad(mins[mn]) + '"';
				if(parseInt(mins[mn]) == m) output += ' selected';
				output += '>' + pad(mins[mn]) + '</option>';
			}
			output += '</select>';				
	
			output += '<select id="p_' + this.id + '" class="timepicker">';				
			for(var pp in ap){
				output += '<option value="' + ap[pp] + '"';
				if(ap[pp] == p) output += ' selected';
				output += '>' + ap[pp] + '</option>';
			}
			output += '</select>';
			
			$(this).after(output);
			
			var field = this;
			$(this).siblings('select.timepicker').change(function() {
				var h = parseInt($('#h_' + field.id).val());
				var m = parseInt($('#m_' + field.id).val());
				var p = $('#p_' + field.id).val();
	
				if (p == "am") {
					if (h == 12) {
						h = 0;
					}
				} else if (p == "pm") {
					if (h < 12) {
						h += 12;
					}
				}
				
				var d = new Date();
				d.setHours(h);
				d.setMinutes(m);
				
				$(field).val(d.toUTCString());
			}).change();

		});

		return this;
	};
	
	jQuery.fn.daypicker = function() {
		
		var days = new Array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
		
		this.each(function() {
			
			var v = $(this).val();
			if (!v) v = 0;
			v = parseInt(v);
			
			var output = "";
			output += '<select id="d_' + this.id + '" class="daypicker">';				
			for (var i = 0; i < days.length; i++) {
				output += '<option value="' + i + '"';
				if (v == i) output += ' selected';
				output += '>' + days[i] + '</option>';
			}
			output += '</select>';
			
			$(this).after(output);
			
			var field = this;
			$(this).siblings('select.daypicker').change(function() {
				$(field).val( $(this).val() );
			}).change();
		
		});
		
	};
	
	jQuery.fn.forceToggleClass = function(classNames, bOn) {
		return this.each(function() {
			jQuery(this)[ bOn ? "addClass" : "removeClass" ](classNames);
		});
	};
	
})(jQuery);

jQuery(function() {

	// add in the time and day selects
	jQuery('input.time').timepicker();
	jQuery('input.day').daypicker();
	
	// togglers
	jQuery('.time_toggle .toggler').change(function() {
		var theSelect = jQuery(this);
		theSelect.parent('.time_toggle').forceToggleClass('active', theSelect.val() === "1");
	}).change();
	
});
<?php
						break;
					case 'prototype':
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
						break;
				}
				die();
				break;
			case 'aktt_css_admin':
				header("Content-Type: text/css");
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
#ak_readme {
	height: 300px;
	width: 95%;
}
.wrap h2 {
	margin-top: 32px;
}
#ak_twittertools .options {
	overflow: hidden;
	border: none;
}
#ak_twittertools .option {
	overflow: hidden;
	border-bottom: dashed 1px #ccc;
	padding-bottom: 9px;
	padding-top: 9px;
}
#ak_twittertools .option label {
	display: block;
	float: left;
	width: 200px;
	margin-right: 24px;
	text-align: right;
}
#ak_twittertools .option span {
	display: block;
	float: left;
	margin-left: 230px;
	margin-top: 6px;
	clear: left;
}
#ak_twittertools select,
#ak_twittertools input {
	float: left;
	display: block;
	margin-right: 6px;
}
#ak_twittertools p.submit {
	overflow: hidden;
}
#ak_twittertools .option span {
	color: #666;
	display: block;
}
#ak_twittertools #aktt_login_test_result {
	display: inline;
	padding: 3px;
}
#ak_twittertools fieldset.options .option span.aktt_login_result_wait {
	background: #ffc;
}
#ak_twittertools fieldset.options .option span.aktt_login_result {
	background: #CFEBF7;
	color: #000;
}
#ak_twittertools .timepicker,
#ak_twittertools .daypicker {
	display: none;
}
#ak_twittertools .active .timepicker,
#ak_twittertools .active .daypicker {
	display: block
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
				wp_redirect(get_bloginfo('wpurl').'/wp-admin/options-general.php?page=twitter-tools.php&updated=true');
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
						wp_redirect(get_bloginfo('wpurl').'/wp-admin/post-new.php?page=twitter-tools.php&tweet-posted=true');
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
				die(__($test, 'twitter-tools'));
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
	print('
		<div class="wrap">
	');
	if (empty($aktt->twitter_username) || empty($aktt->twitter_password)) {
		print('
<p>Please enter your <a href="http://twitter.com">Twitter</a> account information in your <a href="options-general.php?page=twitter-tools.php">Twitter Tools Options</a>.</p>		
		');
	}
	else {
		print('
			<h2>'.__('Write Tweet', 'twitter-tools').'</h2>
			<p>This will create a new \'tweet\' in <a href="http://twitter.com">Twitter</a> using the account information in your <a href="options-general.php?page=twitter-tools.php">Twitter Tools Options</a>.</p>
			'.aktt_tweet_form('textarea').'
		');
	}
	print('
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
	
	$js_libs = array(
		'jquery' => 'jQuery'
		, 'prototype' => 'Prototype'
	);
	$js_lib_options = '';
	foreach ($js_libs as $js_lib => $js_lib_display) {
		if ($js_lib == $aktt->js_lib) {
			$selected = 'selected="selected"';
		}
		else {
			$selected = '';
		}
		$js_lib_options .= "\n\t<option value='$js_lib' $selected>$js_lib_display</option>";
	}
	$digest_tweet_orders = array(
		'ASC' => 'Oldest first (Chronological order)'
		, 'DESC' => 'Newest first (Reverse-chronological order)'
	);
	$digest_tweet_order_options = '';
	foreach ($digest_tweet_orders as $digest_tweet_order => $digest_tweet_order_display) {
		if ($digest_tweet_order == $aktt->digest_tweet_order) {
			$selected = 'selected="selected"';
		}
		else {
			$selected = '';
		}
		$digest_tweet_order_options .= "\n\t<option value='$digest_tweet_order' $selected>$digest_tweet_order_display</option>";
	}	
	$yes_no = array(
		'create_blog_posts'
		, 'create_digest'
		, 'create_digest_weekly'
		, 'notify_twitter'
		, 'notify_twitter_default'
		, 'tweet_from_sidebar'
		, 'give_tt_credit'
		, 'exclude_reply_tweets'
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
	if ( $_GET['tweet-checking-reset'] ) {
		print('
			<div id="message" class="updated fade">
				<p>'.__('Tweet checking has been reset.', 'twitter-tools').'</p>
			</div>
		');
	}
	print('
			<div class="wrap">
				<h2>'.__('Twitter Tools Options', 'twitter-tools').'</h2>
				<form id="ak_twittertools" name="ak_twittertools" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php" method="post">
					<input type="hidden" name="ak_action" value="aktt_update_settings" />
					<fieldset class="options">
						<div class="option">
							<label for="aktt_twitter_username">'.__('Twitter Username', 'twitter-tools').'/'.__('Password', 'twitter-tools').'</label>
							<input type="text" size="25" name="aktt_twitter_username" id="aktt_twitter_username" value="'.$aktt->twitter_username.'" />
							<input type="password" size="25" name="aktt_twitter_password" id="aktt_twitter_password" value="'.$aktt->twitter_password.'" />
							<input type="button" name="aktt_login_test" id="aktt_login_test" value="'.__('Test Login Info', 'twitter-tools').'" onclick="akttTestLogin(); return false;" />
							<span id="aktt_login_test_result"></span>
						</div>
						<div class="option">
							<label for="aktt_notify_twitter">'.__('Enable option to create a tweet when you post in your blog?', 'twitter-tools').'</label>
							<select name="aktt_notify_twitter" id="aktt_notify_twitter">'.$notify_twitter_options.'</select>
						</div>
						<div class="option">
							<label for="aktt_notify_twitter_default">'.__('Set this on by default?', 'twitter-tools').'</label>
							<select name="aktt_notify_twitter_default" id="aktt_notify_twitter_default">'.$notify_twitter_default_options.'</select>
						</div>
						<div class="option">
							<label for="aktt_create_blog_posts">'.__('Create a blog post from each of your tweets?', 'twitter-tools').'</label>
							<select name="aktt_create_blog_posts" id="aktt_create_blog_posts">'.$create_blog_posts_options.'</select>
						</div>
						<div class="option time_toggle">
							<label>'.__('Create a daily digest blog post from your tweets?', 'twitter-tools').'</label>
							<select name="aktt_create_digest" class="toggler">'.$create_digest_options.'</select>
							<input type="hidden" class="time" id="aktt_digest_daily_time" name="aktt_digest_daily_time" value="'.$aktt->digest_daily_time.'" />
						</div>
						<div class="option">
							<label for="aktt_digest_title">'.__('Title for daily digest posts:', 'twitter-tools').'</label>
							<input type="text" size="30" name="aktt_digest_title" id="aktt_digest_title" value="'.$aktt->digest_title.'" />
							<span>'.__('Include %s where you want the date. Example: Tweets on %s', 'twitter-tools').'</span>
						</div>
						<div class="option time_toggle">
							<label>'.__('Create a weekly digest blog post from your tweets?', 'twitter-tools').'</label>
							<select name="aktt_create_digest_weekly" class="toggler">'.$create_digest_weekly_options.'</select>
							<input type="hidden" class="time" name="aktt_digest_weekly_time" id="aktt_digest_weekly_time" value="'.$aktt->digest_weekly_time.'" />
							<input type="hidden" class="day" name="aktt_digest_weekly_day" value="'.$aktt->digest_weekly_day.'" />
						</div>
						<div class="option">
							<label for="aktt_digest_title_weekly">'.__('Title for weekly digest posts:', 'twitter-tools').'</label>
							<input type="text" size="30" name="aktt_digest_title_weekly" id="aktt_digest_title_weekly" value="'.$aktt->digest_title_weekly.'" />
							<span>'.__('Include %s where you want the date. Example: Tweets on %s', 'twitter-tools').'</span>
						</div>
						
						<div class="option">
							<label for="aktt_digest_tweet_order">'.__('Order of tweets in digest?', 'twitter-tools').'</label>
							<select name="aktt_digest_tweet_order" id="aktt_digest_tweet_order">'.$digest_tweet_order_options.'</select>
						</div>
						<div class="option">
							<label for="aktt_blog_post_category">'.__('Category for tweet posts:', 'twitter-tools').'</label>
							<select name="aktt_blog_post_category" id="aktt_blog_post_category">'.$cat_options.'</select>
						</div>
						<div class="option">
							<label for="aktt_blog_post_tags">'.__('Tag(s) for your tweet posts:', 'twitter-tools').'</label>
							<input name="aktt_blog_post_tags" id="aktt_blog_post_tags" value="'.$aktt->blog_post_tags.'">
							<span>'._('Separate multiple tags with commas. Example: tweets, twitter').'</span>
						</div>
						<div class="option">
							<label for="aktt_blog_post_author">'.__('Author for tweet posts:', 'twitter-tools').'</label>
							<select name="aktt_blog_post_author" id="aktt_blog_post_author">'.$author_options.'</select>
						</div>
						<div class="option">
							<label for="aktt_exclude_reply_tweets">'.__('Exclude @reply tweets in your sidebar, digests and created blog posts?', 'twitter-tools').'</label>
							<select name="aktt_exclude_reply_tweets" id="aktt_exclude_reply_tweets">'.$exclude_reply_tweets_options.'</select>
						</div>
						<div class="option">
							<label for="aktt_sidebar_tweet_count">'.__('Tweets to show in sidebar:', 'twitter-tools').'</label>
							<input type="text" size="3" name="aktt_sidebar_tweet_count" id="aktt_sidebar_tweet_count" value="'.$aktt->sidebar_tweet_count.'" />
							<span>'.__('Numbers only please.', 'twitter-tools').'</span>
						</div>
						<div class="option">
							<label for="aktt_tweet_from_sidebar">'.__('Create tweets from your sidebar?', 'twitter-tools').'</label>
							<select name="aktt_tweet_from_sidebar" id="aktt_tweet_from_sidebar">'.$tweet_from_sidebar_options.'</select>
						</div>
						<div class="option">
							<label for="aktt_js_lib">'.__('JS Library to use?', 'twitter-tools').'</label>
							<select name="aktt_js_lib" id="aktt_js_lib">'.$js_lib_options.'</select>
						</div>
						<div class="option">
							<label for="aktt_give_tt_credit">'.__('Give Twitter Tools credit?', 'twitter-tools').'</label>
							<select name="aktt_give_tt_credit" id="aktt_give_tt_credit">'.$give_tt_credit_options.'</select>
						</div>
					</fieldset>
					<p class="submit">
						<input type="submit" name="submit" value="'.__('Update Twitter Tools Options', 'twitter-tools').'" />
					</p>
				</form>
				<h2>'.__('Update Tweets', 'twitter-tools').'</h2>
				<form name="ak_twittertools_updatetweets" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php" method="get">
					<p>'.__('Use this button to manually update your tweets.', 'twitter-tools').'</p>
					<p class="submit">
						<input type="submit" name="submit-button" value="'.__('Update Tweets', 'twitter-tools').'" />
						<input type="submit" name="reset-button" value="'.__('Reset Tweet Checking', 'twitter-tools').'" onclick="document.getElementById(\'ak_action_2\').value = \'aktt_reset_tweet_checking\';" />
						<input type="hidden" name="ak_action" id="ak_action_2" value="aktt_update_tweets" />
					</p>
				</form>
				<h2>'.__('README', 'twitter-tools').'</h2>
				<p>'.__('Find answers to common questions here.', 'twitter-tools').'</p>
				<iframe id="ak_readme" src="http://alexking.org/projects/wordpress/readme?project=twitter-tools"></iframe>
			</div>
	');
}

function aktt_post_options() {
	global $aktt, $post;
	if ($aktt->notify_twitter) {
		if (get_post_meta($post->ID, 'aktt_notify_twitter', true) == 'no' || !$aktt->notify_twitter_default) {
			$checked = '';
		}
		else {
			$checked = 'checked="checked"';
		}
		print('
<p>
	<input type="checkbox" name="aktt_notify_twitter" id="aktt_notify_twitter" value="yes" '.$checked.' />
	<label for="aktt_notify_twitter">Notify Twitter about this post?</label>
</p>
		');
	}
}
add_action('edit_form_advanced', 'aktt_post_options');

function aktt_store_post_options($post_id, $post) {
	if ($post->post_type == 'revision') {
		return;
	}
	if (!empty($_POST['aktt_notify_twitter'])) {
		$notify = 'yes';
	}
	else {
		$notify = 'no';
	}
	if (!update_post_meta($post_id, 'aktt_notify_twitter', $notify)) {
		add_post_meta($post_id, 'aktt_notify_twitter', $notify);
	}
}
add_action('draft_post', 'aktt_store_post_options', 1, 2);
add_action('publish_post', 'aktt_store_post_options', 1, 2);
add_action('save_post', 'aktt_store_post_options', 1, 2);

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
			, __('New Tweet', 'twitter-tools')
			, __('Tweet', 'twitter-tools')
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