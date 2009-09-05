<?php

// Add page to menu
function wp_ozh_yourls_add_page() {
	// Loading CSS & JS *only* where needed. Do it this way too, goddamnit.
	$page = add_options_page('YOURLS: WordPress to Twitter', 'YOURLS', 'manage_options', 'ozh_yourls', 'wp_ozh_yourls_do_page');
	add_action("load-$page", 'wp_ozh_yourls_add_css_js_plugin');
	// Add the JS & CSS for the char counter. This is too early to check wp_ozh_yourls_generate_on('post') or ('page')
	add_action('load-post.php', 'wp_ozh_yourls_add_css_js_post');
	add_action('load-post-new.php', 'wp_ozh_yourls_add_css_js_post');
	add_action('load-page.php', 'wp_ozh_yourls_add_css_js_post');
	add_action('load-page-new.php', 'wp_ozh_yourls_add_css_js_post');
}

// Add style & JS on the plugin page
function wp_ozh_yourls_add_css_js_plugin() {
	$plugin_url = WP_PLUGIN_URL.'/'.plugin_basename( dirname(dirname(__FILE__)) );
	wp_enqueue_script('yourls_js', $plugin_url.'/res/yourls.js');
	wp_enqueue_style('yourls_css', $plugin_url.'/res/yourls.css');
}

// Add style & JS on the Post/Page Edit page
function wp_ozh_yourls_add_css_js_post() {
	global $pagenow;
	$current = str_replace( array('-new.php', '.php'), '', $pagenow);
	if ( wp_ozh_yourls_generate_on($current) ) {
		$plugin_url = WP_PLUGIN_URL.'/'.plugin_basename( dirname(dirname(__FILE__)) );
		wp_enqueue_script('yourls_js', $plugin_url.'/res/post.js');
		wp_enqueue_style('yourls_css', $plugin_url.'/res/post.css');
	}
}

// Sanitize & validate options that are submitted
function wp_ozh_yourls_sanitize($in) {
	// all options: sanitized strings
	$in = array_map( 'esc_attr', $in);
	// extra zealotry : 0 or 1 for generate_on_post, tweet_on_post, generate_on_page, tweet_on_page
	foreach( array('generate_on_post', 'tweet_on_post', 'generate_on_page', 'tweet_on_page') as $item ) {
		$in[$item] = ( $in[$item] == 1 ? 1 : 0 );
	}
	return $in;
}

