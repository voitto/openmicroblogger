/**
 * jQuery custom checkboxes
 * 
 * Copyright (c) 2008 Khavilo Dmitry (http://widowmaker.kiev.ua/checkbox/)
 * Licensed under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 *
 * @version 1.1.0 Beta
 * @author Khavilo Dmitry
 * @mailto wm.morgun@gmail.com
**/

(function($){

	$.fn.checkbox = function(options) {
	
		/* IE6 background flicker fix */
		try	{ document.execCommand('BackgroundImageCache', false, true);	} catch (e) {}
		
		/* Default settings */
		var settings = {
			cls: 'jquery-checkbox',  /* checkbox  */
			empty: 'empty.png'  /* checkbox  */
		};
		
		/* Processing settings */
		settings = $.extend(settings, options || {});
		
		/* Adds check/uncheck & disable/enable events */
		var addEvents = function(object)
		{
			var checked = object.checked;
			var disabled = object.disabled;
			var $object = $(object);
			
			if ( object.stateInterval )
				clearInterval(object.stateInterval);
			
			object.stateInterval = setInterval(
				function() 
				{
					if ( object.disabled != disabled )
						$object.trigger( (disabled = !!object.disabled) ? 'disable' : 'enable');
					if ( object.checked != checked )
						$object.trigger( (checked = !!object.checked) ? 'check' : 'uncheck');
				}, 
				10 /* in miliseconds. Low numbers this can decrease performance on slow computers, high will increase responce time */
			);
			return $object;
		}
		try { console.log(this); } catch(e) {}
		/* Wrapping all passed elements */
		return this.each(function() 
		{
			var ch = this;
			var $ch = addEvents(ch); /* Adds custom eents and returns */
			
			if (ch.wrapper)
			{
				ch.wrapper.remove();
			}
			
			/* Creating div for checkbox and assigning "hover" event */
			ch.wrapper = $('<span class="' + settings.cls + '"><span class="mark"><img src="' + settings.empty + '" /></span></span>');
			ch.wrapperInner = ch.wrapper.children('span');
			ch.wrapper.hover(
				function() { ch.wrapperInner.addClass(settings.cls + '-hover'); },
				function() { ch.wrapperInner.removeClass(settings.cls + '-hover'); }
			);

			/* Wrapping checkbox */
			$ch.css({position: 'absolute', zIndex: -1}).after(ch.wrapper);
			
			/* Fixing IE6 label behaviour */
			var parents = $ch.parents('label');
			/* Creating "click" event handler for checkbox wrapper*/
			if ( parents.length )
			{
				parents.click(function(e) { $ch.trigger('click', [e]); return ( $.browser.msie && $.browser.version < 7 ); });
			}
			else
			{
				ch.wrapper.click(function(e) { $ch.trigger('click', [e]); });
			}
			
			delete parents;
				
			$ch.bind('disable', function() { ch.wrapperInner.addClass(settings.cls+'-disabled');}).bind('enable', function() { ch.wrapperInner.removeClass(settings.cls+'-disabled');});
			$ch.bind('check', function() { ch.wrapper.addClass(settings.cls+'-checked' );}).bind('uncheck', function() { ch.wrapper.removeClass(settings.cls+'-checked' );});
			
			/* Disable image drag-n-drop  */
			$('img', ch.wrapper).bind('dragstart', function () {return false;}).bind('mousedown', function () {return false;});
			
			/* Firefox div antiselection hack */
			if ( window.getSelection )
				ch.wrapper.css('MozUserSelect', 'none');
			
			/* Applying checkbox state */
			if ( ch.checked )
				ch.wrapper.addClass(settings.cls + '-checked');
			if ( ch.disabled )
				ch.wrapperInner.addClass(settings.cls + '-disabled');
		});
	};


})(jQuery);
