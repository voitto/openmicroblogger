<?php
class strip_reply {
    function run($tweet) {
        if (strpos($tweet->text, '@') == 0) {
            $tweet->text = preg_replace('/^@[\d\w\-]+\s+/', '', $tweet->text);
        } 
        
        return $tweet;
    }
}

?>