// Draw the option page
function wp_ozh_yourls_do_page() {
	$plugin_url = WP_PLUGIN_URL.'/'.plugin_basename( dirname(dirname(__FILE__)) );
	?>
	<div class="wrap">
	
	<?php /** ?>
	<pre><?php print_r(get_option('ozh_yourls')); ?></pre>
	<?php /**/ ?>

	<div class="icon32" id="icon-plugins"><br/></div>
	<h2>YOURLS - WordPress to Twitter</h2>
	
	<div id="y_logo">
		<div class="y_logo">
			<a href="http://yourls.org/"><img src="<?php echo $plugin_url; ?>/res/yourls-logo.png"></a>
		</div>
		<div class="y_text">
			<p><a href="http://yourls.org/">YOURLS</a> is a free URL shortener service you can run on your webhost to have your own personal TinyURL.</p>
			<p>This plugin is a bridge between <a href="http://yourls.org/">YOURLS</a>, <a href="http://twitter.com/">Twitter</a> and your blog: when you'll submit a new post or page, your blog will tap into YOURLS to generate a short URL for it, and will then tweet it.</p>
			<p>Note that, for maximum fun, this plugin also supports a few other public URL shortener services: tr.im, is.gd, tinyURL and bit.ly</p>
		</div>
	</div>
	
	<form method="post" action="options.php">
	<?php settings_fields('wp_ozh_yourls_options'); ?>
	<?php $ozh_yourls = get_option('ozh_yourls'); ?>

	<h3>URL Shortener Settings</h3>

	<table class="form-table">

	<tr valign="top">
	<th scope="row">URL Shortener Service</th>
	<td>

	<label for="y_service">You are using:</label>
	<select name="ozh_yourls[service]" id="y_service" class="y_toggle">
	<option value="" <?php selected( '', $ozh_yourls['service'] ); ?> >Please select..</option>
	<option value="yourls" <?php selected( 'yourls', $ozh_yourls['service'] ); ?> >your own YOURLS install</option>
	<option value="other" <?php selected( 'other', $ozh_yourls['service'] ); ?> >another public service such as TinyURL or tr.im</option>
	</select>
	
	<?php $hidden = ( $ozh_yourls['service'] == 'yourls' ? '' : 'y_hidden' ) ; ?>
	<div id="y_show_yourls" class="<?php echo $hidden; ?> y_service y_level2">
		<label for="y_location">Your YOURLS installation is</label>
		<select name="ozh_yourls[location]" id="y_location" class="y_toggle">
		<option value="" <?php selected( '', $ozh_yourls['location'] ); ?> >Please select...</option>
		<option value="local" <?php selected( 'local', $ozh_yourls['location'] ); ?> >local, on the same webserver</option>
		<option value="remote" <?php selected( 'remote', $ozh_yourls['location'] ); ?> >remote, on another webserver</option>
		</select>
		
		<?php $hidden = ( $ozh_yourls['location'] == 'local' ? '' : 'y_hidden' ) ; ?>
		<div id="y_show_local" class="<?php echo $hidden; ?> y_location y_level3">
			<label for="y_path">Path to the YOURLS config file</label> <input type="text" class="y_longfield" id="y_path" name="ozh_yourls[yourls_path]" value="<?php echo $ozh_yourls['yourls_path']; ?>"/> <span id="y_test_path"></span><br/>
			<em>Example: <tt>/home/you/site.com/yourls/includes/config.php</tt></em>
		</div>
		
		<?php $hidden = ( $ozh_yourls['location'] == 'remote' ? '' : 'y_hidden' ) ; ?>
		<div id="y_show_remote" class="<?php echo $hidden; ?> y_location y_level3">
			<label for="y_url">URL to the YOURLS API</label> <input type="text" id="y_url" class="y_longfield" name="ozh_yourls[yourls_url]" value="<?php echo $ozh_yourls['yourls_url']; ?>"/> <span id="y_test_url"></span><br/>
			<em>Example: <tt>http://site.com/yourls-api.php</tt></em><br/>
			<label for="y_yourls_login">YOURLS Login</label> <input type="text" id="y_yourls_login" name="ozh_yourls[yourls_login]" value="<?php echo $ozh_yourls['yourls_login']; ?>"/><br/>
			<label for="y_yourls_passwd">YOURLS Password</label> <input type="password" id="y_yourls_passwd" name="ozh_yourls[yourls_password]" value="<?php echo $ozh_yourls['yourls_password']; ?>"/><br/>
		</div>
		
	</div>
	
	<?php $hidden = ( $ozh_yourls['service'] == 'other' ? '' : 'y_hidden' ) ; ?>
	<div id="y_show_other" class="<?php echo $hidden; ?> y_service y_level2">

		<label for="y_other">Public service</label>
		<select name="ozh_yourls[other]" id="y_other" class="y_toggle">
		<option value="" <?php selected( '', $ozh_yourls['other'] ); ?> >Please select...</option>
		<option value="trim" <?php selected( 'trim', $ozh_yourls['other'] ); ?> >tr.im</option>
		<option value="rply" <?php selected( 'rply', $ozh_yourls['other'] ); ?> >rp.ly</option>
		<!--<option value="pingfm" <?php selected( 'pingfm', $ozh_yourls['other'] ); ?> >ping.fm</option>-->
		<option value="bitly" <?php selected( 'bitly', $ozh_yourls['other'] ); ?> >bit.ly</option>
		<option value="tinyurl" <?php selected( 'tinyurl', $ozh_yourls['other'] ); ?> >tinyURL</option>
		<option value="isgd" <?php selected( 'isgd', $ozh_yourls['other'] ); ?> >is.gd</option>
		</select>
		
		<?php $hidden = ( $ozh_yourls['other'] == 'bitly' ? '' : 'y_hidden' ) ; ?>
		<div id="y_show_bitly" class="<?php echo $hidden; ?> y_other y_level3">
			<label for="y_api_bitly_login">API Login</label> <input type="text" id="y_api_bitly_login" name="ozh_yourls[bitly_login]" value="<?php echo $ozh_yourls['bitly_login']; ?>"/> (case sensitive!)<br/>
			<label for="y_api_bitly_pass">API Key</label> <input type="text" id="y_api_bitly_pass" class="y_longfield" name="ozh_yourls[bitly_password]" value="<?php echo $ozh_yourls['bitly_password']; ?>"/><br/>
			<em>If you have a <a href="http://bit.ly/account/">bit.ly</a> account, entering your credentials will link the short URLs to it</em>
		</div>

		<?php $hidden = ( $ozh_yourls['other'] == 'trim' ? '' : 'y_hidden' ) ; ?>
		<div id="y_show_trim" class="<?php echo $hidden; ?> y_other y_level3">
			<label for="y_api_trim_login">Username</label> <input type="text" id="y_api_trim_login" name="ozh_yourls[trim_login]" value="<?php echo $ozh_yourls['trim_login']; ?>"/><br/>
			<label for="y_api_trim_pass">Password</label> <input type="password" id="y_api_trim_pass" name="ozh_yourls[trim_password]" value="<?php echo $ozh_yourls['trim_password']; ?>"/><br/>
			<em>If you have a <a href="http://tr.im/">tr.im</a> account, entering your credentials will link the short URLs to it</em>
		</div>

		<?php $hidden = ( $ozh_yourls['other'] == 'rply' ? '' : 'y_hidden' ) ; ?>
		<div id="y_show_rply" class="<?php echo $hidden; ?> y_other y_level3">
			<label for="y_api_rply_login">Username</label> <input type="text" id="y_api_rply_login" name="ozh_yourls[rply_login]" value="<?php echo $ozh_yourls['rply_login']; ?>"/><br/>
			<label for="y_api_rply_pass">Password</label> <input type="password" id="y_api_rply_pass" name="ozh_yourls[rply_password]" value="<?php echo $ozh_yourls['rply_password']; ?>"/><br/>
			<em>If you have a <a href="http://rp.ly/">rp.ly</a> account, entering your credentials will link the short URLs to it</em>
		</div>
		
		<?php $hidden = ( $ozh_yourls['other'] == 'pingfm' ? '' : 'y_hidden' ) ; ?>
		<div id="y_show_pingfm" class="<?php echo $hidden; ?> y_other y_level3">
			<label for="y_api_pingfm_user_app_key">Web Key</label> <input type="text" id="y_api_pingfm_user_app_key" name="ozh_yourls[pingfm_user_app_key]" value="<?php echo $ozh_yourls['pingfm_user_app_key']; ?>"/><br/>
			<em>If you have a <a href="http://ping.fm/">ping.fm</a> account, enter your private <a href="http://ping.fm/key/">Web Key</a></em>
		</div>
		
		<?php $hidden = ( $ozh_yourls['other'] == 'tinyurl' ? '' : 'y_hidden' ) ; ?>
		<div id="y_show_tinyurl" class="<?php echo $hidden; ?> y_other y_level3">
			<em>(this service needs no authentication)</em>
		</div>
		

		<?php $hidden = ( $ozh_yourls['other'] == 'isgd' ? '' : 'y_hidden' ) ; ?>
		<div id="y_show_isgd" class="<?php echo $hidden; ?> y_other y_level3">
			<em>(this service needs no authentication)</em>
		</div>
		
	</div>

	</td>
	</tr>
	</table>

	<h3>Twitter Settings</h3> 

	<table class="form-table">

	<tr valign="top">
	<th scope="row">Twitter Login</th>
	<td><input id="tw_login" name="ozh_yourls[twitter_login]" type="text" value="<?php echo $ozh_yourls['twitter_login']; ?>"/></td>
	</tr>

	<tr valign="top">
	<th scope="row">Twitter Password</th>
	<td><input id="tw_passwd" name="ozh_yourls[twitter_password]" type="password" value="<?php echo $ozh_yourls['twitter_password']; ?>"/></td>
	</tr>
	
	</table>
	
	<h3>When to generate a short URL and tweet it</h3> 

	<table class="form-table">

	<tr valign="top">
	<th scope="row">New <strong>post</strong> published</th>
	<td>
	<input class="y_toggle" id="generate_on_post" name="ozh_yourls[generate_on_post]" type="checkbox" value="1" <?php checked( '1', $ozh_yourls['generate_on_post'] ); ?> /><label for="generate_on_post"> Generate short URL</label><br/>
	<?php $hidden = ( $ozh_yourls['generate_on_post'] == '1' ? '' : 'y_hidden' ) ; ?>
	<div id="y_show_generate_on_post" class="<?php echo $hidden; ?> generate_on_post">
		<input id="tweet_on_post" name="ozh_yourls[tweet_on_post]" type="checkbox" value="1" <?php checked( '1', $ozh_yourls['tweet_on_post'] ); ?> /><label for="tweet_on_post"> Send a tweet with the short URL</label>
	</div>
	</td>
	</tr>

	<tr valign="top">
	<th scope="row">New <strong>page</strong> published</th>
	<td>
	<input class="y_toggle" id="generate_on_page" name="ozh_yourls[generate_on_page]" type="checkbox" value="1" <?php checked( '1', $ozh_yourls['generate_on_page'] ); ?> /><label for="generate_on_page"> Generate short URL</label><br/>
	<?php $hidden = ( $ozh_yourls['generate_on_page'] == '1' ? '' : 'y_hidden' ) ; ?>
	<div id="y_show_generate_on_page" class="<?php echo $hidden; ?> generate_on_page">
		<input id="tweet_on_page" name="ozh_yourls[tweet_on_page]" type="checkbox" value="1" <?php checked( '1', $ozh_yourls['tweet_on_page'] ); ?> /><label for="tweet_on_page"> Send a tweet with the short URL</label>
	</div>
	</td>
	</tr>

	</table>

	<h3>What to tweet</h3> 

	<table class="form-table">

	<tr valign="top">
	<th scope="row">Tweet message</th>
	<td><input id="tw_msg" name="ozh_yourls[twitter_message]" type="text" size="50" value="<?php echo $ozh_yourls['twitter_message']; ?>"/><br/>
	This is your tweet template. The plugin will replace <tt>%T</tt> with the post title and <tt>%U</tt> with its short URL, with as much text as possible so it fits in the 140 character limit<br/>
	Examples (click one to copy)<br/>
	<ul id="tw_msg_sample">
		<li><code class="tw_msg_sample">Fresh on <?php bloginfo();?>: %T %U</code></li>
		<li><code class="tw_msg_sample">On the blog: %T (%U)</code></li>
		<li><code class="tw_msg_sample">%T - %U</code></li>
	</ul>
	<em>Tip: Keep the tweet message template short!</em>
	</td>
	</td>
	</tr>

	</table>


	<p class="submit">
	<input type="submit" class="button-primary y_submit" value="<?php _e('Save Changes') ?>" />
	</p>

	</form>

	</div> <!-- wrap -->

	
	<?php	
}

