<?php
class ignore_replies {
    function run($tweet) {
        if (stripos($tweet->text, '@') !== 0) {
            return $tweet;
        } else {
            return false;
        }
    }
}
?>