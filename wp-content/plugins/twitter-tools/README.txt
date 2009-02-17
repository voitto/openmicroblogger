=== Twitter Tools ===
Tags: twitter, tweet, integration, post, digest, notify, integrate, archive, widget
Contributors: alexkingorg
Requires at least: 2.3
Tested up to: 2.3.1
Stable tag: 1.1b1

Twitter Tools is a plugin that creates a complete integration between your WordPress blog and your Twitter account.

== Details ==

Twitter Tools integrates with Twitter by giving you the following functionality:

* Archive your Twitter tweets (downloaded every 15 minutes)
* Create a blog post from each of your tweets
* Create a daily digest post of your tweets
* Create a tweet on Twitter whenever you post in your blog, with a link to the blog post
* Post a tweet from your sidebar
* Post a tweet from the WP Admin screens
* Pass your tweets along to another service (via API hook)


== Installation ==

1. Download the plugin archive and expand it (you've likely already done this).
2. Put the 'twitter-tools.php' file into your wp-content/plugins/ directory.
3. Go to the Plugins page in your WordPress Administration area and click 'Activate' for Twitter Tools.
4. Go to the Twitter Tools Options page (Options > Twitter Tools) to set your Twitter account information and preferences.


== Configuration ==

There are a number of configuration options for Twitter Tools. You can find these in Options > Twitter Tools.

== Showing Your Tweets ==

= Widget Friendly =

If you are using widgets, you can drag Twitter Tools to your sidebar to display your latest tweets.


= Template Tags =

If you are not using widgest, you can use a template tag to add your latest tweets to your sidebar.

`<?php aktt_sidebar_tweets(); ?>`


If you just want your latest tweet, use this template tag.

`<?php aktt_latest_tweet(); ?>`


== Hooks/API ==

Twitter Tools contains a hook that can be used to pass along your tweet data to another service (for example, some folks have wanted to be able to update their Facebook status). To use this hook, create a plugin and add an action to:

`aktt_add_tweet`

Your plugin function will receive an `aktt_tweet` object as the first parameter.

Example psuedo-code:

`function my_status_update($tweet) { // do something here }`
`add_action('aktt_add_tweet', 'my_status_update')`


== Known Issues ==

* Only one Twitter account is supported (not one account per author).
* Tweets are not deleted from the tweet table in your WordPress database when they are deleted from Twitter. To delete from your WordPress database, use a database admin tool like phpMyAdmin.
* The relative date function isn't fully localized.


== Frequently Asked Questions ==

= What happens if I have both my tweets posting to my blog as posts and my posts sent to Twitter? Will it cause the world to end in a spinning fireball of death? = 

Actually, Twitter Tools has taken this into account and you can safely enable both creating posts from your tweets and tweets from your posts without duplicating them in either place.

= Anything else? =

That about does it - enjoy!

--Alex King

http://alexking.org/projects/wordpress
