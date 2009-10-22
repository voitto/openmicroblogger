<?php
/*
Plugin Name: Add RSS Cloud Element
Plugin URI: http://www.educer.org/add-rss-cloud-element/
Description: Adds the cloud element to the RSS 2.0 feed in Wordpress so that clients and aggregators know which rssCloud server they can use to subscribe to feed updates. Also pings the cloud server
whenever the feed is updated.
Version: 0.3
Author: Jeremy Felt
Author URI: http://www.educer.org
*/

/*  Copyright 2009 Jeremy Felt (jeremy.felt@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function admin_display_cloud_options(){
   // Used to display and handle updated settings for the plugin in the admin interface
   echo '<div class="wrap">' . "\n";
   echo '<h2>rssCloud Element Options</h2>' . "\n";
   if($_REQUEST['submit']){
      update_cloud_options();
   }
   display_cloud_form();
   echo '</div>' . "\n";
}

function display_cloud_options(){
   // Adds an options page for the plugin to the Wordpress admin site.
   add_options_page(
      'rssCloud Element',
      'rssCloud Element',
      'manage_options',
      __FILE__,
      'admin_display_cloud_options'
      );
}

function add_cloud_element(){
   // Grabs the stored rss cloud element options and pushes them out into the feed when called.
   $cloud_domain = get_option('cloud_domain');
   $cloud_port = get_option('cloud_port');
   $cloud_path = get_option('cloud_path');
   $cloud_function = get_option('cloud_function');
   $cloud_protocol = get_option('cloud_protocol');

   echo "<cloud domain=\"" . $cloud_domain . "\" port=\"" . $cloud_port . "\" path=\"" . $cloud_path . "\" registerProcedure=\"" . $cloud_function . "\" protocol=\"" . $cloud_protocol . "\" />";
}

function set_default_cloud_options(){
   // When the plugin is first activated, we fill with default values.
   add_option('cloud_domain','rpc.rsscloud.org','Cloud Domain');
   add_option('cloud_port','5337','Cloud Port');
   add_option('cloud_path','/rsscloud/pleaseNotify','Cloud Path');
   add_option('cloud_function','','Cloud Function');
   add_option('cloud_protocol','http-post','Cloud Protocol');
   add_option('cloud_ping','/rsscloud/ping','Cloud Ping Path');
}

function unset_default_cloud_options(){
   // When the plugin is deactivated, we remove those values.
   delete_option('cloud_domain');
   delete_option('cloud_port');
   delete_option('cloud_path');
   delete_option('cloud_function');
   delete_option('cloud_protocol');
   delete_option('cloud_ping');
}

register_activation_hook(__FILE__,'set_default_cloud_options');
register_deactivation_hook(__FILE__,'unset_default_cloud_options');

function update_cloud_options(){
   // Handle the update of cloud options when saved in the admin interface.
   $ok = true;

   if(isset($_REQUEST['cloud_domain'])){
      $cloud_domain = $_REQUEST['cloud_domain'];
      update_option('cloud_domain', $cloud_domain);
   }
   if(isset($_REQUEST['cloud_port'])){
      $cloud_port = $_REQUEST['cloud_port'];
      update_option('cloud_port', $cloud_port);
   }
   if(isset($_REQUEST['cloud_path'])){
      $cloud_path = $_REQUEST['cloud_path'];
      update_option('cloud_path', $cloud_path);
   }
   if(isset($_REQUEST['cloud_function'])){
      $cloud_function = $_REQUEST['cloud_function'];
      update_option('cloud_function', $cloud_function);
   }
   if(isset($_REQUEST['cloud_protocol'])){
      $cloud_protocol = $_REQUEST['cloud_protocol'];
      update_option('cloud_protocol', $cloud_protocol);
   }
   if(isset($_REQUEST['cloud_ping'])){
      $ping_path=$_REQUEST['cloud_ping'];
      update_option('cloud_ping',$ping_path);
    }

   if(!$ok){
      echo '<div id="message" class="error fade">';
      echo '<p>Failed to save options</p>';
      echo '</div>';
   } else {
      echo '<div id="message" class="update fade">';
      echo '<p>Options Saved</p>';
      echo '</div>';
   }
}

function display_cloud_form(){
   // Displays the options form in the admin interface.
   $current_domain = get_option('cloud_domain');
      if (empty($current_domain)) $current_domain = 'rpc.rsscloud.org';
   $current_port = get_option('cloud_port');
      if (empty($current_port)) $current_port = '5337';
   $current_path = get_option('cloud_path');
      if (empty($current_path)) $current_path = '/rsscloud/pleaseNotify';
   $current_function = get_option('cloud_function');
      if (empty($current_function)) $current_function = '';
   $current_protocol = get_option('cloud_protocol');
      if (empty($current_protocol)) $current_protocol = 'http-post';
    $ping_path = get_option('cloud_ping');
      if (empty($ping_path)) $ping_path="/rsscloud/ping";
   ?>
   <form method="post">
      <p>
      <label for="cloud_domain"><strong>Cloud Domain:</strong></label>
         <input type name="cloud_domain" value="<?php echo $current_domain; ?>" style="width:200px;" /><br />
      </p>
      <p>
      <label for="cloud_port"><strong>Cloud Port:</strong></label>
         <input type name="cloud_port" value="<?php echo $current_port; ?>" style="width:200px;" />
      </p>
      <p>
      <label for="cloud_path"><strong>Cloud Path:</strong></label>
         <input type name="cloud_path" value="<?php echo $current_path; ?>" style="width:200px;" />
      </p>
      <p>
      <label for="cloud_function"><strong>Cloud Function:</strong></label>
         <input type name="cloud_function" value="<?php echo $current_function; ?>" style="width:200px;" />
      </p>
      <p>
      <label for="cloud_protocol"><strong>Cloud Protocol:</strong></label>
         <input type name="cloud_protocol" value="<?php echo $current_protocol; ?>" style="width:200px;" />
      </p>
      <label for="cloud_ping"><strong>Ping Path:</strong></label>
         <input type name="cloud_ping" value="<?php echo $ping_path; ?>" style="width:200px;" />
        </p>
      <p>
      <input type="submit"  name="submit" value="Submit" />
      </p>
   <?php
}

function rss_cloud_ping(){
   /*
      This function pings the specified cloud server whenever the
      feed is updated. In release 0.3 (this one), only CURL is
      available to use, and there is no time out option handling.
      Would recommend commenting this out if experiencing lock-ups.

      Will be prettyed up and errored out in 0.4
   */

   $current_domain = get_option('cloud_domain');
      if (empty($current_domain)) $current_domain = 'rpc.rsscloud.org';
   $current_port = get_option('cloud_port');
      if (empty($current_port)) $current_port = '5337';
   $current_path = get_option('cloud_path');
      if (empty($current_path)) $current_path = '/rsscloud/pleaseNotify';
   $current_function = get_option('cloud_function');
      if (empty($current_function)) $current_function = '';
   $current_protocol = get_option('cloud_protocol');
      if (empty($current_protocol)) $current_protocol = 'http-post';
   $ping_path = get_option('cloud_ping');
      if (empty($ping_path)) $ping_path="/rsscloud/ping";

   $ping_url="http://" . $current_domain . ":" . $current_port . "" . $ping_path . "";
   /*
      If in doubt about your settings, uncomment the next line:
   */
   //$ping_url="http://rpc.rsscloud.org:5337/rsscloud/ping";

   $ping_vars="url=" . get_bloginfo('rss2_url');

   $ch = curl_init();
   curl_setopt ($ch, CURLOPT_URL, $ping_url);
   curl_setopt ($ch, CURLOPT_HEADER, 0); /// Header control
   curl_setopt ($ch, CURLOPT_PORT, $current_port);
   curl_setopt ($ch, CURLOPT_POST, true);  /// tell it to make a POST, not a GET
   curl_setopt ($ch, CURLOPT_POSTFIELDS, $ping_vars);
   curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
   $xml_response = curl_exec ($ch);
   curl_close ($ch);
}

add_action('publish_post', 'rss_cloud_ping');
add_action('admin_menu', 'display_cloud_options');
add_action('rss2_head', 'add_cloud_element');

?>
