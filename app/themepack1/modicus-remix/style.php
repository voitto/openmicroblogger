<?php
require_once( dirname(__FILE__) . '../../../../wp-config.php');
require_once( dirname(__FILE__) . '/functions.php');
header("Content-type: text/css");

global $options;

foreach ($options as $value) {
	if (get_settings( $value['id'] ) === FALSE) { $$value['id'] = $value['std']; } else { $$value['id'] = get_settings( $value['id'] ); } }
?>

body{ 
font-family:<?php echo $mcs_body_font; ?>;
color:<?php echo $mcs_body_color; ?>;
background-color:<?php echo $mcs_body_backgroundcolor; ?>;
}

#wrapper {
width:<?php echo $mcs_wrapper_width; ?>;
}

#sidebar {
width:<?php echo $mcs_sidebar_width; ?>;
<?php if ($mcs_post_position == "Post Left/Sidebar Right") {
echo 'right:0;';
} else {
echo 'left:0;';
} ?>
}

.post {
width:<?php echo $mcs_post_width; ?>;
<?php if ($mcs_post_position == "Post Left/Sidebar Right") {
echo 'left:0;';
} else {
echo 'right:0;';
} ?>
}
