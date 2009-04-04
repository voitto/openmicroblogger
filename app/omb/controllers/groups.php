<?php

function get( &$vars ) {
  extract( $vars );

  switch ( count( $collection->members )) {
    case ( 1 ) :
      if ($request->id && $request->entry_url())
        render( 'action', 'entry' );
    default :
      render( 'action', 'index' );
  }
}

function put( &$vars ) {
  
  extract( $vars );
  
  $Group->update_from_post( $request );
  
  $subscribers = explode( "\n", $request->subscribers );
  
  $g = $Group->find($request->id);
  
  if ($g && count($subscribers) > 0)
    $result = $db->get_result( "DELETE FROM ".$db->prefix."memberships WHERE group_id = ".$g->id );
  
  foreach ( $subscribers as $addr ) {
    $p = false;
    $i = false;
    $a = trim( $addr );
    $i = $Identity->find_by( 'email_value', $a );
    if (is_email($a) && $i) {
      $p = $i->FirstChild( 'people' );
    } elseif (is_email($a)) {
      $p = $Person->base();
      $p->save();
      $i = $Identity->base();
      $i->set_value( 'url', $a );
      $i->set_value( 'email_value', $a );
      $i->set_value( 'given_name', '' );
      $i->set_value( 'label', 'profile 1' );
      $token = make_token($p->id);
      $i->set_value( 'token', $token);
      $i->set_value( 'person_id', $p->id );
      $i->save_changes();
      $i->set_etag($p->id);
      do_invite_email($a,$token);
    }
    if ($g && is_email($a) && $p) {
      $m = $Membership->base();
      $m->set_value( 'group_id', $g->id );
      $m->set_value( 'person_id', $p->id );
      $m->save_changes();
    }
  }
  
  header( 'Status: 200 OK' );
  redirect_to( 'groups' );
  
}


  
function do_invite_email($addr,$token, &$group) {
  
  global $request;
  
  $link = $request->url_for(array('ident'=>$token));
  
  $subject = 'You were added to a group on '.$request->base;
  
  $email = "Hi, you have been invited to join the ".$group->name." group on ".$request->base.".\n\n";
  
  $email .= "Click here to check it out --> $link \n\n";

  
  $html = false;
  
  send_email( $addr, $subject, $email, environment('email_from'), environment('email_name'), $html );
  
}


function post( &$vars ) {
  
  
  extract( $vars );
  
  $g = $Group->base();
  
  $fields = $Group->fields_from_request( $request );
  
  foreach ( $fields['groups'] as $field=>$type )
    $g->set_value( $field, $request->params['group'][$field] );
  
  $g->save_changes();

  $g->set_etag(get_person_id());
  
  $subscribers = explode( "\n", $request->subscribers );
  
  foreach ( $subscribers as $addr ) {
    $p = false;
    $i = false;
    $a = trim( $addr );
    $i = $Identity->find_by( 'email_value', $a );
    if (is_email($a) && $i) {
      $p = $i->FirstChild( 'people' );
    } elseif (is_email($a)) {
      $p = $Person->base();
      $p->save();
      $i = $Identity->base();
      $i->set_value( 'url', $a );
      $i->set_value( 'email_value', $a );
      $i->set_value( 'given_name', '' );
      $i->set_value( 'label', 'profile 1' );
      $token = make_token($p->id);
      $i->set_value( 'token', $token);
      $i->set_value( 'person_id', $p->id );
      $i->save_changes();
      $i->set_etag($p->id);
      do_invite_email($a,$token,$g);
    }
    if (is_email($a) && $p) {
      $m = $Membership->base();
      $m->set_value( 'group_id', $g->id );
      $m->set_value( 'person_id', $p->id );
      $m->save_changes();
    }
  }
  
  header( 'Status: 201 Created' );
  
  redirect_to( 'groups' );
  
}

function delete( &$vars ) {
  extract( $vars );
  $e = $Entry->find_by('etag',$request->etag);
  if ($e) {
    $g = $Group->find($e->record_id);
    if ($g)
      $result = $db->get_result( "DELETE FROM ".$db->prefix."memberships WHERE group_id = ".$g->id );
  }
  $Group->delete_from_post( $request );
  header( 'Status: 200 OK' );
  redirect_to( 'groups' );
}

function index( &$vars ) {
  extract( $vars );
  $theme = environment('theme');
  $blocks = environment('blocks');
  $atomfeed = $request->feed_url();
  return vars(
    array(
      &$blocks,
      &$profile,
      &$collection,
      &$atomfeed,
      &$theme
    ),
    get_defined_vars()
  );
}




function _index( &$vars ) {

  extract( $vars );
  $Group->find();
  return vars(
    array(

      &$collection
    ),
    get_defined_vars()
  );

}


function _entry( &$vars ) {

  // bring controller vars into scope
  extract( $vars );

  $Member = $Group->find( $request->id );
  
  $Member->set_etag();

  if (!$Member)
    trigger_error( "Sorry, I could not find that entry in groups.", E_USER_ERROR );

  $Membership = $Member->FirstChild( "memberships" );
  
  $Entry = $Member->FirstChild( "entries" );
  
  
  
  
  return vars(
    array(
      
      // return vars to the _entry partial
      &$Member,
      &$Membership,
      &$Entry

    ),
    get_defined_vars()
  );

}




function _remove( &$vars ) {

  // bring controller vars into scope
  extract( $vars );

  $Member = $Group->find( $request->id );
  
  $Member->set_etag();

  if (!$Member)
    trigger_error( "Sorry, I could not find that entry in groups.", E_USER_ERROR );

  $Membership = $Member->FirstChild( "memberships" );
  
  $Entry = $Member->FirstChild( "entries" );

  return vars(
    array(

      // return vars to the _entry partial
      &$Member,
      &$Membership,
      &$Entry

    ),
    get_defined_vars()
  );

}

function _edit( &$vars ) {

  // bring controller vars into scope
  extract( $vars );

  if ( $request->error )
    $Member = session_restore( $db->models['groups'] );
  else
    $Member = $Group->find( $request->id );

  $Entry = $Member->FirstChild( "entries" );
  
  if (empty($Entry->etag)) {
    $Member->set_etag();
    $Member = $Group->find( $request->id );
    $Entry = $Member->FirstChild( "entries" );
  }
  
  $subscribers = "";
  $arr = resource_group_members($Member->id);
  $avatars = array();
  foreach ( $arr as $member_ident ) {
    $subscribers .= htmlentities($member_ident->email_value)."\n";
    $avatars[$member_ident->id] = $member_ident->avatar;
  }
  
  // custom controller adds $group_perms named variable
  
  $group_perms = array();
  
  foreach ( $db->models as $model ) {
    foreach( $model->access_list as $permission=>$values ) {
      foreach($values as $fieldname=>$listofgroups) {
        if (in_array($Member->name,$listofgroups)) {
          if (!(is_array($group_perms[$model->table])))
            $group_perms[$model->table] = array();
          if (empty($model->table))
            admin_alert( "table name not set for object ".get_class($model) );
          $group_perms[$model->table][$permission] = $values;
          break;
        }
      }
    }
  }
  
  return vars(
    array(
      
      // return vars to the _edit partial
      &$avatars,
      &$Member,
      &$Entry,
      &$group_perms,   // << I added this one to the standard list
      &$subscribers

    ),
    get_defined_vars()
  );

}



function _new( &$vars ) {

  // bring controller vars into scope
  extract( $vars );


  $Member = $Group->base();


  return vars(
    array(

      // return vars to the _new partial
      &$Member
      
    ),
    get_defined_vars()
  );

}

