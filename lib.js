
// Zygote
// June 22, 2013
// Brian Hendrickson <bh@bh.ly>


var app = {};

var routes = {};

var called = false;

function get( path, f ) {
  if (called) return;
  routes[ path + "get" ] = f;
  var req = null;
  var res = null;
  var a = document.createElement( 'a' );
  a.href = window.location;
  if ( path == a.pathname ) {
    if ( isFunc( f )) {
      called = true;
      f( req, res );
    }
  } else {
    var myarray  = a.pathname.split(/[\/]/);
    value = false;
    if (!(undefined == myarray[1]))
      if (isInt(myarray[1])) {
        f = routes[ '/:id' + 'get' ];
        value = myarray[1];
      }
    if ( isFunc( f )) {
      called = true;
      f( req, res, value );
    }
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
    title: $('#post-title').val(),
    author: $('#post-author').val(),
    author_url: $('#post-author_url').val(),
    in_reply_to: $('#post-in_reply_to').val()
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
  json['url'] = '{{{url}}}';
  return json;
}


Controller.prototype.model = null;

Controller.prototype.view = null;


View.prototype.model = null;

View.prototype.controller = null;

View.prototype.response = null;

View.prototype.template = null;

View.prototype.render = function() {
  console.log('render '+get_class(this));
  var view = this;
  var viewname = get_class(this).toLowerCase();
  var modelname = get_class(this.model).toLowerCase();
  $.get( '/'+modelname+'/_'+viewname+'.html', function( tpl ) {
    view.template = tpl;
    $( '#content' ).html(''+ Mustache.render( view.template, view.model.to_hash() ));
  });
};

View.prototype.receive = function( message ) {
  if (message == 'changed') {
    console.log('changed '+get_class(this));
    this.controller.render();
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

/*
 * Date Format 1.2.3
 * (c) 2007-2009 Steven Levithan <stevenlevithan.com>
 * MIT license
 *
 * Includes enhancements by Scott Trenda <scott.trenda.net>
 * and Kris Kowal <cixar.com/~kris.kowal/>
 *
 * Accepts a date, a mask, or a date and a mask.
 * Returns a formatted version of the given date.
 * The date defaults to the current date/time.
 * The mask defaults to dateFormat.masks.default.
 */

var dateFormat = function () {
	var	token = /d{1,4}|m{1,4}|yy(?:yy)?|([HhMsTt])\1?|[LloSZ]|"[^"]*"|'[^']*'/g,
		timezone = /\b(?:[PMCEA][SDP]T|(?:Pacific|Mountain|Central|Eastern|Atlantic) (?:Standard|Daylight|Prevailing) Time|(?:GMT|UTC)(?:[-+]\d{4})?)\b/g,
		timezoneClip = /[^-+\dA-Z]/g,
		pad = function (val, len) {
			val = String(val);
			len = len || 2;
			while (val.length < len) val = "0" + val;
			return val;
		};

	// Regexes and supporting functions are cached through closure
	return function (date, mask, utc) {
		var dF = dateFormat;

		// You can't provide utc if you skip other args (use the "UTC:" mask prefix)
		if (arguments.length == 1 && Object.prototype.toString.call(date) == "[object String]" && !/\d/.test(date)) {
			mask = date;
			date = undefined;
		}

		// Passing date through Date applies Date.parse, if necessary
		date = date ? new Date(date) : new Date;
		if (isNaN(date)) throw SyntaxError("invalid date");

		mask = String(dF.masks[mask] || mask || dF.masks["default"]);

		// Allow setting the utc argument via the mask
		if (mask.slice(0, 4) == "UTC:") {
			mask = mask.slice(4);
			utc = true;
		}

		var	_ = utc ? "getUTC" : "get",
			d = date[_ + "Date"](),
			D = date[_ + "Day"](),
			m = date[_ + "Month"](),
			y = date[_ + "FullYear"](),
			H = date[_ + "Hours"](),
			M = date[_ + "Minutes"](),
			s = date[_ + "Seconds"](),
			L = date[_ + "Milliseconds"](),
			o = utc ? 0 : date.getTimezoneOffset(),
			flags = {
				d:    d,
				dd:   pad(d),
				ddd:  dF.i18n.dayNames[D],
				dddd: dF.i18n.dayNames[D + 7],
				m:    m + 1,
				mm:   pad(m + 1),
				mmm:  dF.i18n.monthNames[m],
				mmmm: dF.i18n.monthNames[m + 12],
				yy:   String(y).slice(2),
				yyyy: y,
				h:    H % 12 || 12,
				hh:   pad(H % 12 || 12),
				H:    H,
				HH:   pad(H),
				M:    M,
				MM:   pad(M),
				s:    s,
				ss:   pad(s),
				l:    pad(L, 3),
				L:    pad(L > 99 ? Math.round(L / 10) : L),
				t:    H < 12 ? "a"  : "p",
				tt:   H < 12 ? "am" : "pm",
				T:    H < 12 ? "A"  : "P",
				TT:   H < 12 ? "AM" : "PM",
				Z:    utc ? "UTC" : (String(date).match(timezone) || [""]).pop().replace(timezoneClip, ""),
				o:    (o > 0 ? "-" : "+") + pad(Math.floor(Math.abs(o) / 60) * 100 + Math.abs(o) % 60, 4),
				S:    ["th", "st", "nd", "rd"][d % 10 > 3 ? 0 : (d % 100 - d % 10 != 10) * d % 10]
			};

		return mask.replace(token, function ($0) {
			return $0 in flags ? flags[$0] : $0.slice(1, $0.length - 1);
		});
	};
}();

// Some common format strings
dateFormat.masks = {
	"default":      "ddd mmm dd yyyy HH:MM:ss",
	shortDate:      "m/d/yy",
	mediumDate:     "mmm d, yyyy",
	longDate:       "mmmm d, yyyy",
	fullDate:       "dddd, mmmm d, yyyy",
	shortTime:      "h:MM TT",
	mediumTime:     "h:MM:ss TT",
	longTime:       "h:MM:ss TT Z",
	isoDate:        "yyyy-mm-dd",
	isoTime:        "HH:MM:ss",
	isoDateTime:    "yyyy-mm-dd'T'HH:MM:ss",
	isoUtcDateTime: "UTC:yyyy-mm-dd'T'HH:MM:ss'Z'"
};

// Internationalization strings
dateFormat.i18n = {
	dayNames: [
		"Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat",
		"Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
	],
	monthNames: [
		"Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
		"January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
	]
};

// For convenience...
Date.prototype.format = function (mask, utc) {
	return dateFormat(this, mask, utc);
};