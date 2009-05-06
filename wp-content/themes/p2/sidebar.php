
<div id="sidebar">
<ul>

<?php global $request; ?>

<?php if ($request->resource == 'identities' && signed_in() && !(environment('use_sidebar_blocks'))) : ?>

<?php

      render_partial('admin');
    echo '<script type="text/javascript" src="'.$request->url_for(array('resource'=>'pages','action'=>'block.js')).'"></script>';

    ?>

<?php elseif (signed_in() && !(environment('use_sidebar_blocks'))) : ?>

<?php
$profile = get_profile();

?>

<?php if (isset($request->params['nickname'])) : ?>

<ul>
  <?php if (!empty($profile->fullname)) : ?>
    <li>Name: <?php echo $profile->fullname; ?></li>
  <?php endif; ?>
  <?php if (!empty($profile->locality)) : ?>
    <li>Location: <?php echo $profile->locality; ?></li>
  <?php endif; ?>
  <?php if (!empty($profile->homepage)) : ?>
    <li>Web: <?php echo $profile->homepage; ?></li>
  <?php endif; ?>
  <?php if (!empty($profile->bio)) : ?>
    <li>Bio: <?php echo $profile->bio; ?></li>
  <?php endif; ?>
  
</ul>

<?php endif; ?>

<?php

$count1 = 0;
$count2 = 0;
$count3 = 0;
global $db;
$sql = "SELECT count(*) as count FROM ".$db->prefix."subscriptions WHERE subscriber = ".$profile->id;
$result = $db->get_result($sql);
if ($result)
  $count1 = $db->result_value($result,0,"count");

$sql = "SELECT count(*) as count FROM ".$db->prefix."subscriptions WHERE subscribed = ".$profile->id;
$result = $db->get_result($sql);
if ($result)
  $count2 = $db->result_value($result,0,"count");

$sql = "SELECT count(*) as count FROM ".$db->prefix."posts WHERE profile_id = ".$profile->id;
$result = $db->get_result($sql);
if ($result)
  $count3 = $db->result_value($result,0,"count");

?>
<?php if (!isset($request->params['nickname'])) : ?>

<li><img width="32" height="32" style="vertical-align:middle;width:32px;height:32px;" src="<?php echo $profile->avatar; ?>" alt="<?php echo $profile->fullname; ?>"><a style="font-size:16px;margin-left:8px;" href="<?php echo $profile->profile_url; ?>"><?php echo $profile->nickname; ?></a></li>
<?php endif; ?>
<li>
  <table border="0" cellpadding="0" cellspacing="10">
    <tr>
      <td><h2><?php echo $count1; ?></h2></td>
      <td><h2><?php echo $count2; ?></h2></td>
<?php if (!isset($request->params['nickname'])) : ?>
      <td><h2><?php echo $count3; ?></h2></td>
    <?php endif; ?>
    </tr>
    <tr>
      <td>following</td>
      <td>followers</td>
<?php if (!isset($request->params['nickname'])) : ?>
      <td>updates</td>
    <?php endif; ?>
    </tr>
  </table>
</li>
<?php if (isset($request->params['nickname'])) : ?>
<li>
  <table border="0">
    <tr>
      <td>Updates</td>
      <td>&nbsp;&nbsp;</td>
      <td><h2><?php echo $count3; ?></h2></td>
    </tr>
  </table>
</li>
<?php endif; ?>
<li>
  <a href="">Home</a>
</li>
<li>
<?php

    $links = array();
    global $request;
    if (member_of('administrators'))
      $links['Admin'] = $request->url_for('admin');
    $links['Logout'] = $request->url_for('openid_logout');
    $links['Register'] = $request->url_for('register');
    $links['Login'] = $request->url_for('email_login');

?>
  <a href="<?php echo $request->url_for(array("resource"=>$profile->nickname))."/replies"; ?>"><?php echo "@".$profile->nickname; ?></a>
</li>
<li>Direct Messages</li>
<li>Favorites</li>
<li><form method="post"><input size="14" value="Search"></form></li>

<li>Trending Topics</li>
<li>Following</li>
<li><a class="rss" style="float:left;" href="<?php bloginfo( 'rss2_url' ); ?>">RSS</a></li>
<?php else : ?>
  
<?php 
if( !function_exists('dynamic_sidebar') || !dynamic_sidebar() ) { 

	echo prologue_widget_recent_comments_avatar(array('before_widget' => ' <li id="recent-comments" class="widget widget_recent_comments"> ', 'after_widget' => '</li>', 'before_title' =>'<h2>', 'after_title' => '</h2>'  ));

	$before = "<li><h2>Recent Projects</h2>\n";
	$after = "</li>\n";
	$num_to_show = 35;
	echo prologue_recent_projects( $num_to_show, $before, $after );
} // if dynamic_sidebar
?>
	</ul>
<div style="clear: both;"></div>

<?php endif; ?>

</div> <!-- // sidebar -->
