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
   * Boot Loader
   * 
   * loads the boot
   * 
   * Usage:
   * <code>
   *   $loader = new BootLoader();
   *   $loader->start();
   * </code>
   * 
   * More info...
   * {@link http://dbscript.net/bootloader}
   * 
   * @package dbscript
   * @author Brian Hendrickson <brian@dbscript.net>
   * @access public
   * @version 0.6.0 -- 22-October-2008
   */

class BootLoader {

  var $subclasses;
  var $loaders;
  var $after_load_callbacks;
  var $before_load_callbacks;
  
  function BootLoader( $classes = NULL ) {
    
    if ($classes != NULL)
      $this->subclasses = $classes;
    else
      $this->subclasses = array(
        'RackUpApplication',
        'BuildFramework',
        'Dependencies',
        'BeforeAppRuns',
        'LoadClasses',
        'Templates',
        'MimeTypes',
        'AfterAppLoads',
        'MixinSessionContainer',
        'ChooseAdapter',
        'ReloadClasses',
        'ReloadTemplates'
      );
    $this->after_load_callbacks = array();
    $this->before_load_callbacks = array();
    $this->loaders = array();
    
  }
  
  function start() {
    foreach($this->subclasses as $loader) {
      //Merb.logger.debug!("Loading: #{bootloader}") if ENV['DEBUG']
      $this->loaders[$loader] = new $loader();
      $this->loaders[$loader]->run();
    }
  }
  
  function default_framework() {
    // set paths here, here's how Merbs paths look
    // ---------------------
    // application,  Merb.root_path("app/controllers/application.rb"))
    // config,       Merb.root_path("config"), nil)
    // router,       Merb.dir_for(:config), (Merb::Config[:router_file] || "router.rb"))
    // lib,          Merb.root_path("lib"), nil)
    // log,          Merb.log_path, nil)
    // public,       Merb.root_path("public"), nil)
    // stylesheet,   Merb.dir_for(:public) / "stylesheets", nil)
    // javascript,   Merb.dir_for(:public) / "javascripts", nil)
    // image,        Merb.dir_for(:public) / "images", nil)
  }
  
  function before_app_runs($function) {
    $this->before_load_callbacks[] = $function;
  }
  
  function after_app_loads($function) {
    $this->after_load_callbacks[] = $function;
  }
  
  function add_loader($loader) {
    $this->subclasses[] = $loader;
  }

}

// log_file, log_level, log_delimiter, log_auto_flush

class BuildFramework extends BootLoader {
    # Builds the framework directory structure.
  function BuildFramework() {
    //$loader->add_subclass( 'BuildFramework' );
  }
  
  function run() {
    $this->build_the_framework();
  }
  
  function build_the_framework() {
    
    //      if File.exists?(Merb.root / "config" / "framework.rb")
    //    require Merb.root / "config" / "framework"
    //  elsif File.exists?(Merb.root / "framework.rb")
    //    require Merb.root / "framework"
    //  else
    //    Merb::BootLoader.default_framework
    //  end
    //  (Merb::Config[:framework] || {}).each do |name, path|
    //    path = [path].flatten
    //    Merb.push_path(name, Merb.root_path(path.first), path[1])
    //  Merb::Config[:framework] = {
#     :view => Merb.root / "views"
#     :model => Merb.root / "models"
#     :lib => Merb.root / "lib"
#   }
# 
    
  }
  
}

class Dependencies extends BootLoader {
  
  function Dependencies() {
    
  }
  
  function run() {
    //$this->();
  }
  
}

class BeforeAppRuns extends BootLoader {
  
  function BeforeAppRuns() {
    
  }
  
  function run() {
    //$this->build_the_framework();
  }
  
}

class LoadClasses extends BootLoader {
# Load all classes inside the load paths.
#
# This is used in conjunction with Merb::BootLoader::ReloadClasses to track
# files that need to be reloaded, and which constants need to be removed in
# order to reload a file.
#
# This also adds the model, controller, and lib directories to the load path,
# so they can be required in order to avoid load-order issues.

  var $loaded_classes;
  var $orphaned_classes;
  var $mtimes;
  
  function LoadClasses() {
    
  }
  
  function run() {
    $paths = environment( 'load_paths' );

    //$this->models[$table] = new $custom_class();
    //$this->models[$table]->register($table);
    //return $this->models[$table];
    
    global $db;
    
    if (isset($paths['application']))
      $app = $paths['application'];
    else
      $app = 'db';
    
    $loadpaths = array();
    
    foreach($paths as $name=>$path)
      if (!empty($path))
        $loadpaths[$name] = $GLOBALS['PATH']['app'].$path.DIRECTORY_SEPARATOR;
    
    if (isset($GLOBALS['PATH']['apps'])) {
      foreach($GLOBALS['PATH']['apps'] as $k=>$v) {
        $loadpaths[$k] = $v['model_path'];
      }
    }
    
    foreach ($loadpaths as $name => $loadpath) {
      //next unless path.last && name != :application
      //Dir[path.first / path.last].each do |file|
      //load_file file
     
        if (!empty($loadpath) && $handle = opendir($loadpath)) {
          while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..' && substr($file,-3) == 'php') {
              require_once $loadpath.$file;
              $cl = substr($file,0,-4);
              if (!(isset($db->models[tableize($cl)])))
                $db->models[tableize($cl)] = new $cl();
            }
          }
          closedir($handle);
        }
    }


    $orphaned_classes = array();

    # Add models, controllers, and lib to the load path
    #$LOAD_PATH.unshift Merb.dir_for(:model)
    #$LOAD_PATH.unshift Merb.dir_for(:controller)
    #$LOAD_PATH.unshift Merb.dir_for(:lib)
    #load_file Merb.dir_for(:application) if File.file?(Merb.dir_for(:application))
    # Require all the files in the registered load paths
    //Merb::Controller.send :include, Merb::GlobalHelpers
    $this->load_classes_with_requirements($orphaned_classes);
  }
  
  function load_file( $file ) {
    //klasses = ObjectSpace.classes.dup
    //load file
    //$this->loaded_classes[$file] = ObjectSpace.classes - klasses
    //$this->mtimes[$file] = File.mtime(file)
  }
  
  function load_classes_with_requirements( $classes ) {
    foreach ( $classes as $class ) {
      $this->load_file( $class );
    }
  }
  
  function reload( $file ) {
    // Merb.klass_hashes.each {|x| x.protect_keys!}
    // if klasses = LOADED_CLASSES.delete(file)
    // klasses.each { |klass| remove_constant(klass) unless klass.to_s =~ /Router/ }
  }
  //parts = const.to_s.split("::")
  //base = parts.size == 1 ? Object : Object.full_const_get(parts[0..-2].join("::"))
  //object = parts[-1].to_s
  //base.send(:remove_const, object)
      
  //  Merb.logger.debug("Removed constant #{object} from #{base}")
  //  Merb.logger.debug("Failed to remove constant #{object} from #{base}")
  
}

