<div id="sidebar">


<?php include 'wp-content/language/lang_chooser.php'; //Loads the language-file ?>


<?php global $request; ?>

<?php if (get_app_id()) : ?>

  <?php

  $profile = get_profile(get_app_id());

  if ($profile->id == get_profile_id()) {
   if (in_array('settings',$request->activeroute->patterns))
     render_partial('admin');
  }
  
  //  echo '<script type="text/javascript" src="'.$request->url_for(array('resource'=>'pages','action'=>'block.js')).'"></script>';   }

  ?>

  <?php if (!in_array('settings',$request->activeroute->patterns)) { ?>

  <?php if (isset($request->params['nickname'])) : ?>

    <?php if (!empty($profile->fullname)) : ?>
      <p class="liname"><?php echo $txt['sidebar_name']; ?><?php echo $profile->fullname; ?></p>
    <?php endif; ?>

    <?php if (!empty($profile->locality)) : ?>
    <p class="liother">
    <?php echo $txt['sidebar_location']; ?><?php echo $profile->locality; ?>
    </p>
    <?php endif; ?>

    <?php if (!empty($profile->country_name)) : ?>
    <p class="liother">
    <?php echo $txt['sidebar_country']; ?><?php echo $profile->country_name; ?>
    </p>
    <?php endif; ?>
    
    <?php if (!empty($profile->homepage)) : ?>
    <p class="liother">
    <?php echo $txt['sidebar_web']; ?><br /><a href='<?php echo $profile->homepage; ?>'><?php echo $profile->homepage; ?></a>
    </p>
    <?php endif; ?>

    <?php if (!empty($profile->bio)) : ?>
    <p class="liother">
    <?php echo $txt['sidebar_bio']; ?><span class="bio"><?php echo $profile->bio; ?></span>
    </p>
    <br />
  <?php endif; ?>

  <?php endif; ?>
  <?php } ?>
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
  <?php if (!in_array('settings',$request->activeroute->patterns)) { ?>
  <?php if (!isset($request->params['nickname'])) : ?>

    <img width="32" height="32" class="profile" src="<?php echo $profile->avatar; ?>" alt="<?php echo $profile->fullname; ?>" /><a class="profile-nick" href="<?php echo $profile->profile_url; ?>"><?php echo $profile->nickname; ?></a>
    <br /><br />
  <?php endif; ?>
  
  <div id="sidebar-posts-stats">

        <div id="sidebar-subscribers">
        <span class="sidebar_subscriptions_count"><?php echo $count1; ?></span>
        <br />
        <span class="sidebar_subscriptions"><a href="<?php echo $request->url_for(array("resource"=>$profile->nickname))."/subscriptions"; ?>"><?php echo $txt['sidebar_following']; ?></a></span>
        </div>
  
        <div id="sidebar-subscriptions">
        <span class="sidebar_subscribers_count"><?php echo $count2; ?></span><br />
        <span class="sidebar_subscribers"><a href="<?php echo $request->url_for(array("resource"=>$profile->nickname))."/subscribers"; ?>"><?php echo $txt['sidebar_followers']; ?></a></span>
        </div>

  <?php if (!isset($request->params['nickname'])) : ?>
        <div id="sidebar-updates">
        <span class="sidebar_updates_count"><?php echo $count3; ?></span><br />
  <?php endif; ?>
  <?php if (!isset($request->params['nickname'])) : ?>
        <span  class="sidebar_updates"><a href="<?php echo $request->url_for(array("resource"=>$profile->nickname)); ?>"><?php echo $txt['sidebar_updates']; ?></a></span>
        </div>
        <br /><br /><br />
  <?php endif; ?>

  </div>
    
  <?php if (isset($request->params['nickname'])) : ?>
    <br /><br />
      <p class="sidebar_updates_nickname"><span class="profile-updates"><?php echo $count3; ?></span><?php echo $txt['sidebar_Updates']; ?></p>
    
  <?php endif; ?>
<?php } ?>
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

    <p class="liother">
    <a href="<?php base_url(); ?>"><?php echo $txt['sidebar_home']; ?></a>
    </p>

    <p class="liother">
    <a href="<?php echo $request->url_for(array("resource"=>$profile->nickname))."/replies"; ?>"><?php echo "@".$profile->nickname; ?></a>
    </p>
  
  <?php endif; ?>
  <?php if (!in_array('settings',$request->activeroute->patterns)) { ?>
  <?php if (!signed_in()) : ?>
    <p class="liother">
    <?php echo $txt['sidebar_favorities']; ?>
    </p>
  <?php elseif (isset($request->params['nickname'])) : ?>
    <p class="liother">
    <?php echo $txt['sidebar_favorities']; ?>
    </p>
    <p class="liother">
    <?php echo $txt['sidebar_following']; ?>
    </p>
      <div id="followgrid">
          <?php followgrid(); ?>
      </div>


  <?php else : ?>
    <p class="liother">
    <?php echo $txt['sidebar_direct_messages']; ?>
    </p>
    <p class="liother">
    <?php echo $txt['sidebar_favorites']; ?>
    </p>
    <form class="liother" method="post" action=""><input size="14" value="<?php echo $txt['sidebar_search']; ?>" /></form>
    <p class="liother">
    <?php echo $txt['sidebar_trending_topics']; ?>
    </p>
    <p class="liother">
    <?php echo $txt['sidebar_following']; ?>
    </p>
      <div id="followgrid">
          <?php followgrid(); ?>
      </div>
  <?php endif; ?>
  <?php } ?>

<?php endif; ?>

<?php if (signed_in() && environment('categories') && !isset($request->params['byid']) && !in_array('settings',$request->activeroute->patterns)) : ?>
  
<?php 
if( !function_exists('dynamic_sidebar') || !dynamic_sidebar() ) { 

	echo prologue_widget_recent_comments_avatar(array('before_widget' => ' <li id="recent-comments" class="widget widget_recent_comments"> ', 'after_widget' => '', 'before_title' =>'<h2>', 'after_title' => '</h2>'  ));

	$before = "<h2>".$txt['recent_projects']."</h2>\n";
	$after = "\n";
	$num_to_show = 35;
	echo prologue_recent_projects( $num_to_show, $before, $after );
} // if dynamic_sidebar
?>

<?php if (!in_array('settings',$request->activeroute->patterns)) { ?>
  <p class="liother">
  <a class="rss" href="<?php bloginfo( 'rss2_url' ); ?>"><?php echo $txt['sidebar_rss']; ?></a>
  </p>
<?php } ?>


<div id="sidebarclear"></div>

<?php endif; ?>

<?php if (!isset($profile)) { ?>

<p class="greeting"><?php echo $txt['sidebar_greeting_headline']; ?><?php bloginfo( 'name' ); ?>!</p>

<p class="greeting-italic"><?php bloginfo( 'name' ); ?><?php echo $txt['sidebar_greeting_text']; ?></p>
<p class="greeting-normal"><a href="<?php url_for(array('resource'=>'email_login')); ?>" title="Sign in"><?php echo $txt['sidebar_sign_in_register']; ?></a></p>

<?php } ?>
</div> <!-- // sidebar -->
