<?php

  /** 
   * dbscript -- restful openid framework
   * @version 0.6.0 -- 22-October-2008
   * @author Brian Hendrickson <brian@dbscript.net>
   * @link http://dbscript.net/
   * @copyright Copyright 2009 Brian Hendrickson
   * @license http://www.opensource.org/licenses/mit-license.php MIT License
   * @package dbscript
   */


  /**
   * render_static
   * 
   * filter to intercept calls to static views
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param object $req
   * @param object $route
   */

function render_static( &$req, &$route ) {
  
  trigger_before( 'render_static', $req, $route );
  
  if (!(session_started()))
    session_start();
  ob_start();
  
  /*
   * List of known content types based on file extension.
   * Note: These must be built-in somewhere...
   */
  
  $known_content_types = array(
      "htm"  => "text/html",
      "html" => "text/html",
      "js"   => "text/javascript",
      "css"  => "text/css",
      "xml"  => "text/xml",
      "gif"  => "image/gif",
      "jpg"  => "image/jpeg",
      "jpeg" => "image/jpeg",
      "png"  => "image/png",
      "txt"  => "text/plain"
  );
  
  /*
   * Get the path of the target file.
   */
   
  if ( in_array( 'static', $req->params, true ) ) {
    
    $resource = "";
    $slash = "";
    
    for ($i=4; $i<count($req->params); $i++) {
      $resource .= $slash . $req->params[$i];
      $slash = "/";
    }
    
    /*
     * Verify the existence of the target file.
     * Return HTTP 404 if needed.
     */
    
    if (($src_uri = realpath( $resource )) === false) {
        /* The file does not exist */
        header( "HTTP/1.1 404 Not Found" );
        echo( "<html><body><h1>HTTP 404 - Not Found</h1></body></html>" );
        exit;
    }
    
    /*
     * Verify the requested file is under the doc root for security reasons.
     */
    
    $doc_root = realpath( "." );
    
    if (strpos($src_uri, $doc_root) !== 0) {
        header( "HTTP/1.1 403 Forbidden" );
        echo( "<html><body><h1>HTTP 403 - Forbidden</h1></body></html>" );
        exit;
    }
    
    /*
     * Set the HTTP response headers that will
     * tell the client to cache the resource.
     */
    
    $file_last_modified = filemtime( $src_uri );
    header( "Last-Modified: " . date( "r", $file_last_modified ) );
    
    $max_age = 300 * 24 * 60 * 60; // 300 days
    
    $expires = $file_last_modified + $max_age;
    header( "Expires: " . date( "r", $expires ) );
    
    $etag = dechex( $file_last_modified );
    header( "ETag: " . $etag );
    
    $cache_control = "must-revalidate, proxy-revalidate, max-age=" . $max_age . ", s-maxage=" . $max_age;
    header( "Cache-Control: " . $cache_control );
    
    /*
     * Check if the client should use the cached version.
     * Return HTTP 304 if needed.
     */
 
    if ( isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
            && $file_last_modified <= strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) ) {
        //error_log('using cached static file');
        header( "HTTP/1.1 304 Not Modified" );
        exit;
    }
    if ( isset($_SERVER['HTTP_IF_NONE_MATCH'])
            && stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) === $etag ) {
        //error_log('using cached static file');
        header( "HTTP/1.1 304 Not Modified" );
        exit;
    }
    
    /*
     * Extract the directory, file name and file
     * extension from the "uri" parameter.
     */
    
    $uri_dir = "";
    $file_name = "";
    $content_type = "";
    
    $uri_parts = explode( "/", $src_uri );
    
    for ( $i=0 ; $i<count( $uri_parts ) - 1 ; $i++ )
        $uri_dir .= $uri_parts[$i] . "/";
    
    $file_name = end( $uri_parts );
    
    $file_parts = explode( ".", $file_name );
    if ( count( $file_parts ) > 1 ) {
        $file_extension = end( $file_parts );
        $content_type = $known_content_types[$file_extension];
    }
    
    /*
     * Get the target file.
     * If the browser accepts gzip encoding, the target file
     * will be the gzipped version of the requested file.
     */
    
    $dst_uri = $src_uri;
    
    $compress = true;
    
    /*
     * Let's compress only text files...
     */
    
    $compress = $compress && ( strpos( $content_type, "text" ) !== false );
    
    /*
     * Finally, see if the client sent us the correct Accept-encoding: header value...
     */
    
    $compress = $compress && ( strpos( $_SERVER["HTTP_ACCEPT_ENCODING"], "gzip" ) !== false );
    
    if ( $compress ) {
        $gz_uri = "tmp/gzip/" . $src_uri . ".gz";
        
        if ( file_exists( $gz_uri ) ) {
            $src_last_modified = filemtime( $src_uri );
            $dst_last_modified = filemtime( $gz_uri );
            // The gzip version of the file exists, but it is older
            // than the source file. We need to recreate it...
            if ( $src_last_modified > $dst_last_modified )
                unlink( $gz_uri );
        }
        
        if ( !file_exists( $gz_uri ) ) {
            if ( !file_exists( "tmp/gzip/" . $uri_dir ) )
                mkdir_r( "tmp/gzip/" . $uri_dir );
            $error = false;
            if ( $fp_out = gzopen( $gz_uri, "wb" ) ) {
                if ( $fp_in = fopen( $src_uri, "rb" ) ) {
                    while( !feof( $fp_in ) )
                        gzwrite( $fp_out, fread( $fp_in, 1024*512 ) );
                    fclose( $fp_in );
                } else {
                    $error = true;
                }
                gzclose( $fp_out );
            } else {
                $error = true;
            }
            
            if ( !$error ) {
                $dst_uri = $gz_uri;
                header( "Content-Encoding: gzip" );
            }
        } else {
            $dst_uri = $gz_uri;
            header( "Content-Encoding: gzip" );
        }
    }
    
    /*
     * Output the target file and set the appropriate HTTP headers.
     */
    
    if ( $content_type )
        header( "Content-Type: " . $content_type );
    
    header( "Content-Length: " . filesize( $dst_uri ) );
    readfile( $dst_uri );
    
    ob_end_flush();
    
  }
  
  trigger_after( 'render_static', $req, $route );
  
}


  /**
   * catch_params
   * 
   * filter to show the current request parameters and die
   * 
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @param array $arr
   * @param object $route
   */

function catch_params( &$arr, &$route ) {

  print "dbscript Debug Output:";
  print "<br />--------------------------------";
  print "<br /><br /> Request Parameters: ";
  print_r($arr)."";
  print "<br /><br /> Route Patterns: ";
  print_r($route->patterns);
  exit;
  
}

/*
 * The mkdir function does not support the recursive
 * parameter in the version of PHP run by Yahoo! Web
 * Hosting. This function simulates it.
 */

function mkdir_r( $dir_name, $rights=0777 ) {
   $dirs = explode( "/", $dir_name );
   $dir = "";
   foreach ( $dirs as $part ) {
       $dir .= $part . "/";
       if ( !is_dir( $dir ) && strlen( $dir ) > 0 )
           mkdir( $dir, $rights );
   }
}

?>