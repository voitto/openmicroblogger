<?php

// Copyright 2008 FriendFeed
//
// Licensed under the Apache License, Version 2.0 (the "License"); you may
// not use this file except in compliance with the License. You may obtain
// a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
// WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
// License for the specific language governing permissions and limitations
// under the License.

// This module requires the Curl PHP module, available in PHP 4 and 5
assert(function_exists("curl_init"));


// Methods to interact with the FriendFeed API
//
// Detailed documentation is available at http://friendfeed.com/api/.
//
// Many parts of the FriendFeed API require authentication. To support
// authentication, FriendFeed gives users a "remote key" that they give to
// third party applications to access FriendFeed. The user's nickname and that
// remote key are given to the constructor of the FriendFeed class and are
// included as authentication with every call made on the instance of the
// class. For example:
//
//     $friendfeed = new FriendFeed($_GET["nickname"], $_GET["auth_key"]);
//     $entry = $friendfeed->publish_message("Testing the FriendFeed API");
// 
// Users can get their remote key from http://friendfeed.com/remotekey. You
// should direct users who don't know their remote key to that page.
// For guidelines on user interface and terminology, check out
// http://friendfeed.com/api/guidelines.
class FriendFeed {
    // Constructs a FriendFeed session with the given authentication params.
    function FriendFeed($auth_nickname=null, $auth_key=null) {
	$this->auth_nickname = $auth_nickname;
	$this->auth_key = $auth_key;
    }

    // Returns the public feed with everyone's public entries.
    //
    // Authentication is not required.
    function fetch_public_feed($service=null, $start=0, $num=30) {
	return $this->fetch_feed("/api/feed/public", $service, $start, $num);
    }

    // Returns the entries shared by the user with the given nickname.
    //
    // Authentication is required if the user's feed is not public.
    function fetch_user_feed($nickname, $service=null, $start=0, $num=30) {
	return $this->fetch_feed("/api/feed/user/" . urlencode($nickname),
				 $service, $start, $num);
    }

    // Returns the most recent entries the given user has commented on.
    function fetch_user_comments_feed($nickname, $service=null, $start=0,
				      $num=30) {
	return $this->fetch_feed(
	    "/api/feed/user/" . urlencode($nickname) . "/comments",
	    $service, $start, $num);
    }

    // Returns the most recent entries the given user has "liked."
    function fetch_user_likes_feed($nickname, $service=null, $start=0,
				   $num=30) {
	return $this->fetch_feed(
	    "/api/feed/user/" . urlencode($nickname) . "/likes",
	    $service, $start, $num);
    }

    // Returns the most recent entries the given user has "liked."
    function fetch_user_discussion_feed($nickname, $service=null, $start=0,
					$num=30) {
	return $this->fetch_feed(
	    "/api/feed/user/" . urlencode($nickname) . "/discussion",
	    $service, $start, $num);
    }

    // Returns a merged feed with all of the given users' entries.
    //
    // Authentication is required if any one of the users' feeds is not
    // public.
    function fetch_multi_user_feed($nicknames, $service=null, $start=0,
				   $num=30) {
	return $this->fetch_feed("/api/feed/user", $service, $start, $num,
				 join(",", $nicknames));
    }

    // Returns the entries the authenticated user sees on their home page.
    //
    // Authentication is always required.
    function fetch_home_feed($service=null, $start=0, $num=30) {
	return $this->fetch_feed("/api/feed/home", $service, $start, $num);
    }

    // Searches over entries in FriendFeed.
    //
    // If the request is authenticated, the default scope is over all of the
    // entries in the authenticated user's Friends Feed. If the request is
    // not authenticated, the default scope is over all public entries.
    //
    // The query syntax is the same syntax as
    // http://friendfeed.com/search/advanced
    function search($query, $service=null, $start=0, $num=30) {
	return $this->fetch_feed("/api/feed/search", $service, $start, $num,
				 null, $query);
    }

