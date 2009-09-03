<?php

// do shortener redirect
// and set memcached 301

before_filter('do_shorten_redirect','find_by');
  
class Shortener extends Model {
  
  function Shortener() {
    
    $this->char_field( 'apikey' );
    $this->char_field( 'nickname' );
    $this->char_field( 'password' );
    $this->char_field( 'type' );
    $this->int_field( 'urlcount' );
    $this->int_field( 'hitcount' );
    $this->char_field( 'urlbase' );
    $this->char_field( 'endpoint' );
    
    $this->time_field( 'created' );
    $this->time_field( 'modified' );

    $this->int_field( 'profile_id' );
    
    $this->int_field( 'entry_id' );

    $this->auto_field( 'id' );
    
    // relationships
    
    $this->has_one( 'entry' );
    
    // permissions
    
    $this->let_access( 'all:administrators' );
    
  }
  
}


function get_code($seed_length=30) {
    $seed = "ABCDEFGHJKLMNPQRSTUVWXYZ234567892345678923456789";
    $str = '';
    srand((double)microtime()*1000000);
    for ($i=0;$i<$seed_length;$i++) {
        $str .= substr ($seed, rand() % 48, 1);
    }
    return strtolower($str);
}



function do_shorten() {




add_include_path(library_path().'urlshort/upload');
require_once 'includes/config.php'; // settings
require_once 'includes/gen.php'; // url generation and location
$perma = parse_url( $_SERVER['REQUEST_URI'] );
$_PERMA = explode( "/", $perma['path'] );
@array_shift( $_PERMA );
$shorturl = new shorturl();
$msg = '';
$strurl = '';

global $db,$request;
$Url =& $db->model('Url');



global $pretty_url_base;

$urlbase = $pretty_url_base;

global $prefix;

if (!empty($prefix)) {
  
  $sql = "SELECT urlbase FROM shorteners WHERE nickname LIKE '".$db->escape_string($request->username)."'";
  $sql .= " AND password LIKE '".$db->escape_string($request->password)."'";
  $result = $db->get_result( $sql );
  $url_base = $db->result_value( $result, 0, "urlbase" );
  if ( $db->num_rows($result) == 1 ) {
    $urlbase = 'http://'.$url_base;
  } else {
    trigger_error('sorry the username and password were incorrect', E_USER_ERROR);
  }
  
} else {
  $parts = split('\.',$urlbase);
  if (count($parts)>2) {
    $urlbase = 'http://'.$parts[1].'.'.$parts[2];
  }
}

if ( REWRITE ) {
  $urlbase = $urlbase.dirname($_SERVER['PHP_SELF']);
} else {
  $urlbase = 'http://'.$request->domain.$_SERVER['PHP_SELF'];
}



if ( isset($request->url) ) {
	$longurl = trim(mysql_escape_string($request->url));
	$plain = trim(mysql_escape_string($request->plain));
	$protocol_ok = false;
	if ( count($allowed_protocols) ) {
		foreach ( $allowed_protocols as $ap ) {
			if ( strtolower(substr($longurl, 0, strlen($ap))) == strtolower($ap) ) {
				$protocol_ok = true;
				break;
			}
		}
	} else {
		$protocol_ok = true;
	}
  $protocol_ok = true;
	$plaincheck = check_plain($plain);

  // url 	Required 	The destination URL to be shortened.

  // custom 	Optional 	A custom URL that is preferred to an auto-generated URL.
  // searchtags 	Optional 	A search string value to attach to a tr.im URL.
  // privacycode 	Optional 	A string value that must be appended after the URL.
  // newtrim 	Optional 	If present with any value, it will force the creation of a new tr.im URL.
  // sandbox 	Optional 	If present with any value a test data set will be returned, and no URL created. This is intended for testing so that you do not consume API calls or insert pointless data while in development.

  // api_key 	Optional 	An application API key assigned to your application.
  // username 	Optional 	A tr.im username that you would like to attach the URL to.
  // password 	Optional 	The password for the tr.im username referenced above.

  // if the id has been sent to this script

  if ( isset($request->custom) && strlen(trim($request->custom)) ){
    $shorten = trim(mysql_escape_string($request->custom));
    $string = "$shorten";
    list($string1,$string2) = explode("$install_path",$string); 
    $shortid = $string1.$string2;
    $q2 = 'SELECT url FROM `urls` WHERE `id` LIKE CONVERT(_utf8 \''.$shortid.'\' USING latin1)'; 
    $result2 = mysql_query($q2);
    while ($row = mysql_fetch_array($result2, MYSQL_ASSOC)) {
      printf($row["url"]);
      exit();
    }
    if ( mysql_num_rows( $result2 ) == $result2 ) {
      $longurl = mysql_result($result2, 1);
    }
    else{
      header('HTTP/1.1 500 Internal Server Error'); 
      exit;
    }
  }


  $make_new_url = true;
  $q2 = 'SELECT id FROM '.URL_TABLE.' WHERE (url="'.$longurl.'")';
  $result2 = mysql_query($q2);
  if ( mysql_num_rows( $result2 ))
    $make_new_url = false;

  if (isset($request->searchtags))
    $longurl .= $request->searchtags;

  if (isset($request->privacycode))
    $longurl .= $request->privacycode;
  
  if (isset($request->newtrim))
    $make_new_url = true;

  if ($protocol_ok && $plaincheck){
  
    if (isset($request->sandbox)) {
    
        
        $trimresponse = array(
          'trimpath'=>'w92s',
          'reference'=>'lsTZf8vHaslrrmskREhbRArpHh125c',
          'trimmed'=>'10/08/2009',
          'destination'=>"http://www.google.com/",
          'trim_path'=>'w92S',
          'domain'=>'google.com',
          'url'=>'http://tr.im/w92S',
          'visits'=>0,
          'status'=>array(
            'result'=>'OK',
            'code'=>'200',
            'message'=>'tr.im URL Added.'
            ),
          'date_time'=>'2009/08/10 05:46:13 -0400'
        );
      
      
      $time_of = time();
  
      $responsetype = $request->client_wants;

      $trimpath = $trimresponse['trimpath'];
      $reference = $trimresponse['reference'];
      $trimmed = $trimresponse['trimmed'];
      $destination = $trimresponse['destination'];
      $trim_path = $trimresponse['trim_path'];
      $domain = $trimresponse['domain'];
      $strurl = $trimresponse['url'];
      $visits = 0;
      $status_result = 'OK';
      $status_code = '200';
      $date_time = $trimresponse['date_time'];

    } else {
    
      $shorturl->add_url($longurl, $plain);
  
  		if ( REWRITE ) {
  			$strurl = $urlbase.''.$shorturl->get_id($longurl);
  		} else {
  			$strurl = $urlbase.'?id='.$shorturl->get_id($longurl);
  		}

      $time_of = time() - (3 * 60 * 60);
     
      $responsetype = $request->client_wants;
      $id = $shorturl->get_id($longurl);
      $trimpath = $id;
      $reference = get_code();
      $trimmed = date("d/m/Y",$time_of);
      $destination = $longurl;
      $trim_path = $id;


      $url_parts = @parse_url( $longurl );

      $domain = $url_parts["host"];
  
      $visits = 0;
      $status_result = 'OK';
      $status_code = '200';
  
      $date_time = date("Y/m/d H:i:s O",$time_of);
  
  
      $l = $Url->find_by(array('id'=>$id));
      
      if ($make_new_url) {
        
        $l->set_value('text',$plain);
        $l->set_value('title',$plain);
        $l->set_value('trimurl',$strurl);
        $l->set_value('created',date("Y-m-d H:i:s",$time_of));
        $l->set_value('date',date("Y-m-d H:i:s",$time_of));
        
        $l->set_value('trimpath',$trimpath);
        $l->set_value('trimref',$reference);
        $l->set_value('trimmed',$trimmed);
        $l->set_value('trimvisits',$visits);
        $l->set_value('trimtime',$date_time);
        
        $l->save_changes();
        
      } else {

        $reference = $l->reference;
        $trimpath = $l->trimpath;
        $reference = $l->trimref;
        $trimmed = $l->trimmed;
        $trimpath = $l->trimpath;
        $trim_path = $l->trimpath;
        $visits = $l->trimvisits;
        $date_time = $l->trimtime;
        
        $l->set_value('trimvisits',$l->trimvisits + 1);
        $l->save_changes();
        
      }
      
    
    }
    
    $arr = array('destination','url','trimmed');
    
    if ($responsetype == 'json')
      foreach( $arr as $var)
        $$var = str_replace('/', '\/', $$var);
    
    if (substr($longurl,-strlen($domain)) == $domain)
      $destination .= '\/';
    
    $callback1 = '';
    $callback2 = '';
    
    if (isset($request->callback)) {
      $callback1 = $request->callback.'(';
      $callback2 = ')';
    }
    
    if ($responsetype == 'json') {
      header( 'Content-Type: application/json' );
      header( "Content-Disposition: inline" );
    }



    if ($responsetype == 'xml') echo '<?xml version="1.0" encoding="UTF-8"?>
<trim>
  <status result="OK" code="200" message="tr.im URL Added."/>
  <url>'.$strurl.'</url>
  <reference>'.$reference.'</reference>
  <trimpath>'.$trimpath.'</trimpath>
</trim>';
    if ($responsetype == 'json') echo $callback1.'{"trimpath": "'.$trimpath.'", "reference": "'.$reference.'", "trimmed": "'.$trimmed.'", "destination": "'.$destination.'", "trim_path": "'.$trim_path.'", "domain": "'.$domain.'", "url": "'.$strurl.'", "visits": '.$visits.', "status": {"result": "'.$status_result.'", "code": "'.$status_code.'", "message": "tr.im URL Added."}, "date_time": "'.$date_time.'"}'.$callback2;
  }
  
  $redircode = '<html>

  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title></title>
    <META HTTP-EQUIV="Refresh" CONTENT="0;URL='.stripslashes($destination).'">
    <meta name="robots" content="noindex"/>
    <link rel="canonical" href="'.stripslashes($destination).'"/>
  </head>

  <body>

  </body>
  
</html>';
  $make_s3 = false;
  if ($url_base && $make_s3) {
    $redirfile = tempnam( "/tmp", $url_base.'/'.$trimpath );
    $handle = fopen($redirfile, "w");
    fwrite($handle, $redircode);
    fclose($handle);
    lib_include( 'S3' );
    $s3 = new S3( environment('awsAccessKey'), environment('awsSecretKey') );
    if ($s3) {
      $s3->getBucket($url_base);
      $s3->putObjectFile( $redirfile , $url_base, $trimpath, 'public-read' );
    }
  }
  
}

exit;

}

function do_shorten_redirect(&$model,&$model) {
  global $request;
  if (!($request->resource == 'settings'))
    return;
  $perma = parse_url( $_SERVER['REQUEST_URI'] );
  $_PERMA = explode( "/", $perma['path'] );
  @array_shift( $_PERMA );
  if ( isset($_PERMA[0]) )
  	$id = mysql_escape_string($_PERMA[0]);
  else
  	$id = '';
  if ( $id != '' && $id != basename($_SERVER['PHP_SELF']) ){
    add_include_path(library_path().'urlshort/upload');
    require_once 'includes/config.php'; // settings
    require_once 'includes/gen.php'; // url generation and location
    $url = new shorturl();
  	$location = $url->get_url($id);
  	if ( $location != -1 )	{
  	  include 'db/library/pca/pca.class.php';
      $cache = PCA::get_best_backend();
      $timeout = 86400;
      $cache->add($_SERVER['REQUEST_URI'], $location, $timeout);
  		header('Location: '.$location, TRUE, 301);
  		exit;
  	}
  }
}
