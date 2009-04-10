<?php 



class Method extends Model {
  
  function Method() {
    
    $this->auto_field( 'id' );
    
    $this->int_field( 'entry_id' );
    $this->int_field( 'oauth' );
    $this->int_field( 'http' );
    $this->int_field( 'omb' );
    
    $this->bool_field( 'enabled' );
    
    $this->char_field( 'function' );
    $this->char_field( 'route' );
    $this->char_field( 'resource' );
    $this->char_field( 'permission' );
    
    $this->text_field( 'code' );
    
    $this->has_one( 'entry' );
    
    $this->let_access( 'all:administrators' );
    
  }
  
  function init() {
    
    $m = $this->base();
    $m->set_value( 'code', '
    
// get some variables in scope
extract( $vars );
$tweets = array();

$callback = $_GET[\'callback\'];

// get the data model for the "posts" table
$Post =& $db->model( \'Post\' );

// search for the most recent 10 records
$Post->find();

// loop over each record
while ( $p = $Post->MoveNext() ) {

  $profile = owner_of( $p );

  $tweet = array();

  $user = array(
     \'screen_name\' => $profile->nickname,
     \'profile_background_image_url\' => $profile->avatar,
     \'url\' => $profile->profile_url
  );

  $tweet[\'text\'] = $p->title;
  $tweet[\'truncated\'] = \'false\';
  $tweet[\'created_at\'] = date( "D M d G:i:s O Y", strtotime( $p->created ));
  $tweet[\'in_reply_to_status_id\'] = null;
  $tweet[\'source\'] = null;
  $tweet[\'id\'] = $p->uri;
  $tweet[\'favorited\'] =\'false\';
  $tweet[\'user\'] = $user;

  $tweets[] = $tweet;

}

echo $callback."(";

if (!(class_exists(\'Services_JSON\')))
  lib_include(\'json\');
$json = new Services_JSON();

// create the JSON data
echo $json->encode( $tweets );

echo ");";

');
  
    $m->set_value( 'function', 'api_statuses_public_timeline' );
    $m->set_value( 'route', 'api/statuses/public_timeline.json' );
    $m->set_value( 'resource', 'posts' );
    $m->set_value( 'permission', 'read' );
    $m->set_value( 'enabled', true );
    $m->set_value( 'omb', 1 );
    $m->set_value( 'oauth', 1 );
    $m->set_value( 'http', 1 );

    $m->save_changes();
    $m->set_etag(1);
  

    $m = $this->base();
    $m->set_value( 'code', '
    
extract( $vars );

$request->set_param( array( \'post\', \'title\' ), $request->status );

$resource->insert_from_post( $request );

header( \'Status: 200 OK\' );

');
  
    $m->set_value( 'function', 'api_statuses_update_json' );
    $m->set_value( 'route', 'api/statuses/update.json' );
    $m->set_value( 'resource', 'posts' );
    $m->set_value( 'permission', 'write' );
    $m->set_value( 'enabled', true );
    $m->set_value( 'omb', 1 );
    $m->set_value( 'oauth', 1 );
    $m->set_value( 'http', 1 );

    $m->save_changes();
    $m->set_etag(1);
  
  }

}