    // Publishes the given textual message to the authenticated user's feed.
    //
    // See publish_link for additional options.
    function publish_message($message, $comment=null, $image_urls=null,
			     $images=null) {
	return $this->publish_link($message, null, $comment, $image_urls,
				   $images);
    }

    // Publishes the given link/title to the authenticated user's feed.
    //
    // Authentication is always required.
    //
    // image_urls is a list of URLs that will be downloaded and included as
    // thumbnails beneath the link. The thumbnails will all link to the
    // destination link. If you would prefer that the images link somewhere
    // else, you can specify images instead, which should be an array of
    // name-associated arrays of the form array("url"=>...,"link"=>...).
    // The thumbnail with the given url will link to the specified link.
    //
    // audio_urls is a list of MP3 URLs that will show up as a play
    // button beneath the link. You can optionally supply audio[]
    // instead, which should be a list of name-associated arrays of the 
    // form ("url"=> ..., "title"=> ...). The given title will appear when
    // the audio file is played.
    //
    // We return the parsed/published entry as returned from the server,
    // which includes the final thumbnail URLs as well as the ID for the
    // new entry.
    function publish_link($title, $link, $comment=null, $image_urls=null,
			  $images=null, $via=null, $audio_urls=null,
			  $audio=null, $room=null) {
	$post_args = array("title" => $title);
	if ($link) $post_args["link"] = $link;
	if ($comment) $post_args["comment"] = $comment;
	if ($via) $post_args["via"] = $via;
	if ($room) $post_args["room"] = $room;

	$post_images = array();
	if ($image_urls) {
	    foreach ($image_urls as $url) {
		$post_images[] = array("url" => $url);
	    }
	}
	if ($images) {
	    foreach ($images as $image) {
		$post_images[] = $image;
	    }
	}
	for ($i = 0; $i < count($post_images); $i++) {
	    $image = $post_images[$i];
	    $post_args["image" . $i . "_url"] = $image["url"];
	    if ($image["link"]) {
		$post_args["image" . $i . "_link"] = $image["link"];
	    }
	}

	$post_audio = array();
	if ($audio_urls) {
	    foreach ($audio_urls as $url) {
		$post_audio[] = array("url" => $url);
	    }
	}
	if ($audio) {
	    foreach ($audio as $clip) {
		$post_audio[] = $clip;
	    }
	}
	for ($i = 0; $i < count($post_audio); $i++) {
	    $clip = $post_audio[$i];
	    $post_args["audio" . $i . "_url"] = $clip["url"];
	    if ($clip["title"]) {
		$post_args["audio" . $i . "_title"] = $clip["link"];
	    }
	}

	$feed = $this->fetch_feed("/api/share", null, null, null, null, null,
				  $post_args);
	return $feed->entries[0];
    }

    // Adds the given comment to the entry with the given ID.
    //
    // We return the ID of the new comment, which can be used to edit or
    // delete the comment.
    function add_comment($entry_id, $body) {
	$result = $this->fetch("/api/comment", null, array(
            "entry" => $entry_id,
            "body" => $body
	));
        return $result->id;
    }

    // Updates the comment with the given ID.
    function edit_comment($entry_id, $comment_id, $body) {
	$this->fetch("/api/comment", null, array(
            "entry" => $entry_id,
            "comment" => $comment_id,
            "body" => $body
	));
    }

    // Deletes the comment with the given ID.
    function delete_comment($entry_id, $comment_id) {
	$this->fetch("/api/comment/delete", null, array(
            "entry" => $entry_id,
            "comment" => $comment_id,
	));
    }

    // Un-deletes the comment with the given ID.
    function undelete_comment($entry_id, $comment_id) {
	$this->fetch("/api/comment/delete", null, array(
            "entry" => $entry_id,
            "comment" => $comment_id,
	    "undelete" => 1,
	));
    }

    // 'Likes' the entry with the given ID.
    function add_like($entry_id) {
	$this->fetch("/api/like", null, array(
            "entry" => $entry_id,
	));
    }

    // Deletes the 'Like' for the entry with the given ID (if any).
    function delete_like($entry_id) {
	$this->fetch("/api/like/delete", null, array(
            "entry" => $entry_id,
	));
    }

