/**
 * jqUploader (http://www.pixeline.be/experiments/jqUploader/)
 * A jQuery plugin to replace html-based file upload input fields with richer flash-based upload progress bar UI.
 *
 * Version 1.0.2.2
 * September 2007
 *
 * Copyright (c) 2007 Alexandre Plennevaux (http://www.pixeline.be)
 * Dual licensed under the MIT and GPL licenses.
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * using plugin "Flash" by Luke Lutman (http://jquery.lukelutman.com/plugins/flash)
 *
 * IMPORTANT:
 * The packed version of jQuery breaks ActiveX control
 * activation in Internet Explorer. Use JSMin to minifiy
 * jQuery (see: http://jquery.lukelutman.com/plugins/flash#activex).
 *
 **/
 jQuery.fn.jqUploader = function(options) {
    return this.each(function(index) {
        var $this = jQuery(this);
		// fetch label value if any, otherwise set a default one
		var $thisForm =  $this.parents("form");
		var $thisInput = $("input[@type='file']",$this);
		var $thisLabel = $("label",$this);
		var containerId = $this.attr("id") || 'jqUploader-'+index;
		//alert(containerId);
		var startMessage = ($thisLabel.text() =='') ? 'Choose a Photo, Song or Video to Upload...' : $thisLabel.text();
		// get form action attribute value as upload script, appending to it a variable telling the script that this is an upload only functionality
		var actionURL = $thisForm.attr("action");
		// adds a var setting jqUploader to 1, so you can use it for serverside processing
		var prepender = (actionURL.lastIndexOf("?") != -1) ? "&": "?";
		//actionURL = actionURL+prepender+'jqUploader=1';
		// check if max file size is set in html form
		var maxFileSize = $("input[@name='MAX_FILE_SIZE']", $(this.form)).val();
		var opts = jQuery.extend({
				width:402,
				height:85,
				version: 8, // version 8+ of flash player required to run jqUploader
				background: 'FFFFFF', // background color of flash file
				src:    'jqUploader.swf',
				uploadScript:     actionURL,
				afterScript:      null, // if this is empty, jqUploader will replace the upload swf by a hidden input element
				varName:	        $thisInput.attr("name"),  //this holds the variable name of the file input field in your html form
				allowedExt:	      '*.jpg; *.jpeg; *.png', // allowed extensions
				allowedExtDescr:  'Images (*.jpg; *.jpeg; *.png)',
				params:           {menu:false},
				flashvars:        {},
				hideSubmit:       true,
				barColor:		      '0000CC',
				maxFileSize:      maxFileSize,
				startMessage:     startMessage,
				errorSizeMessage: 'File is too big!',
				validFileMessage: 'now click Upload to proceed',
				progressMessage: 'Please wait, uploading ',
				endMessage:    'You\'re all done'
		}, options || {}
		);
		// disable form submit button
		if (opts.hideSubmit==true) {
			$("*[@type='submit']",this.form).hide();
		}
		// THIS WILL BE EXECUTED IN THE USECASE THAT THERE IS NO REDIRECTION TO BE DONE AFTER UPLOAD
		TerminateJQUploader = function(containerId,filename,varname){
			$this= $('#'+containerId).empty();
			$this.text('').append('<span style="color:#884444">Upload of <strong>'+filename+'</strong> finished!<input name="postfile" type="hidden" value="'+filename+'"/>');
			//var myForm = $this.parents("form");
			//myForm.submit(function(){return true});
			//$("*[@type='submit']",myForm).show();
			//myForm.submit();
		}
		var myParams = '';
		for (var p in opts.params){
				myParams += p+'='+opts.params[p]+',';
		}
		myParams = myParams.substring(0, myParams.length-1);
		// this function interfaces with the jquery flash plugin
		jQuery(this).flash(
		{
			src: opts.src,
			width: opts.width,
			height: opts.height,
			id:'movie_player-'+index,
			bgcolor:'#'+opts.background,
			flashvars: {
				containerId: containerId,
				uploadScript: opts.uploadScript,
				afterScript: opts.afterScript,
				allowedExt: opts.allowedExt,
				allowedExtDescr: opts.allowedExtDescr,
				varName :  opts.varName,
				barColor : opts.barColor,
				maxFileSize :opts.maxFileSize,
				startMessage : opts.startMessage,
				errorSizeMessage : opts.errorSizeMessage,
				validFileMessage : opts.validFileMessage,
				progressMessage : opts.progressMessage,
				endMessage: opts.endMessage
			},
			params: myParams
		},
		{
			version: opts.version,
			update: false
		},
			function(htmlOptions){
				var $el = $('<div id="'+containerId+'" class="flash-replaced"><div class="alt">'+this.innerHTML+'</div></div>');
					 $el.prepend($.fn.flash.transform(htmlOptions));
					 $('div.alt',$el).remove();
					 $(this).after($el).remove();
			}
		);
	});
};
