<div id="sidebar">
<ul>

<?php include 'wp-content/language/lang_chooser.php'; //Loads the language-file ?>


<?php global $request; ?>

<?php if (get_app_id() && !(environment('use_sidebar_blocks'))) : ?>

  <?php

  $profile = get_profile(get_app_id());

  if ($profile->id == get_profile_id()) {
   if (in_array('settings',$request->activeroute->patterns))
     render_partial('admin');
  }
  
  //  echo '<script type="text/javascript" src="'.$request->url_for(array('resource'=>'pages','action'=>'block.js')).'"></script>';   }

  ?>

  <?php

  ?>

  <?php if (isset($request->params['nickname'])) : ?>

  <ul>
    <?php if (!empty($profile->fullname)) : ?>
      <li><?php echo $txt['sidebar_name']; ?><?php echo $profile->fullname; ?></li>
    <?php endif; ?>
    <?php if (!empty($profile->locality)) : ?>
      <li><?php echo $txt['sidebar_location']; ?><?php echo $profile->locality; ?></li>
    <?php endif; ?>
    <?php if (!empty($profile->homepage)) : ?>
      <li><?php echo $txt['sidebar_web']; ?><?php echo $profile->homepage; ?></li>
    <?php endif; ?>
    <?php if (!empty($profile->bio)) : ?>
      <li><?php echo $txt['sidebar_bio']; ?><?php echo $profile->bio; ?></li>
    <?php endif; ?>
  
  </ul>

  <?php endif; ?>

  <?php

  $count1 = 0;
  $count2 = 0;
  $count3 = 0;
  global $db;
  
  if ($db->table_exists('subscriptions') && $db->table_exists('posts')) {
    
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
    
  }

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
        <td><a href="<?php echo $request->url_for(array("resource"=>$profile->nickname))."/subscriptions"; ?>"><?php echo $txt['sidebar_following']; ?></a></td>
        <td><a href="<?php echo $request->url_for(array("resource"=>$profile->nickname))."/subscribers"; ?>"><?php echo $txt['sidebar_followers']; ?></a></td>
  <?php if (!isset($request->params['nickname'])) : ?>
        <td><?php echo $txt['sidebar_updates']; ?></td>
      <?php endif; ?>
      </tr>
    </table>
  </li>
  <?php if (isset($request->params['nickname'])) : ?>
    <li>
      <table border="0">
        <tr>
          <td><?php echo $txt['sidebar_Updates']; ?></td>
          <td>&nbsp;&nbsp;</td>
          <td><h2><?php echo $count3; ?></h2></td>
        </tr>
      </table>
    </li>
  <?php endif; ?>

  <?php

      $links = array();
      global $request;
      if (member_of('administrators'))
        $links['Admin'] = $request->url_for('admin');
      $links['Logout'] = $request->url_for('openid_logout');
      $links['Register'] = $request->url_for('register');
      $links['Login'] = $request->url_for('email_login');

  ?>
    
  <?php if (!isset($request->params['nickname'])) : ?>
  <li>
    <a href="<?php base_url(); ?>"><?php echo $txt['sidebar_home']; ?></a>
  </li>
  <li>
    <a href="<?php echo $request->url_for(array("resource"=>$profile->nickname))."/replies"; ?>"><?php echo "@".$profile->nickname; ?></a>
  </li>
  <?php endif; ?>
  <?php if (!signed_in()) : ?>
  <li><?php echo $txt['sidebar_favorities']; ?></li>
  <?php elseif (isset($request->params['nickname'])) : ?>
  <li><?php echo $txt['sidebar_favorities']; ?></li>
  <li><?php echo $txt['sidebar_following']; ?></li>
<?php else : ?>
    <li><?php echo $txt['sidebar_direct_messages']; ?></li>
  <li><?php echo $txt['sidebar_favorites']; ?></li>
  <li><form method="post"><input size="14" value="<?php echo $txt['sidebar_search']; ?>"></form></li>

  <li><?php echo $txt['sidebar_trending_topics']; ?></li>
  <li><?php echo $txt['sidebar_following']; ?></li>

  <?php endif; ?>
  <li><a class="rss" style="float:left;" href="<?php bloginfo( 'rss2_url' ); ?>"><?php echo $txt['sidebar_rss']; ?></a></li>



<?php else : ?>
  
<?php 
if( !function_exists('dynamic_sidebar') || !dynamic_sidebar() ) { 

	echo prologue_widget_recent_comments_avatar(array('before_widget' => ' <li id="recent-comments" class="widget widget_recent_comments"> ', 'after_widget' => '</li>', 'before_title' =>'<h2>', 'after_title' => '</h2>'  ));

	$before = "<li><h2>".$txt['recent_projects']."</h2>\n";
	$after = "</li>\n";
	$num_to_show = 35;
	echo prologue_recent_projects( $num_to_show, $before, $after );
} // if dynamic_sidebar
?>
	</ul>
<div style="clear: both;"></div>

<?php endif; ?>

<?php if (!isset($profile)) { ?>
<ul>
<li style="font-weight:bold;"><?php echo $txt['sidebar_greeting_headline']; ?><?php bloginfo( 'name' ); ?>!</li>

<li style="font-weight:normal; font-size:1.0em; font-style:italic"><?php bloginfo( 'name' ); ?><?php echo $txt['sidebar_greeting_text']; ?></li>
<li style="font-weight:normal; font-size:1.0em;"><a href="<?php url_for(array('resource'=>'email_login')); ?>" title="Sign in"><?php echo $txt['sidebar_sign_in_register']; ?></a></li>
</ul>
<?php } ?>
</div> <!-- // sidebar -->
