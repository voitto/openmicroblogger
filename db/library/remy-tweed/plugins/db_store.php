<?php
class db_store {
    var $config;
    var $db;
    var $connected;
    var $table = 'tweets';
    var $initialised = false;
    
    function db_store($config = null) {
        $this->connected = false;
        // required for db disconnect
        register_shutdown_function(array($this, 'deconstructor'));
        
        if ($config != null) {
            $this->config = $config;
            $this->db = mysql_connect($config['database']['host'], $config['database']['user'], $config['database']['password']);
            
            if ($this->db) {
                if (mysql_select_db($config['database']['database'], $this->db)) {
                    $this->connected = true;
                    $this->setup();
                } else {
                    user_error("Could not connect to database");
                }
            } else {
              user_error("Could not connect to mysql host");
            }
        }        
    }
    
    function setup() {
        if (!$this->connected) {
            return;
        }

        $result = mysql_query('desc ' . $this->table, $this->db);
        if (!$result) {
            // create table
            $sql = 'create table ' . $this->table . '(id int not null, avatar varchar(255), name char(255), screen_name char(255), message text, createdDate datetime, primary key (id), index screen_name (screen_name), index date (createdDate))';
            if (!mysql_query($sql, $this->db)) {
                user_error('Could not create ' . $this->table . ' table for DB storage');
            } else {
                $this->initialised = true;
            }
        } else {
            $this->initialised = true;
        }
    }
    
    function deconstructor() {
        if ($this->connected) {
            mysql_close($this->db);
        }
    }
    
    function run($tweet) {
        if (($this->initialised && $this->connected) || $this->config['debug']) {
            $sql = sprintf('insert into tweets (id, avatar, name, screen_name, message, createdDate) values (%d, "%s", "%s", "%s", "%s", "%s")', $tweet->id, mysql_escape_string($tweet->profile_image_url), mysql_escape_string($tweet->from_user), mysql_escape_string($tweet->from_user), mysql_escape_string($tweet->text), date('c', $tweet->created_at));
            
            if ($this->config['debug']) {
                echo $sql . ";\n";
            } elseif (!mysql_query($sql, $this->db)) {
                user_error("db_store failed: $sql");
            }
        }
        
        return $tweet;
    }
}

?>