// Add meta boxes to post & page edit
function wp_ozh_yourls_addbox() {
	// add_meta_box($id, $title, $callback, $page, $context = 'advanced', $priority = 'default')
	if ( wp_ozh_yourls_generate_on('post') )
		add_meta_box('yourlsdiv', 'Short URL &amp; Tweet', 'wp_ozh_yourls_drawbox', 'post', 'side', 'default');
	if ( wp_ozh_yourls_generate_on('page') )
		add_meta_box('yourlsdiv', 'Short URL &amp; Tweet', 'wp_ozh_yourls_drawbox', 'page', 'side', 'default');
}

// Draw meta box
function wp_ozh_yourls_drawbox($post) {
	$type = $post->post_type;
	$status = $post->post_status;
	$id = $post->ID;
	$title = $post->post_title;

	if ($type != 'post' && $type !='page')
		return; // Not sure this can actually happen since add_meta_box() should take care of this. Just in case.
	
	// Too early, young Padawan
	if ($status != 'publish') {
		echo '<p>When you publish this post, you will be able here to (re)promote it via Twitter.</p>
		<p>Depending on <a href="options-general.php?page=ozh_yourls">configuration</a>, this also happens automagically when you press "Publish" of course :)</p>';
		return;
	}
	
	$shorturl = wp_ozh_yourls_geturl( $id );
	// Bummer, could not generate a short URL
	if (!shorturl) {
		echo '<p>Bleh. The URL shortening service you configured could not be reached as of now. This might be a temporary problem, please try again later!</p>';
		return;
	}
	

	
	global $wp_ozh_yourls;
	$action = 'Tweet this';
	$promote = "Promote this $type";
	$tweeted = get_post_meta( $id, 'yourls_tweeted', true );
	$account = $wp_ozh_yourls['twitter_login'];

	wp_nonce_field( 'yourls', '_ajax_yourls', false );
	echo '
	<input type="hidden" id="yourls_post_id" value="'.$id.'" />
	<input type="hidden" id="yourls_shorturl" value="'.$shorturl.'" />
	<input type="hidden" id="yourls_twitter_account" value="'.$account.'" />';
	
	echo '<p><strong>Short URL</strong></p>';
	echo '<div id="yourls-shorturl">';
	
	echo "<p>This $type's short URL: <strong><a href='$shorturl'>$shorturl</a></strong></p>
	<p>You can click Reset to generate another short URL if you picked another URL shortening service in the <a href='options-general.php?page=ozh_yourls'>plugin options</a></p>";
	echo '<p style="text-align:right"><input class="button" id="yourls_reset" type="submit" value="Reset short URL" /></p>';
	echo '</div>';
	
	echo '<p><strong>'.$promote.' on <a href="http://twitter.com/'.$account.'">@'.$account.'</a>: </strong></p>
	<div id="yourls-promote">';
	if ($tweeted) {
		$action = 'Retweet this';
		$promote = "Promote this $type again";
		echo '<p><em>Note:</em> this post has already been tweeted. Not that there\'s something wrong to promote it again, of course :)</p>';
	}
	echo '<p><textarea id="yourls_tweet" rows="1" style="width:100%">'.wp_ozh_yourls_maketweet( $shorturl, $title ).'</textarea></p>
	<p style="text-align:right"><input class="button" id="yourls_promote" type="submit" value="'.$action.'" /></p>
	</div>';
	
	?>
	<script type="text/javascript">
	/* <![CDATA[ */
	(function($){
		var yourls = {
			// Send a tweet
			send: function() {
			
				var post = {};
				post['yourls_tweet'] = $('#yourls_tweet').val();
				post['yourls_post_id'] = $('#yourls_post_id').val();
				post['yourls_twitter_account'] = $('#yourls_twitter_account').val();
				post['action'] = 'yourls-promote';
				post['_ajax_nonce'] = $('#_ajax_yourls').val();

				$('#yourls-promote').html('<p>Please wait...</p>');

				$.ajax({
					type : 'POST',
					url : '<?php echo admin_url('admin-ajax.php'); ?>',
					data : post,
					success : function(x) { yourls.success(x, 'yourls-promote'); },
					error : function(r) { yourls.error(r, 'yourls-promote'); }
				});
			},
			
			// Reset short URL
			reset: function() {
			
				var post = {};
				post['yourls_post_id'] = $('#yourls_post_id').val();
				post['yourls_shorturl'] = $('#yourls_shorturl').val();
				post['action'] = 'yourls-reset';
				post['_ajax_nonce'] = $('#_ajax_yourls').val();

				$('#yourls-shorturl').html('<p>Please wait...</p>');

				$.ajax({
					type : 'POST',
					url : '<?php echo admin_url('admin-ajax.php'); ?>',
					data : post,
					success : function(x) { yourls.success(x, 'yourls-shorturl'); yourls.update(x); },
					error : function(r) { yourls.error(r, 'yourls-shorturl'); }
				});
			},
			
			// Update short URL in the tweet textarea
			update: function(x) {
				var r = wpAjax.parseAjaxResponse(x);
				r = r.responses[0];
				var oldurl = r.supplemental.old_shorturl;
				var newurl = r.supplemental.shorturl;
				var bg = jQuery('#yourls_tweet').css('backgroundColor');
				if (bg == 'transparent') {bg = '#fff';}

				$('#yourls_tweet')
					.val( $('#yourls_tweet').val().replace(oldurl, newurl) )
					.animate({'backgroundColor':'#ff8'}, 500, function(){
						jQuery('#yourls_tweet').animate({'backgroundColor':bg}, 500)
					});
			},
			
			// Ajax: success
			success : function(x, div) {
				if ( typeof(x) == 'string' ) {
					this.error({'responseText': x}, div);
					return;
				}

				var r = wpAjax.parseAjaxResponse(x);
				if ( r.errors )
					this.error({'responseText': wpAjax.broken}, div);

				r = r.responses[0];
				$('#'+div).html('<p>'+r.data+'</p>');
			},

			// Ajax: failure
			error : function(r, div) {
				var er = r.statusText;
				if ( r.responseText )
					er = r.responseText.replace( /<.[^<>]*?>/g, '' );
				if ( er )
					$('#'+div).html('<p>Error during Ajax request: '+er+'</p>');
			}
		};
		
		$(document).ready(function(){
			$('#yourls_promote').click(function(e) {
				yourls.send();
				e.preventDefault();
			});
			$('#yourls_reset').click(function(e) {
				yourls.reset();
				e.preventDefault();
			});
			
			$('#edit-slug-box').append('<span id="yourls-shorturl-button"><a onclick="prompt(\'Short URL:\', \'<?php echo $shorturl; ?>\'); return false;" class="button" href="#">Get Short URL</a></span>');
			$('#yourls-shorturl-button a').css('border-color','#bbf');
		})

	})(jQuery);
	/* ]]> */
	</script>

	<?php
}

?>