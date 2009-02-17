<?php
/*
Plugin Name: wp-XRDS-Simple
Plugin URI: http://singpolyma.net/plugins/xrds/
Description: Add XRDS information to your blog.
Version: 0.1
Author: Stephen Paul Weber
Author URI: http://singpolyma.net/
*/

//Licensed under an MIT-style license 

if(!$xrds_included) {
$xrds_included = true;

require_once dirname(__FILE__).'/../../wp-config.php';

function xrds_meta($echo=true) {
  $tag = '<meta http-equiv="X-XRDS-Location" content="'.get_bloginfo('home').'/?xrds" />'."\n";
  $tag .= '<meta http-equiv="X-Yadis-Location" content="'.get_bloginfo('home').'/?xrds" />'."\n";
  if($echo) echo $tag;
  return $tag;
}//end xrds_meta
function xrds_meta_head() {
  xrds_meta(true);
}//end function xrds_meta_head
add_action('wp_head','xrds_meta_head');

function register_xrd($id, $type=array(), $expires=false) {
  $xrd = get_option('xrds_simple');
  if(!is_array($xrd)) $xrd = array();
  $xrd[$id] = array('type' => $type, 'expires' => $expires, 'services' => array());
  update_option('xrds_simple', $xrd);
}//end function register_xrd

/*
Format of $content:
array(
  'NodeName (ie, Type)' => array( array('attribute' => 'value', 'content' => 'content string') , ... ) ,
)
*/
function register_xrd_service($xrd_id, $name, $content, $priority=10) {
  $xrd = get_option('xrds_simple');
  if(!is_array($xrd[$xrd_id])) register_xrd($xrd_id);
  $xrd[$xrd_id]['services'][$name] = array('priority' => $priority, 'content' => $content);
  update_option('xrds_simple', $xrd);
}//end register_xrd_service

function xrds_write() {
  header('Content-type: application/xrds+xml');
  $xrds = get_option('xrds_simple');
  if(!is_array($xrds)) $xrds = array();
  if($xrds['main']) {//make sure main is last
    $o = $xrds['main'];
    unset($xrds['main']);
    $xrds['main'] = $o;
  }//end if main
  echo '<?xml version="1.0" encoding="UTF-8" ?>'."\n";
  echo '<XRDS xmlns="xri://$xrds">'."\n";
  foreach($xrds as $id => $xrd) {
    echo '  <XRD xml:id="'.htmlspecialchars($id).'" xmlns="xri://$xrd*($v*2.0)" version="2.0" xmlns:simple="http://xrds-simple.net/core/1.0"';
    if($id == 'main') echo ' xmlns:openid="http://openid.net/xmlns/1.0"';
    echo '>'."\n";
    echo '    <Type>xri://$xrds*simple</Type>'."\n";
    if(!$xrd['type']) $xrd['type'] = array();
    if(!is_array($xrd['type'])) $xrd['type'] = array($xrd['type']);
    foreach($xrd['type'] as $type)
      echo '    <Type>'.htmlspecialchars($type).'</Type>'."\n";
    if($xrd['expires'])
      echo '  <Expires>'.htmlspecialchars($xrd['expires']).'</Expires>'."\n";
    foreach($xrd['services'] as $service) {
      echo '    <Service priority="'.floor($service['priority']).'">'."\n";
      foreach($service['content'] as $node => $nodes) {
        if(!is_array($nodes)) $nodes = array($nodes);//sanity check
        foreach($nodes as $attr) {
          echo '      <'.htmlspecialchars($node);
          if(!is_array($attr)) $attr = array('content' => $attr);//sanity check
          foreach($attr as $name => $v) {
            if($name == 'content') continue;
            echo ' '.htmlspecialchars($name).'="'.htmlspecialchars($v).'"';
          }//end foreach attr
          echo '>'.htmlspecialchars($attr['content']).'</'.htmlspecialchars($node).'>'."\n";
        }//end foreach content
      }//end foreach
      echo '    </Service>'."\n";
    }//end foreach services
    echo '  </XRD>'."\n";
  }//end foreach

  echo '</XRDS>'."\n";
  if(function_exists('ob_flush')) ob_flush();
  flush();
  exit;
}//end xrds_write

function xrds_checkXML($data) {//returns FALSE if $data is well-formed XML, errorcode otherwise
  $rtrn = 0;
  $theParser = xml_parser_create();
  if(!xml_parse_into_struct($theParser,$data,$vals)) {
    $errorcode = xml_get_error_code($theParser);
    if($errorcode != XML_ERROR_NONE && $errorcode != 27)
      $rtrn = $errorcode;
  }//end if ! parse
  xml_parser_free($theParser);
  return $rtrn;
}//end function checkXML

function xrds_page() {

  register_xrd_service('main', 'AtomPub Service', array(
    'Type' => array( array('content' => 'http://www.w3.org/2007/app') ),
    'MediaType' => array( array('content' => 'application/atomsvc+xml') ),
    'URI' => array( array('content' => get_bloginfo('wpurl').'/wp-app.php/service' ) ),
  ) );

  $xrds = get_option('xrds_simple');
  if(!is_array($xrds)) {$xrds = array(); update_option('xrds_simple',$xrds); register_xrd('main');}

  echo "<div class=\"wrap\">\n";
  echo "<h2>XRDS-Simple XRDs</h2>\n";

  if(isset($_REQUEST['delete'])) {
    unset($xrds[$_REQUEST['xrd_id']]);
    update_option('xrds_simple', $xrds);
    echo '<b>XRD deleted!</b>';
  }//end if delete

  if($_REQUEST['openid_server']) {
    $types = array();
    if(isset($_REQUEST['openid_sreg'])) {
      $types[] = array('content' => 'http://openid.net/sreg/1.0');
      $types[] = array('content' => 'http://openid.net/extensions/sreg/1.1');
    }//end if sreg
    register_xrd_service('main', 'OpenID 2.0', array(
      'Type' =>  array(array('content' => 'http://specs.openid.net/auth/2.0/signon'),array('content' => 'http://openid.net/signon/1.1'))+$types,
      'URI' => array($_REQUEST['openid_server']),
      'LocalID' => array($_REQUEST['openid_identifier']),
      'openid:Delegate' => array($_REQUEST['openid_identifier']),
    ) );
    $xrds = get_option('xrds_simple');
    echo '<b>OpenID delegated!</b>';
  }//end if openid_server

  echo '<ul>';
  foreach($xrds as $key => $data) {
    echo '<li>'.htmlentities($key).' - <form style="display:inline;" method="post" action=""><input type="hidden" name="xrd_id" value="'.htmlentities($key).'" /><input type="submit" name="delete" value="Delete" /></form></li>';
  }//end foreach
  echo '</ul>';

  echo "<h3>Delegate an OpenID</h3>\n";
  echo '<form action="" method="post"><div>';
  echo '<label for="openid_server">Server URI</label> &nbsp;<input type="text" name="openid_server" id="openid_server" value="'.htmlentities($services['OpenID 2.0']['URI']).'" /><br />';
  echo '<label for="openid_identifier">OpenID URI</label> <input type="text" name="openid_identifier" id="openid_identifier" value="'.htmlentities($services['OpenID 2.0']['LocalID']).'" /><br />';
  echo '<label for="openid_sreg">Simple Registration?</label> <input type="checkbox" name="openid_sreg" id="openid_sreg" checked="checked" /><br />';
  echo '<input type="submit" value="Save &raquo;" />';
  echo '</div></form>';

  echo "\n</div>";
}//end xrds_page

function xrds_tab($s) {
  add_submenu_page('options-general.php', 'XRDS-Simple', 'XRDS-Simple', 1, __FILE__, 'xrds_page');
  return $s;
}//end function
add_action('admin_menu', 'xrds_tab');

}//end if ! included

if(isset($_GET['xrds'])) {
  xrds_write();
} else {
  if(in_array('application/xrds+xml',explode(',',$_SERVER['HTTP_ACCEPT']))) {
    xrds_write();
  } else {
    header('X-XRDS-Location: '.get_bloginfo('home').'/?xrds');
    header('X-Yadis-Location: '.get_bloginfo('home').'/?xrds');
  }//end if header
}//end if ! in wordpress

?>