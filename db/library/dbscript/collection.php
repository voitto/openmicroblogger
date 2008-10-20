<?php

  /**
   * dbscript -- restful openid framework
   * @version 0.6.0 -- 22-October-2008
   * @author Brian Hendrickson <brian@dbscript.net>
   * @link http://dbscript.net/
   * @copyright Copyright 2008 Brian Hendrickson
   * @package dbscript
   * @license http://www.opensource.org/licenses/mit-license.php MIT License
   */

  /**
   * Collection
   * 
   * More info...
   * {@link http://dbscript.net/collection}
   * 
   * @package dbscript
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @version 0.6.0 -- 22-October-2008
   */

class Collection extends GenericIterator {
  
  var $member_entry_iri;
  
  var $media_iri;
  
  var $resource;
  
  var $accept;
  
  var $members;
  
  var $fields;
  
  var $updated;
  
  var $per_page;
  
  // member_entry_iri will be a media link or member entry
  
  // a media link entry is a member entry that
  // contains metadata about a media resource
  
  function Collection( $resource, $find_by = NULL, $accept = "text/html" ) {
    
    $this->_currentRow = 0;
    
    $this->EOF = false;
    
    $this->members = array();
    
    $this->fields = array();
        
    if ($resource == NULL)
      return;
    
    global $request;
    
    global $db;
    
    $this->resource = $resource;
    
    $this->accept = $accept;
    
    if ( $resource == 'introspection' ) {
      $this->members = introspect_tables();
      return;
    }
    
    if ($resource != classify($resource))
      $table =& $db->get_table( $this->resource );
    else
      return;
    
    // $member->member_entry_iri // Entry object of type 'member' or 'media link'
    
    // $member->media_iri = ; // (optional) Entry object of type 'media link'
    
    if ( isset( $table->params )) {
      foreach ( $table->params as $key=>$val ) {
        if (!(isset($this->$key)))
          $this->$key = $val;
      }
    }
    
    if (isset($request->params['offset']))
      $table->set_offset($request->params['offset']);
    
    if (isset($request->params['orderby']))
      $table->set_orderby($request->params['orderby']);
    
    if (isset($request->params['order']))
      $table->set_order($request->params['order']);
    
    if (isset($table->limit))
      $this->per_page = $table->limit;
    else
      $this->per_page = 20;

    if (isset($request->params['page']) && !is_array($request->params['page']))
      $table->set_offset( ($this->per_page * $request->params['page']) - $this->per_page );

    if ( !$request->id )
      $table->find();
    else
      $table->find( $request->id );
    
    if (isset($table->uri_key))
      $uri_key = $table->uri_key;
    else
      $uri_key = $table->primary_key;
    
    if ($table->rowcount() > 0) {
      $first = true;
      while ( $Member = $table->MoveNext() ) {
        if ( isset( $db->models['entries'] )) {
          $Entry = $Member->FirstChild( 'entries' );
          if ($Entry) {
            $Member->last_modified = $Entry->last_modified;
            $Member->etag = $Entry->etag;
          }
        }
        $this->members[$Member->$uri_key] = $resource;
        $this->updated = timestamp();
        if (isset($Member->modified) && !empty($Member->modified))
          $this->updated = $Member->modified;
        if ($first) {
          if ( isset( $db->models['entries'] )) {
            if (!empty($Entry->last_modified))
              $this->updated = $Entry->last_modified;
          }
        }
        $first = false;
      }
      $table->rewind();
    }
    
  }
  
  function rewind() {
    global $db;
    global $request;
    $model =& $db->models[$this->resource];
    $model->rewind();
    $this->_currentRow = 0;
    $this->EOF = false;
  }
  
  function MoveNext() {
    global $db;
    global $request;
    $model =& $db->models[$this->resource];
    $this->_currentRow++;
    if ($this->_currentRow <= $this->per_page) {
      if ($model)
        return $model->MoveNext();
    }
    
    return false;
    
  }
  
  function MoveFirst() {
    global $db;
    $model =& $db->models[$this->resource];
    if ($model)
      return $model->MoveFirst();
  }
  
}

?>