<?php

/**
 * A minimalist PHP Twitter API.
 * Inspired by Mike Verdone's <http://mike.verdone.ca> Python Twitter Tools
 * 
 * @author Travis Dent <tcdent@gmail.com>
 * @copyright (c) 2009 Travis Dent.
 * @version 0.2
 * 
 * Usage:
 * 
 * $twitter = new Twitter('username', 'password');
 * 
 * // Get the public timeline.
 * $tweets = $twitter->statuses->public_timeline();
 * 
 * // Get page two of the user's followers.
 * $entries = $twitter->statuses->followers(array('page' => 2));
 * 
 * // Send a direct message.
 * $twitter->direct_messages->new(array('user' => 12345, 'text' => 'foo'));
 * 
 * // Search.
 * $twitter->search(array('q' => 'foo'));
 */

class Twitter {
    
    private $user;
    private $pass;
    private $format;
    private $uri;
    
    public function __construct($user, $pass, $format='json', $uri=NULL){
        if(!in_array($format, array('json', 'xml', 'rss', 'atom')))
            throw new TwitterException("Unsupported format: $format");
        
        $this->user = $user;
        $this->pass = $pass;
        $this->format = $format;
        $this->uri = $uri;
    }
    
    public function __get($key){
        return new Twitter($this->user, $this->pass, $this->format, $key);
    }
    
    public function __call($method, $args){
        $args = (count($args) && is_array($args[0]))? $args[0] : FALSE;
        
        $curlopt = array(
            CURLOPT_USERPWD => sprintf("%s:%s", $this->user, $this->pass), 
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC, 
            CURLOPT_RETURNTRANSFER => TRUE, 
            // Twitter returns a HTTP code 417 if we send an expectation.
            CURLOPT_HTTPHEADER => array('Expect:')
        );
        
        $uri = ($this->uri)? sprintf("%s/%s", $this->uri, $method) : $method;
        
        if(array_key_exists('id', $args))
            $uri .= '/'.$args['id']; unset($args['id']);
        
        $url = sprintf("%s.twitter.com/%s.%s", 
            ($method == 'search')? 'search' : 'www', 
            $uri, 
            $this->format);
        
        if(in_array($method, array('new', 'create', 'update', 'destroy'))){
            $curlopt[CURLOPT_POST] = TRUE;
            if($args) $curlopt[CURLOPT_POSTFIELDS] = $args;
        }
        elseif($args){
            $url .= '?'.http_build_query($args);
        }
        
        $curl = curl_init($url);
        curl_setopt_array($curl, $curlopt);
        $data = curl_exec($curl);
        $meta = curl_getinfo($curl);
        curl_close($curl);
        
        if($meta['http_code'] != 200)
            throw new TwitterException(
              "Response code: {$meta['http_code']} from \n\t${url}");
        
        if($this->format == 'json')
            return json_decode($data);
        
        return $data;
    }
}

class TwitterException extends Exception {}

?>