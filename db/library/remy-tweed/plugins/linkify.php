<?php
class linkify {
    var $matches, $replacements;
    
    function linkify() {
        // note - this order is important, i.e. links at the top, then anything else
        $this->matches = array(
            '(/[A-Za-z]+:\/\/[A-Za-z0-9-_]+\.[A-Za-z0-9-_:%&\?\/.=]+/)',
            '/(^|[^\w])(#[\d\w\-]+)/',
            '/(^|[^\w])(@([\d\w\-]+))/'
        );
        
        $this->replacements = array(
            '<a href="$1">$1</a>',
            '$1<a href="http://search.twitter.com/search?q=$2">$2</a>',
            '$1@<a href="http://twitter.com/$3">$3</a>'
        );
    }
    
    function run($tweet) {
        $tweet->text = preg_replace($this->matches, $this->replacements, $tweet->text);
        
        return $tweet;
    }
}
?>
