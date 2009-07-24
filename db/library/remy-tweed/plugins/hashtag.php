<?php
class hashtag {
    function run($tweet) {
        if (stripos($tweet->text, '#') !== false) {
            $tweet->text = preg_replace('/(^|[^\w])(#[\d\w\-]+)/', '$1<a href="http://search.twitter.com/search?q=$2">$2</a>', $tweet->text);
        } 
        
        return $tweet;
    }
}
?>