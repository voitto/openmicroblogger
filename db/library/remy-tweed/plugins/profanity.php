<?php
class profanity {
    function run($tweet) {
        if (preg_match("/fuck/", $tweet->text)) {
            return false;
        } else {
            return $tweet;
        }
    }
}

?>