<?php

function _doctype( &$vars ) {
  // doctype controller
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

function put( &$vars ) {
  extract( $vars );
  $resource->update_from_post( $request );
  header_status( '200 OK' );
  redirect_to( $request->resource );
}

function delete( &$vars ) {
  extract( $vars );
  $resource->delete_from_post( $request );
  header_status( '200 OK' );
  redirect_to($request->url_for(array(
        'resource'=>'identities',
        'id'=>$profile->id,
        'action'=>'edit' )));
}


function _remove( &$vars ) {
  extract( $vars );
  $Member = $collection->MoveFirst();
  $Entry = $Member->FirstChild( 'entries' );
  return vars(
    array( &$Member, &$Entry, &$profile ),
    get_defined_vars()
  );
}



function _followers( &$vars ) {
  extract($vars);
  global $request;
  global $response;
  
  $pagevar = "followerspage";
  
  if (isset($request->params[$pagevar]))
    $page = $request->params[$pagevar];
  else
    $page = 1;
  
  $mapper = array(
    'nickname'=>$request->params['nickname'],
  );
  
  $where = array(
    'subscribed'=>$request->params['byid']
  );
  
  $Subscription->set_param( 'find_by', $where );
  
  $request->set_param('page',$page);
  
  $Subscription->set_limit(10);

  $response->collection = new Collection('subscriptions');
  if (count($response->collection->members) >= $response->collection->per_page ) {
    $mapper[$pagevar] = ($page + 1);
    $older = '<a href="'.$request->url_for( $mapper );
    $older .= '">&lt; older</a>';
  }
  if ($page > 1) {
    $mapper[$pagevar] = ($page - 1);
    $newer = "&nbsp;&nbsp;&nbsp;";
    $newer .= '<a href="'.$request->url_for( $mapper );
    $newer .= '">newer &gt;</a>';
  }
  $Identity =& $db->model('Identity');
  return vars(
    array( &$newer, &$older, &$collection, &$Identity ),
    get_defined_vars()
  );
}

function _following( &$vars ) {
  extract($vars);
  global $request;
  global $response;
  
  $pagevar = "followingpage";
  
  if (isset($request->params[$pagevar]))
    $page = $request->params[$pagevar];
  else
    $page = 1;
  
  $mapper = array(
    'nickname'=>$request->params['nickname'],
  );
  
  $where = array(
    'subscriber'=>$request->params['byid']
  );
  
  $Subscription->set_param( 'find_by', $where );
  
  $request->set_param('page',$page);
  
  $Subscription->set_limit(10);
  
  $response->collection = new Collection('subscriptions');
  if (count($response->collection->members) >= $response->collection->per_page ) {
    $mapper[$pagevar] = ($page + 1);
    $older = '<a href="'.$request->url_for( $mapper );
    $older .= '">&lt; older</a>';
  }
  if ($page > 1) {
    $mapper[$pagevar] = ($page - 1);
    $newer = "&nbsp;&nbsp;&nbsp;";
    $newer .= '<a href="'.$request->url_for( $mapper );
    $newer .= '">newer &gt;</a>';
  }
  $Identity =& $db->model('Identity');
  return vars(
    array( &$newer, &$older, &$collection, &$Identity ),
    get_defined_vars()
  );
}




