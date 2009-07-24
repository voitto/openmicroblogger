<?php
$path = pathinfo(__FILE__, PATHINFO_DIRNAME);
include_once($path . '/lib/spyc.php');

if( !function_exists('json_decode') ) {
    include_once($path . '/lib/JSON.php');
}

function get_from_url($config) {
    global $debug;
    
    // try to get the url
    $url = 'http://search.twitter.com/search.json?rpp=100&q=' . urlencode($config['twitter']['search']);
    
    if (isset($config['twitter']['since_id'])) {
        $url .= '&since_id=' . $config['twitter']['since_id'];
    }
    
    if ($debug) {
        echo $url . "\n";
    }

    if ($debug === true) {
        return test_data();
    }

    $json = '';
    $handle = fopen($url,'r');

    // check to make sure the URL didn't fail for some reason
    if ($handle) {
        while (!feof($handle)) {
            // loop through and build the XML file
            $json .= fread($handle, 1024);
        }
        // clean-up the file pointers
        fclose($handle);
    } else {
        // fopen failed for some reason!!!
        exit('There was an error fetching the URL');
    }

    if ($json == ''){
        // the XML is blank, nothing was pulled from the URL!!!
        exit('There was an error fetching the XML!');
    }
    
    return $json;
}

function test_data() {
    return "{\"results\":[{\"text\":\"@weirdhabit I always eat fucking sandwiches crust first working my way to the centre where the best fillings are. 9 minutes ago\",\"to_user_id\":8681803,\"to_user\":\"weirdhabit\",\"from_user\":\"kemenytest\",\"id\":1471174261,\"from_user_id\":8721360,\"iso_language_code\":\"en\",\"source\":\"&lt;a href=&quot;http:\\\/\\\/twitter.com\\\/&quot;&gt;web&lt;\\\/a&gt;\",\"profile_image_url\":\"http:\\\/\\\/static.twitter.com\\\/images\\\/default_profile_normal.png\",\"created_at\":\"Tue, 07 Apr 2009 18:34:17 +0000\"},{\"text\":\"@weirdhabit scratching my arse\",\"to_user_id\":8681803,\"to_user\":\"weirdhabit\",\"from_user\":\"kemenytest\",\"id\":1396549571,\"from_user_id\":8721360,\"iso_language_code\":\"en\",\"source\":\"&lt;a href=&quot;http:\\\/\\\/twitter.com\\\/&quot;&gt;web&lt;\\\/a&gt;\",\"profile_image_url\":\"http:\\\/\\\/static.twitter.com\\\/images\\\/default_profile_normal.png\",\"created_at\":\"Thu, 26 Mar 2009 20:19:00 +0000\"},{\"text\":\"@weirdhabit i have to have the #handle of a cup facing me on the table\",\"to_user_id\":8681803,\"to_user\":\"weirdhabit\",\"from_user\":\"joshtest\",\"id\":1396434187,\"from_user_id\":8681727,\"iso_language_code\":\"en\",\"source\":\"&lt;a href=&quot;http:\\\/\\\/twitter.com\\\/&quot;&gt;web&lt;\\\/a&gt;\",\"profile_image_url\":\"http:\\\/\\\/static.twitter.com\\\/images\\\/default_profile_normal.png\",\"created_at\":\"Thu, 26 Mar 2009 19:58:37 +0000\"},{\"text\":\"@weirdhabit i pick my nose in public\",\"to_user_id\":8681803,\"to_user\":\"weirdhabit\",\"from_user\":\"joshtest\",\"id\":1394943028,\"from_user_id\":8681727,\"iso_language_code\":\"en\",\"source\":\"&lt;a href=&quot;http:\\\/\\\/twitter.com\\\/&quot;&gt;web&lt;\\\/a&gt;\",\"profile_image_url\":\"http:\\\/\\\/static.twitter.com\\\/images\\\/default_profile_normal.png\",\"created_at\":\"Thu, 26 Mar 2009 15:28:54 +0000\"}],\"since_id\":0,\"max_id\":1472390428,\"refresh_url\":\"?since_id=1472390428&q=%40weirdhabit\",\"results_per_page\":15,\"total\":4,\"completed_in\":0.242095,\"page\":1,\"query\":\"%40weirdhabit\"}";
}

// localises the timestamp
function fix_date($data) {
    $results = $data->results;
    for ($i = 0; $i < count($results); $i++) {
        $results[$i]->created_at = strtotime($results[$i]->created_at);
    }
    
    return $results;
}

function update_config($config, $filename) {
    
    if (is_writable($filename)) {

        // In our example we're opening $filename in append mode.
        // The file pointer is at the bottom of the file hence
        // that's where $somecontent will go when we fwrite() it.
        if (!$handle = fopen($filename, 'w')) {
             echo "Cannot open file ($filename)";
             exit;
        }

        $yaml = Spyc::YAMLDump($config,4,60);

        // Write $somecontent to our opened file.
        if (fwrite($handle, $yaml) === FALSE) {
            echo "Cannot write to file ($filename)";
            exit;
        }

        // echo "Success, wrote ($yaml) to file ($filename)";

        fclose($handle);
    } else {
        echo "The file $filename is not writable";
    }
}


$config_file = $path . '/config.yaml';
$config = Spyc::YAMLLoad($config_file);
if ($config['debug']) {
    $debug = $config['debug'];
}

$json = fix_date(json_decode(get_from_url($config)));

if (count($json)) {
    if (isset($config['twitter']['since_id'])) {
        $config['twitter']['since_id'] = $json[0]->id; // store the latest
        update_config($config, $config_file);        
    }

    // go through each plugin specified in the config and apply it if it exists
    foreach ($config['plugins'] as $plugin) {
        if (file_exists($path . '/plugins/' . $plugin . '.php')) {
            // dynamically construct the new object, and pass each tweet to the 'run' method
            // updating the list of tweets as each plugin is processed.
            $plugin_fn = create_function('$tweets, $config', 'include("' . $path . '/plugins/' . $plugin . '.php"); $plugin = new ' . $plugin . '($config); $filtered_tweets = array(); foreach ($tweets as $tweet) { $filtered_tweet = $plugin->run($tweet); if ($filtered_tweet !== false) { $filtered_tweets[] = $filtered_tweet; } }; return $filtered_tweets;');
            $json = $plugin_fn($json, $config);
        }
    }   
} else {
    // none found
}
?>