class Templates extends BootLoader {
  
  function Templates() {
    
  }
  
  function run() {
    //$this->();
  }
  
}

class MimeTypes extends BootLoader {
  
  function MimeTypes() {
    
  }
  
  function run() {
    //$this->();
  }
  
}

class AfterAppLoads extends BootLoader {
  
  function AfterAppLoads() {
    
  }
  
  function run() {
    //$this->();
  }
  
}

class MixinSessionContainer extends BootLoader {
  
  function MixinSessionContainer() {
    
  }
  
  function run() {
    //$this->();
  }
  
}

class ChooseAdapter extends BootLoader {
  
  function ChooseAdapter() {
    
  }
  
  function run() {
    //$this->();
  }
  
}

class RackUpApplication extends BootLoader {
  
  function RackUpApplication() {
    
    # Setup the Merb Rack App or read a rack.rb config file located at the
    # Merb.root or Merb.root / config / rack.rb with the same syntax as the
    # rackup tool that comes with rack. Automatically evals the rack.rb file in
    # the context of a Rack::Builder.new { } block. Allows for mounting
    # additional apps or middleware.
    # def self.run
    #   if File.exists?(Merb.dir_for(:config) / "rack.rb")
    #     Merb::Config[:app] =  eval("::Rack::Builder.new {( #{IO.read(Merb.dir_for(:config) / 'rack.rb')}\n )}.to_app", TOPLEVEL_BINDING, __FILE__, __LINE__)
    #   else
    #     Merb::Config[:app] = ::Merb::Rack::Application.new
    #   end
    # end
    # end
    
    // $
    
    
  }
  
  function run() {
    global $config;
    $apps = environment('apps');
    $GLOBALS['PATH']['app_plugins'] = array();
    $GLOBALS['PATH']['apps'] = array();
    foreach($apps as $app) {
      $GLOBALS['PATH']['app_plugins'][] = $app.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR;
      $GLOBALS['PATH']['apps'][$app] = array(
        'layout_path' => $app.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR,
        'model_path' => $app.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR,
        'controller_path' => $app.DIRECTORY_SEPARATOR.'controllers'.DIRECTORY_SEPARATOR
      );
    }
    
    //    if ( is_dir( $app . $env['view_folder'] ) )
    //      $request->set_template_path( $app . $env['view_folder'].DIRECTORY_SEPARATOR );
    //    else
    //      $request->set_template_path( $env['view_folder'].DIRECTORY_SEPARATOR );
    
    //    if ( is_dir( $app . $env['layout_folder'] ) )
    //      $request->set_layout_path( $app . $env['layout_folder'].DIRECTORY_SEPARATOR );
    //    else
    //      $request->set_layout_path( $env['layout_folder'].DIRECTORY_SEPARATOR );
    
    
  }
  
}

class ReloadClasses extends BootLoader {
  
  function ReloadClasses() {
    
  }
  
  function run() {
    //$this->();
  }
  
}

class ReloadTemplates extends BootLoader {
  
  function ReloadTemplates() {
    
  }
  
  function run() {
    //$this->();
  }
  
}









# To override the default, set Merb::Config[:framework] in your initialization
# file. Merb::Config[:framework] takes a Hash whose key is the name of the
# path, and whose values can be passed into Merb.push_path (see Merb.push_path
# for full details).
# application:: Merb.root/app/controller/application.rb
# config:: Merb.root/config
# lib:: Merb.root/lib
# log:: Merb.root/log
# view:: Merb.root/app/views
# model:: Merb.root/app/models
# controller:: Merb.root/app/controllers
# helper:: Merb.root/app/helpers
# mailer:: Merb.root/app/mailers
# part:: Merb.root/app/parts
#   Merb::Config[:framework] = {
#     :view => Merb.root / "views"
#     :model => Merb.root / "models"
#     :lib => Merb.root / "lib"
#   }
# That will set up a flat directory structure with the config files and
# controller files under Merb.root, but with models, views, and lib with their
# own folders off of Merb.root.
