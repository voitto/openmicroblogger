<?php

class TwitterUser extends Model {
  
  function TwitterUser() {
    
    $this->auto_field('id');
    
    $this->text_field('description');
    $this->text_field('location');
    
    $this->char_field('screen_name');
    $this->char_field('url');
    $this->char_field('name');
    $this->char_field('protected');
    $this->char_field('profile_image_url');
    $this->char_field('twitter_id');
    
    $this->int_field('followers_count');
    
  }
  
}
