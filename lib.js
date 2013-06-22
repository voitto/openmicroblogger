
// Zygote
// June 22, 2013
// Brian Hendrickson <bh@bh.ly>


var app = {};

var routes = {};

function get( path, f ) {
  routes[ path + "get" ] = f;
  var req = null;
  var res = null;
  var a = document.createElement( 'a' );
  a.href = window.location;
  if ( path == a.pathname ) {
    if ( isFunc( f ))
      f( req, res );
  } else {
    var myarray  = a.pathname.split(/[\/]/);
    value = false;
    if (!(undefined == myarray[1]))
      if (isInt(myarray[1])) {
        f = routes[ '/:id' + 'get' ];
        value = myarray[1];
      }
    if ( isFunc( f ))
      f( req, res, value );
  }
}

function post( path, func ) {

}

function isFunc( ff ) {
  var getType = {};
  return ff && getType.toString.call( ff ) === '[object Function]';
}

var Model,
  __hasProp = {}.hasOwnProperty,
  __extends = function(child, parent) { for (var key in parent) { if (__hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor(); child.__super__ = parent.prototype; return child; };
  Model = (function() {
    function Model() {
      var m = this;
      socket.on( 'changed', function(){
        m.find();
        m.send('changed');
      });
    }
    return Model;
})();

var View,
  __hasProp = {}.hasOwnProperty,
  __extends = function(child, parent) { for (var key in parent) { if (__hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor(); child.__super__ = parent.prototype; return child; };
  View = (function() {
    function View( mod ) {
      //this.response = res;
      this.model = mod;
      this.model.register( this );
    }
    return View;
})();

var Controller,
  __hasProp = {}.hasOwnProperty,
  __extends = function(child, parent) { for (var key in parent) { if (__hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor(); child.__super__ = parent.prototype; return child; };
  Controller = (function() {
    function Controller( Model, View ) {
      this.view = View;
      this.view.controller = this;
      this.model = Model;
    }
    return Controller;
})();


Model.prototype.dependents = [];

Model.prototype.events = {};

Model.prototype.data = [];

Model.prototype.register = function( view ) {
  this.dependents.push( view );
}

Model.prototype.bind = function( evt, func ) {
  this.events[ evt ] = func;
}

Model.prototype.save = function() {
  var postdata = {
    title: $('#post-title').val()
  };
  $.ajax({
    type: "POST",
    url: '/post/new',
    dataType: 'json',
    async: false,
    data: JSON.stringify(postdata),
    success: function (resp) {
    }
  });
}

Model.prototype.send = function( evt ) {
  for (view in this.dependents) {
    this.dependents[view].receive( evt );
  }
}

Model.prototype.find = function(id) {
  var modelname = get_class(this).toLowerCase();
  var model = this;
  $.ajax({
    type: "POST",
    url: '/'+modelname+'.json',
    dataType: 'json',
    async: false,
    data: id,
    success: function (data) {
      model.data = data;
      model.send( 'changed' );
    }
  });
}

Model.prototype.to_hash = function() {
  json = {}
  recs = []
  for (i in this.data) {
    recs.push(this.data[i])
  }
  json['items'] = recs;
  return json;
}


Controller.prototype.model = null;

Controller.prototype.view = null;


View.prototype.model = null;

View.prototype.controller = null;

View.prototype.response = null;

View.prototype.template = null;

View.prototype.render = function() {
  var view = this;
  var viewname = get_class(this).toLowerCase();
  var modelname = get_class(this.model).toLowerCase();
  $.get( '/'+modelname+'/_'+viewname+'.html', function( tpl ) {
    view.template = tpl;
    $( '#content' ).html('<h1 style="color:blue;">client</h1>'+ Mustache.render( view.template, view.model.to_hash() ));
  });
  //$( 'body' ).append( '<div class="modal-bg"></div>' );
  //$( '#mymodal' ).addClass( 'active' ).css( 'top', $(window).scrollTop() + 50 + "px" );
  //t = fs.readFileSync(__dirname + '/../../views/index.html', 'utf-8');
  //return eco.render( t, data );
};

View.prototype.receive = function( message ) {
  if (message == 'changed') {
    this.controller.render();
    //this.response.end( this.controller.render() );
  }
}

app.Model = Model;
app.View = View;
app.Controller = Controller;
app.get = get;
app.post = post;

var re = function(){
  return {
    config:function(){
      return app;
    }
  }
}
var require = re;

(function($) {
    $(function () {
      
        $('#openbtn').click(function(e){
          e.preventDefault();
          var modalID = '#mymodal';
          $('body').append('<div class="modal-bg"></div>'); // Add modal background.
          $(modalID).addClass('active').css('top', $(window).scrollTop() + 50 + "px");
        });

        // Modal close button
        $('.modal-close').click(function(e) {
            e.preventDefault(); // Prevent default link behavior.
            $('.modal').removeClass('active'); // Hide modal.
            $('.modal-bg').remove(); // Remove modal background.
        });

        // When click outside of modal
        $('body').on('click touchstart','.modal-bg',function() {
            $('.modal').removeClass('active'); // Hide modal.
            $('.modal-bg').remove(); // Remove modal background.
        });

        // When escape key pressed
        $(document).on('keydown',function(e) {
            if ( e.keyCode === 27 ) { // If escape key pressed
                $('.modal').removeClass('active'); // Hide modal.
                $('.modal-bg').remove(); // Remove modal background.
            }
        });
    });
})(jQuery);


function get_class(obj){
 function get_class(obj){
  return "".concat(obj).replace(/^.*function\s+([^\s]*|[^\(]*)\([^\x00]+$/, "$1") || "anonymous";
 };
 var result = "";
 if(obj === null)
  result = "null";
 else if(obj === undefined)
  result = "undefined";
 else {
  result = get_class(obj.constructor);
  if(result === "Object" && obj.constructor.prototype) {
   for(result in this) {
    if(typeof(this[result]) === "function" && obj instanceof this[result]) {
     result = get_class(this[result]);
     break;
    }
   }
  }
 };
 return result;
};
function is_a(obj, className){
 className = className.replace(/[^\w\$_]+/, "");
 return  get_class(obj) === className && {function:1}[eval("typeof(".concat(className,")"))] && obj instanceof eval(className)
};

function isInt(value){
    var er = /^[0-9]+$/;
    return ( er.test(value) ) ? true : false;
}
