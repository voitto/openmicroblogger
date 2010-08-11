<?php
class PuSHPress {
	var $http_timeout;
	var $http_user_agent;

	function PuSHPress( ) { }

	function init( ) {
		// Let other plugins modify various options
		$this->http_timeout = apply_filters( 'pushpress_http_timeout', 5 );
		$this->http_user_agent = apply_filters( 'pushpress_http_timeout', 'WordPress/PuSHPress ' . PUSHPRESS_VERSION );

		// Make sure the hubs get listed in the RSS2 and Atom feeds
		add_action( 'rss2_head', array( &$this, 'hub_link_rss2' ) );
		add_action( 'atom_head', array( &$this, 'hub_link_atom' ) );

		// Make the built in hub URL work
		add_action( 'parse_request', array( &$this, 'parse_wp_request' ) );

		// Send out fat pings when a new post is published
		add_action( 'publish_post', array( &$this, 'publish_post' ) );
	}




	function add_callback( $feed_url, $callback, $secret ) {
		$subs = get_option( 'pushpress_subscribers' );
		$subs[$feed_url][$callback] = array(
			'is_active'		=> TRUE,
			'secret'		=> $secret,
			'start_date'	=> gmdate( 'Y-m-d H:i:s' ),
			'unsubscribe'	=> FALSE,
		);
		update_option( 'pushpress_subscribers', $subs );
	}

	function check_required_fields( ) {
		// required fields must have values
		if ( empty( $_POST['hub_callback'] ) )
			$this->return_error( 'hub.callback is empty' );

		if ( empty( $_POST['hub_mode'] ) ) 
			$this->return_error( 'hub.mode is empty' );

		if ( empty( $_POST['hub_topic'] ) )
			$this->return_error( 'hub.topic is empty' );

		if ( empty( $_POST['hub_verify'] ) )
			$this->return_error( 'hub.verify is empty' );

		// mode has a small set of valid values
		$_POST['hub_mode'] = strtolower( $_POST['hub_mode'] );
		if ( $_POST['hub_mode'] != 'subscribe' ) {
			if ( $_POST['hub_mode'] != 'unsubscribe' ) {
				$this->return_error( 'hub.mode is invalid' );
			}
		}
	}

	function check_topic( ) {
		$allowed = FALSE;

		if ( $_POST['hub_topic'] == get_bloginfo( 'rss2_url' ) )
			$allowed = TRUE;

		if ( $_POST['hub_topic'] == get_bloginfo( 'atom_url' ) )
			$allowed = TRUE;

		// If there is no trailing slash on the requested feed URL
		// see if there is a match when a trailing slash is added
		if ( substr( $_POST['hub_topic'], -1, 1 ) != '/' ) {
			$topic_with_slash = $_POST['hub_topic'] . '/';

			if ( $topic_with_slash == get_bloginfo( 'rss2_url' ) ) {
				$_POST['hub_topic'] = $topic_with_slash;
				$allowed = TRUE;
			} elseif ( $topic_with_slash == get_bloginfo( 'atom_url' ) ) {
				$_POST['hub_topic'] = $topic_with_slash;
				$allowed = TRUE;
			}
		}
    $allowed = TRUE;
		if ( $allowed === FALSE ) {
			do_action( 'pushpress_topic_failure' );

			$msg = 'hub_topic - ' . $_POST['hub_topic'];
			$msg .= ' - is value is not allowed.  ';
			$msg .= 'You may only subscribe to ' . get_bloginfo( 'rss2_url' );
			$msg .= ' or ' . get_bloginfo( 'atom_url' );

			$this->return_error( $msg );
		}
	}

	function get_subscribers( $feed_url ) {
		/*
			array(
				_rss_url_ => array(
					_callback_url_ => array(
						'is_active'		=> true/false,
						'secret'		=> _string_,
						'start_date'	=> _date_gmt_,
						'unsubscribe'	=> true/false
					)
				),
				_atom_url_ => array(
					_callback_url_ => array(
						'is_active'		=> true/false,
						'secret'		=> _string_,
						'start_date'	=> _date_gmt_,
						'unsubscribe'	=> true/false
					)
				)
			)
		 */

		$subs = get_option( 'pushpress_subscribers' );

		if ( isset( $subs[$feed_url] ) ) {
			return $subs[$feed_url];
		} else {
			return FALSE;
		}
	}

	function hub_link_atom( ) {
		$hubs = apply_filters( 'pushpress_hubs', array(
			get_bloginfo( 'url' ) . '?pushpress'
		) );

		foreach ( (array) $hubs as $hub ) {
			echo "\t<link rel='hub' href='{$hub}' />\n";
		}
	}

	function hub_link_rss2( ) {
		$hubs = apply_filters( 'pushpress_hubs', array(
			get_bloginfo( 'url' ) . '?pushpress'
		) );

		foreach ( (array) $hubs as $hub ) {
			echo "\t<atom:link rel='hub' href='{$hub}'/>\n";
		}
	}

