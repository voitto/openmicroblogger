/**
 * LongURL jQuery Plugin 1.0
 *
 * Appends an expand link next to shortened URLs. The expand link will replace
 * the shortened link with the final destination retrieved using LongURL.org.
 *
 * Usage: jQuery.longurl();
 *
 * @author Sean Murphy <sean@statiksoft.com>
 * @class longurl
 * @param {Object} config, custom config-object
 *
 * Copyright (c) 2008, Sean Murphy
 * Released under the GPL license http://www.gnu.org/copyleft/gpl.html
 */
jQuery.fn.longurl = function(config) {
	config = jQuery.extend({
		api_endpoint: 'http://api.longurl.org/v1/',
		working_image: 'http://longurl.org/static/ajax_indicator_sm_round.gif'
	}, config);
	
	var links = this;
	var known_services = [];
	var data_cache = [];

	getServicesFromAPI = function() {
		jQuery.ajax({
			type: 'GET',
			url: config.api_endpoint + 'services',
			data: {format: 'json'},
			dataType: 'jsonp',
			success: function(data, responseText) {
				if (typeof(data.messages) === 'undefined') {
					saveKnownServices(data);
					modifyShortLinks();
				}
			}
		});
	};
	
	saveKnownServices = function(data) {
		for (var x in data) { // Foreach service
			if (typeof(data[x]) === 'object') {
				for (var y in data[x]) { // Foreach domain
					if (typeof(data[x][y]) === 'string') {
						known_services.push(data[x][y]);
					}
				}
			}
		}
		
		known_services = known_services.sort();
		
		// Store the list of supported services locally
		var services_str = '["' + known_services.join('","') + '"]';
		setValue('longurl_services', services_str);
		
		var date = new Date();
		date.setTime(date.getTime() + (1000 * 60 * 60 * 24 * 1)); // 1 Day
		setValue('longurl_expire_services', date.toUTCString());
	};
	
	modifyShortLinks = function() {
		links.filter('a[href^="http://"]').each(function() {
			var link = jQuery(this);
			var domain = link.attr('href').match(/^http:\/\/([^\/]+)\/.+/i);
			
			if (domain && searchArray(domain[1], known_services) !== -1) {
			
			
				jQuery.ajax({
    			type: 'GET',
    			url: config.api_endpoint + 'expand',
    			data: {format: 'json', url: link.attr('href')},
    			dataType: 'jsonp',
    			success: function(data, responseText) {
    				if (typeof(data.messages) !== 'undefined') { // There was an error
    					var response = 'LongURL Error: ' + data.messages[0].message;
    				} else {
    					var response = data.long_url;
    				}
				
    				dataCache(escape(link.attr('href')), {
    					response: response,
    					original_text: link.html()
    				});
    				
            link.html(response);
            link.attr('href',response);
            link.oembed();
            
    			}
    		});
				//link.after(' <span class="longurl"><a href="#" onClick="toggleLink(this); return false;" class="expand">expand</a><img src="'+config.working_image+'" style="display:none;" /></span>');
			}
		});
	};
	
	toggleLink = function(toggle) {
		toggle = jQuery(toggle);
		var link = toggle.parent().prev('a');
		
		if (toggle.hasClass('expand')) {
			expandLink(toggle, link);
			toggle.removeClass('expand').addClass('contract');
			toggle.html('contract');
		} else {
			link.html(dataCache(escape(link.attr('href'))).original_text);
			toggle.removeClass('contract').addClass('expand');
			toggle.html('expand');
		}
	};
	
	expandLink = function(toggle, link) {
		// Check cache
		if (dataCache(escape(link.attr('href')))) {
			link.html(dataCache(escape(link.attr('href'))).response);
			return;
		}
		
		// Display working image
		toggle.hide().siblings('img').show();
		
		jQuery.ajax({
			type: 'GET',
			url: config.api_endpoint + 'expand',
			data: {format: 'json', url: link.attr('href')},
			dataType: 'jsonp',
			success: function(data, responseText) {
				if (typeof(data.messages) !== 'undefined') { // There was an error
					var response = 'LongURL Error: ' + data.messages[0].message;
				} else {
					var response = data.long_url;
				}
				
				dataCache(escape(link.attr('href')), {
					response: response,
					original_text: link.html()
				});

				link.html(response);
				toggle.show().siblings('img').hide();
			}
		});
	};
	
	dataCache = function(key, value) {
		if (typeof(value) === 'undefined') { // Get
			if (typeof(data_cache[key]) !== 'undefined') {
				return data_cache[key];
			}
			return false;
		} else { // Set
			data_cache[key] = value;
		}
	}
	
	searchArray = function(needle, haystack, case_insensitive) {
		if (typeof(haystack) === 'undefined' || !haystack.length) return -1;
		
		var high = haystack.length - 1;
		var low = 0;
		case_insensitive = (typeof(case_insensitive) === 'undefined' || case_insensitive) ? true:false;
		needle = (case_insensitive) ? needle.toLowerCase():needle;
		
		while (low <= high) {
			mid = parseInt((low + high) / 2)
			element = (case_insensitive) ? haystack[mid].toLowerCase():haystack[mid];
			if (element > needle) {
				high = mid - 1;
			} else if (element < needle) {
				low = mid + 1;
			} else {
				return mid;
			}
		}
		
		return -1;
	};
	
	setValue = function(key, value) {	
		document.cookie = key+'='+encodeURIComponent(value);
	};
	
	getValue = function(key, default_val) {
		if (document.cookie && document.cookie != '') {
			var cookies = document.cookie.split(';');
			for(var x = 0; x < cookies.length; x++) {
				var cookie = jQuery.trim(new String(cookies[x]));
				if (cookie.substring(0, key.length + 1) == (key + '=')) {
					return decodeURIComponent(cookie.substring(key.length + 1));
				}
			}
		}
		return default_val;
	};
	
	var now = new Date();
	var serialized_services = getValue('longurl_services', false);
	var services_expire = Date.parse(getValue('longurl_expire_services', now.toUTCString()));

	if (serialized_services && services_expire > now.getTime()) {
		known_services = eval('(' + serialized_services + ')');
		modifyShortLinks();
	} else {
		getServicesFromAPI();
	}
};