    // Internal function to download, parse, and process FriendFeed feeds.
    function fetch_feed($uri, $service, $start, $num, $nickname=null,
			$query=null, $post_args=null) {
	$url_args = array(
	    "service" => $service,
	    "start" => $start,
	    "num" => $num,
        );
	if ($nickname) $url_args["nickname"] = $nickname;
	if ($query) $url_args["q"] = $query;
	$feed = $this->fetch($uri, $url_args, $post_args);
	if (!$feed) return null;

	// Parse all the dates in the feed
	foreach ($feed->entries as $entry) {
	    $entry->updated = strtotime($entry->updated);
	    $entry->published = strtotime($entry->published);
	    foreach ($entry->comments as $comment) {
		$comment->date = strtotime($comment->date);
	    }
	    foreach ($entry->likes as $like) {
		$like->date = strtotime($like->date);
	    }
	}
	return $feed;
    }

    // Performs an authenticated FF request, parsing the JSON response.
    function fetch($uri, $url_args=null, $post_args=null) {
	if (!$url_args) $url_args = array();
	$url_args["format"] = "json";
	$pairs = array();
	foreach ($url_args as $name => $value) {
	    $pairs[] = $name . "=" . urlencode($value);
	}
	$url = "http://friendfeed.com" . $uri . "?" . join("&", $pairs);

	$curl = curl_init("friendfeed.com");
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	if ($this->auth_nickname && $this->auth_key) {
	    curl_setopt($curl, CURLOPT_USERPWD,
			$this->auth_nickname . ":" . $this->auth_key);
	    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	}
	if ($post_args) {
	    curl_setopt($curl, CURLOPT_POST, 1);
	    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_args);
	}
	$response = curl_exec($curl);
	$info = curl_getinfo($curl);
	curl_close($curl);
	if ($info["http_code"] != 200) {
	    return null;
	}
	return $this->json_decode($response);
    }

    // JSON decoder that uses the PHP 5.2+ functionality if available.
    function json_decode($str) {
	if (function_exists("json_decode")) {
	    return json_decode($str);
	} else {
	    require_once("JSON.php");
	    $json = new Services_JSON();
	    return $json->decode($str);
	}
    }
}


function test_friendfeed() {
    header("Content-Type: text/plain");

    // Fill in a nickname and a valid remote key below for authenticated
    // actions like posting an entry and reading a protected feed
    // $session = new FriendFeed($_POST["nickname"], $_POST["remote_key"]);
    $session = new FriendFeed();

    $feed = $session->fetch_public_feed();
    // $feed = $session->fetch_user_feed("bret");
    // $feed = $session->fetch_user_feed("paul", "twitter");
    // $feed = $session->fetch_user_discussion_feed("bret");
    // $feed = $session->fetch_multi_user_feed(array("bret", "paul", "jim"));
    // $feed = $session->search("who:bret friendfeed");
    foreach ($feed->entries as $entry) {
	print($entry->title . "\n");
    }

    if ($session->auth_nickname && $session->auth_key) {
	// The feed that the authenticated user would see on their home page
	$feed = $session->fetch_home_feed();

	// Post a message on this user's feed
        $entry = $session->publish_message("Testing the FriendFeed API");
	print("Posted new message at http://friendfeed.com/e/" . $entry->id . "\n");

	// Post a link on this user's feed
        $entry = $session->publish_link("Testing the FriendFeed API",
					"http://friendfeed.com/");
        print("Posted new link at http://friendfeed.com/e/" . $entry->id . "\n");

        // Post a link with two thumbnails on this user's feed
        $entry = $session->publish_link(
	    "Testing the FriendFeed API",
            "http://friendfeed.com/",
	    "Test comment on this test entry",
            array("http://friendfeed.com/static/images/jim-superman.jpg",
		  "http://friendfeed.com/static/images/logo.png"));
        print("Posted images at http://friendfeed.com/e/" . $entry->id . "\n");
    }
}

?>