	function hub_request( ) {
		$this->check_required_fields( );
		$this->check_topic( );

		if ( $_POST['hub_mode'] == 'unsubscribe' ) {
			do_action( 'pushpress_unsubscribe_request' );
			$this->verify_request( );
			$this->unsubscribe_callback( $_POST['hub_topic'], $_POST['hub_callback'] );
			$this->return_ok( );
		}

		if ( $_POST['hub_mode'] == 'subscribe' ) {
			do_action( 'pushpress_subscribe_request' );
			$this->verify_request( );
		}

		$subs = $this->get_subscribers( $_POST['hub_topic'] );

		$secret = '';
		if ( !empty( $_POST['hub_secret'] ) )
			$secret = $_POST['hub_secret'];

		$this->add_callback( $_POST['hub_topic'], $_POST['hub_callback'], $secret );
		$this->return_ok( );
	}

	function parse_wp_request( $wp ) {
		if ( !empty( $_GET['pushpress'] ) && $_GET['pushpress'] == 'hub' ) {
			$this->hub_request( );
			exit;
		}
	}

	function publish_post( $post_id ) {
		$subs = $this->get_subscribers( get_bloginfo( 'rss2_url' ) );
		foreach ( (array) $subs as $callback => $data ) {
			if ( $data['is_active'] == FALSE )
				continue;

			$this->schedule_ping( $callback, $post_id, 'rss2', $data['secret'] );
		}

		$subs = $this->get_subscribers( get_bloginfo( 'atom_url' ) );
		foreach ( (array) $subs as $callback => $data ) {
			if ( $data['is_active'] == FALSE )
				continue;

			$this->schedule_ping( $callback, $post_id, 'atom', $data['secret'] );
		}
	}

	function return_ok( ) {
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'HTTP/1.0 204 No Content' );
		exit;
	}

	function return_error( $msg ) {
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'HTTP/1.0 400 Bad Request' );
		echo $msg;
		exit;
	}

	function schedule_ping( $callback, $post_id, $feed_type, $secret ) {
		wp_schedule_single_event(
			time( ) - 1,
			'pushpress_scheduled_ping',
			array( $callback, $post_id, $feed_type, $secret )
		);
	}

	function unsubscribe_callback( $feed_url, $callback ) {
		$subs = get_option( 'pushpress_subscribers' );
		$subs[$feed_url][$callback]['is_active'] = FALSE;
		$subs[$feed_url][$callback]['unsubscribe'] = TRUE;
		update_option( 'pushpress_subscribers', $subs );
	}

	function verify_request( ) {
		$challenge = uniqid( mt_rand( ), TRUE );
		$challenge .= uniqid( mt_rand( ), TRUE );

		$hub_vars = 'hub.lease_seconds=315360000'; // 10 years
		$hub_vars .= '&hub.mode=' . urlencode( $_POST['hub_mode'] );
		$hub_vars .= '&hub.topic=' . urlencode( $_POST['hub_topic'] );
		$hub_vars .= '&hub.challenge=' . urlencode( $challenge );

		if ( !empty( $_POST['hub_verify_token'] ) ) {
			do_action( 'pushpress_include_verify_token' );
			$hub_vars .= '&hub.verify_token=';
			$hub_vars .= urlencode( $_POST['hub_verify_token'] );
		}

		$callback = parse_url( $_POST['hub_callback'] );

		$url = $callback['scheme'] . '://';

		if ( !empty( $callback['user'] ) ) {
			$url .= $callback['user'];

			if ( !empty( $callback['pass'] ) )
				$url .= ':' . $callback['pass'];

			$url .= '@';
		}

		$url .= $callback['host'];

		$port = 80;
		if ( !empty( $callback['port'] ) )
			$port = (int) $callback['port'];

		if ( $callback['scheme'] == 'https' )
			$port = 443;

		$url .= ':' . $port;

		$path = '/';
		if ( !empty( $callback['path'] ) ) {
			$path = str_replace( '@', '', $callback['path'] );
			if ( $path{0} != '/' ) {
				$path = '/' . $path;
			}

			$url .= $path;
		}

		if ( !empty( $callback['query'] ) ) {
			$url .= '?' . $callback['query'];
			$url .= '&' . $hub_vars;
		} else {
			$url .= '?' . $hub_vars;
		}

		$response = wp_remote_get( $url, array(
			'sslverify'		=> FALSE,
			'timeout'		=> $this->http_timeout,
			'user-agent'	=> $this->http_user_agent,
		) );

		// look for failure indicators
		if ( isset( $response->errors['http_request_failed'][0] ) ) {
			do_action( 'pushpress_verify_http_failure' );
			$this->return_error( "Error verifying callback URL - {$response->errors['http_request_failed'][0]}" );
		}

		$status_code = (int) $response['response']['code'];
		if ( $status_code < 200 || $status_code > 299 ) {
			do_action( 'pushpress_verify_not_2xx_failure' );
			$this->return_error( "Error verifying callback URL, HTTP status code: {$status_code}" );
		}

		if ( trim( $response['body'] ) != $challenge ) {
			do_action( 'pushpress_verify_challenge_failure' );
			$this->return_error( 'Error verifying callback URL, the challenge token did not match' );
		}
	} // function verify_request
} // class PuSHPress
