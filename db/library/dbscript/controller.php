<?php

class Controller {
  
  var $before_filters;
  var $after_filters;
  var $layout;
  var $template_root;
  
  function Controller() {
    
  }
  
  function controller_name() {
    //@controller_name ||= self.name.to_const_path
  }
  
  function template_location() {
    # This would look for templates at controller.action.mime.type instead
    # of controller/action.mime.type
    #---
    # @public
    //def _template_location(action, type = nil, controller = controller_name)
    //"#{controller}/#{action}"
    //  end
  }
  
  function template_roots() {
    
  }
  
  function subclasses_list() {
    
  }
  
  function layout() {
    
  }
  
  function body() {
    
  }
  
  function action_name() {
    
  }
  
  function content_type() {
    
  }
  
  function benchmarks() {
    
  }
  
  function thrown_content() {
    
  }
  
  function call_filters( $filter_set ) {
    $filter_chain_completed = false;
    // Filter rules can be Symbols, Strings or Procs
    //foreach blah
    if (call_filter_for_action($rule, $action) && filter_condition_met($rule)) {
 
    }
    // case filter
    // when string, send content
    // when proc, eval and pass reference to &filter
    // return $filter_chain_completed
  }
  
  function dispatch($action) {
    //setup_session
    //self.action_name = action
    //
    //caught = catch(:halt) do
    //  start = Time.now
    //  result = _call_filters(_before_filters)
    //  @_benchmarks[:before_filters_time] = Time.now - start if _before_filters
    //  result
    //end
    //
    //@body = case caught
    //when :filter_chain_completed  then _call_action(action_name)
    //when String                   then caught
    //when nil                      then _filters_halted
    //when Symbol                   then __send__(caught)
    //when Proc                     then caught.call(self)
    //else
    //  raise MerbControllerError, "The before filter chain is broken dude. wtf?"
    //end
    //start = Time.now
    //_call_filters(_after_filters) 
    //@_benchmarks[:after_filters_time] = Time.now - start if _after_filters
    //finalize_session
  }
  
  function call_filter_for_action($rule, $action){
    return true;
  }
  
  function filter_condition_met($rule) {
    return true;
  }
 
  function before($filter,$opts) {
    add_filter($this->before_filters,$filter,$opts);
  }
  
  function after($filter,$opts) {
    add_filter($this->after_filters,$filter,$opts);
  }
  
    function skip_before($filter) {
    skip_filter($this->before_filters,$filter);
  }
  
  function skip_after($filter) {
    skip_filter($this->after_filters,$filter);
  }
  
}

