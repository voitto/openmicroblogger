<?php

global $prefix;

 function wiki_init() {
   include 'wp-content/language/lang_chooser.php'; //Loads the language-file
   // app_register_init( table, action, apptitle, appname, number )
   global $prefix;
   if (empty($prefix)){
     app_register_init( 'wikis', 'index', 'Wiki', 'wiki', 2 );
	
}else{
      app_register_init( 'wiki_pages', 'index', 'Wiki Pages', 'wiki', 2 );
}
 }

 function wiki_show() {
 }

 function wiki_head() {
 }

 function wiki_menu() {
 }

 function wiki_post() {
 }

after_filter( 'set_up_new_wiki', 'insert_from_post' );

function set_up_new_wiki( &$model, &$rec ) {
  global $request,$db;
  if (!($request->resource == 'blogs'))
    return;
  global $prefix;
  if (!empty($prefix))
    return;
  if (!isset($_POST['wiki_title']))
    return;
  $url = blog_url($rec->nickname,true);
  require_once(ABSPATH.WPINC.'/class-snoopy.php');
  $snoop = new Snoopy;
  $snoop->agent = 'rp.ly http://rp.ly';
  $snoop->submit($url);
  if (!(strpos($snoop->response_code, '200')))
    trigger_error('could not configure the new wiki', E_USER_ERROR);
  if (!(signed_in()))
   trigger_error('sorry, you must be signed in to do that', E_USER_ERROR);
  $profile = get_profile();
  $Wiki =& $db->model('Wiki');
  $s = $Wiki->base();
  $s->set_value('profile_id',$profile->id);
  $s->set_value('nickname',$rec->nickname);
  $s->set_value('blog_id',$rec->id);
  $s->set_value('prefix',$rec->prefix);
  $s->set_value('title',$_POST['wiki_title']);
  $s->save_changes();
  $s->set_etag();
  //
  global $prefix;
  $prefix = $rec->prefix."_";
  $db->prefix = $prefix;
  $Person =& $db->model('Person');
  $Person->save();
  $Identity =& $db->model('Identity');
  $Identity->save();
	$i = make_identity(array(
    $profile->nickname,
    $profile->avatar,
    $profile->fullname,
    $profile->bio,
    $profile->homepage,
    $profile->locality
  ),true);

	$Setting =& $db->model('Setting');
	$tp = $Setting->base();
	$tp->set_value('name','config.env.theme');
	$tp->set_value('value','p2');
	$tp->save_changes();

  $person_id = 1;
  set_cookie($person_id);
  $_SESSION['openid_complete'] = true;

  redirect_to($request->url_for( array( 'resource'=>str_replace(' ','',$s->title) )));

}