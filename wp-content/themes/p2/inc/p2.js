//Init
jQuery(document).ready(function()  {
	commentsLists = jQuery("ul.commentlist");
	jQuery()
	
	jQuery('#posttext').focus();
	
		jQuery(".single #postlist li > div.postcontent, .single #postlist li > h4, li[id^='prologue'] > div.postcontent, li[id^='comment'] > div.commentcontent, li[id^='prologue'] > h4, li[id^='comment'] > h4").hover(function() {
			jQuery(this).parents("li").eq(0).addClass('selected');
		}, function() {
			jQuery(this).parents("li").eq(0).removeClass('selected');
		});
	
	jQuery.ajaxSetup({
	  timeout: updateRate-2000,
	  cache: false
	});
	
	jQuery("#directions-keyboard").click(function(){
		jQuery('#help').toggle();
		return false;
	});
	
	jQuery("#help").click(function(){
		jQuery(this).toggle();
	});
	jQuery("#togglecomments").click(function(){
		hidecomments = hidecomments ? false : true;
		var hideTxt = "Hide threads";
		var showTxt = "Show threads";
		if (hidecomments) {
			commentLoop = false;
			commentsLists.hide();
			jQuery(this).text(showTxt);
		}
		else {
			commentsLists.show();
			jQuery(this).text(hideTxt);
		}
		return false;
	});
	
	if (!isFrontPage)
		jQuery("#togglecomments").click();
	
	//Activate inline editing plugin
	if ((inlineEditPosts || inlineEditComments ) && isUserLoggedIn) {
		jQuery.editable.addInputType('autogrow', {
		    element : function(settings, original) {
		        var textarea = jQuery('<textarea />');
		        if (settings.rows) {
		            textarea.attr('rows', settings.rows);
		        } else {
		            textarea.attr('rows', 4);
		        }
		        if (settings.cols) {
		            textarea.attr('cols', settings.cols);
		        } else {
		            textarea.attr('cols', 45);
		        }
				textarea.width('95%');
		        jQuery(this).append(textarea);
		        return(textarea);
		    },
		    plugin : function(settings, original) {
		        jQuery('textarea', this).keypress(function(e) {autgrow(this, 3);});
		        jQuery('textarea', this).focus(function(e) {autgrow(this, 3);});
		    }
		});
	}
	
	
	//Set tabindex on all forms
     var tabindex = 1;  
     jQuery('form').each(function() {  
         jQuery(':input',this).not('input[type=hidden]').each(function() {  
             var $input = jQuery(this);  
            var tabname = $input.attr("name");  
             var tabnum = $input.attr("tabindex");  
             if(tabnum > 0) {  
                 index = tabnum;  
             } else {  
                 $input.attr("tabindex", tabindex);  
             }  
             tabindex++;  
         });  
     });  
	
	
	//turn on automattic updating
	
	if (prologuePostsUpdates && isUserLoggedIn) {
			toggleUpdates('unewposts');
	}
	
	if (prologueCommentsUpdates && isUserLoggedIn) {
			toggleUpdates('unewcomments');		
	}
	
	//Check which posts are visibles and add to array and comment querystring.  Bind actions to udpates
	jQuery("#main > ul > li").each(function() { 
			var thisId = jQuery(this).attr("id");
			vpost_id = thisId.substring(thisId.indexOf('-')+1);
			postsOnPage.push(thisId);
			postsOnPageQS+= "&vp[]=" + vpost_id;
	});

	//bind actions to comments and posts
	if (inlineEditPosts && isUserLoggedIn) {
		jQuery('div.editarea').editable(ajaxUrl, {event: 'edit',loadurl: ajaxUrl + '?action=prologue_load_post&_inline_edit=' + nonce, id: 'post_ID', name: 'content', type    : 'autogrow', cssclass: 'textedit',rows: '3', indicator : '<img src="' + templateDir +'/i/indicator.gif">', loadtext: 'Loading...', cancel: 'Cancel', submit  : 'Save', tooltip   : '', width: '90%', onblur: 'ignore', submitdata: {action:'prologue_inline_save', _inline_edit: nonce}});
		jQuery('#main a.post-edit-link').click(function() {
			jQuery(this).parents('li').children('div.editarea').trigger("edit");
			return false;
		});
	}

	if (inlineEditComments && isUserLoggedIn) {
		jQuery('div.comment-edit').editable(ajaxUrl, {event: 'edit',loadurl: ajaxUrl + '?action=prologue_load_comment&_inline_edit=' + nonce, id: 'comment_ID',name: 'comment_content', type    : 'autogrow', cssclass: 'textedit', rows: '3', indicator : '<img src="' + templateDir +'/i/indicator.gif">',loadtext: 'Loading...', cancel: 'Cancel', submit  : 'Save',	tooltip   : '',  width: '90%', submitdata: {action:'prologue_inline_comment_save',_inline_edit: nonce}});
		jQuery('a.comment-edit-link').click(function() {
			jQuery(this).parents('h4').next('div.comment-edit').trigger("edit");
			return false;
		});
	}
	
	jQuery('#cancel-comment-reply-link').click(function() {
		jQuery('#comment').val('');
		if (!isSingle)
			jQuery("#respond").hide();
		jQuery(this).parents("li").removeClass('replying');
		jQuery(this).parents('#respond').prev("li").removeClass('replying');
		jQuery("#respond").removeClass('replying');
	});
	
	jQuery('a.comment-reply-link').click(function() {
			jQuery('#main li').removeClass('replying');
			jQuery(this).parents("li").eq(0).addClass('replying');
			jQuery("#respond").addClass('replying').show();
			jQuery("#comment").focus();
		});
		
		
	function removeyellow() {
		jQuery('li.newcomment, tr.newcomment').each(function() {
			if (isElementVisible(this)) {
				jQuery(this).animate({backgroundColor:'transparent'}, {duration: 2500}, function(){
					jQuery(this).removeClass('newcomment');
				});
			}
		});
		if (isFirstFrontPage) {
			jQuery('#main > ul > li.newupdates').each(function() {
				if (isElementVisible(this)) {
					jQuery(this).animate({backgroundColor:'transparent'}, {duration: 2500}, function(){
						jQuery(this).removeClass('newupdates');
						titleCount();
					});
				}
			});
		}
	}


	//Activate keyboard navigation
	if (!isSingle)	{
		document.onkeydown  = function(e)
		{	
			e = e || window.event;
			if(e.target)
				element=e.target;
			else if (e.srcElement)
				element=e.srcElement;
			if( element.nodeType==3)
				element=element.parentNode;
				
			if( e.ctrlKey == true || e.altKey == true || e.metaKey == true )
				return;
			
			var keyCode = (e.keyCode) ? e.keyCode : e.which;

			if (keyCode && (keyCode != 27 && (element.tagName == 'INPUT' || element.tagName == 'TEXTAREA') ) )
				return;
			switch(keyCode)
			{
				//  "c" key
				case 67:
					if (isFrontPage && isUserLoggedIn) {
						if (commentLoop) {
							jQuery('#'+commentsOnPost[currComment]).removeClass('keyselected');
							jQuery('#'+postsOnPage[currPost]).removeClass('commentloop').addClass('keyselected');
							commentLoop=false;
						}
						else {
							jQuery('#'+postsOnPage[currPost]).removeClass('keyselected');
							currPost=-1;
						}
					if (!isElementVisible("#postbox"))
						jQuery.scrollTo('#postbox', 50);
					jQuery("#posttext").focus();
					if (e.preventDefault)
						e.preventDefault();
					else
						e.returnValue = false;
					}
					break;
				//  "k" key
				case 75:	
					if (!commentLoop) {
						if (currPost > 0) {
							jQuery('#'+postsOnPage[currPost]).removeClass('keyselected').children('h4').trigger('mouseleave');							
							currPost--;
							if (0 != jQuery('#'+postsOnPage[currPost]).children('ul.commentlist').length && !hidecomments) {
								commentLoop = true;
								commentsOnPost.length = 0;
								jQuery('#'+postsOnPage[currPost]).find("li[id^='comment']").each(function() { 
									var thisId = jQuery(this).attr("id");
									commentsOnPost.push(thisId);
								});
								currComment = commentsOnPost.length-1;
								jQuery('#'+commentsOnPost[currComment]).addClass('keyselected').children('h4').trigger('mouseenter');			
								if (!isElementVisible('#'+commentsOnPost[currComment]))
									jQuery.scrollTo('#'+commentsOnPost[currComment], 150);
								return;
							}
							if (!isElementVisible('#'+postsOnPage[currPost]))
								jQuery.scrollTo('#'+postsOnPage[currPost], 50);
							jQuery('#'+postsOnPage[currPost]).addClass('keyselected').children('h4').trigger('mouseenter');							
						}
						else {
							if (currPost <= 0){
								jQuery('#'+postsOnPage[currPost]).removeClass('keyselected').children('h4').trigger('mouseleave');
								jQuery.scrollTo('#'+postsOnPage[postsOnPage.length-1], 50);
								currPost=postsOnPage.length-1;
								jQuery('#'+postsOnPage[currPost]).addClass('keyselected').children('h4').trigger('mouseenter');
								return;
							}
						}
					}
					else {
						if (currComment > 0) {
							jQuery('#'+commentsOnPost[currComment]).removeClass('keyselected').children('h4').trigger('mouseleave');			
							currComment--;
							if (!isElementVisible('#'+commentsOnPost[currComment]))
								jQuery.scrollTo('#'+commentsOnPost[currComment], 50);
							jQuery('#'+commentsOnPost[currComment]).addClass('keyselected').children('h4').trigger('mouseenter');			
						}
						else {
							if (currComment <= 0) {
								jQuery('#'+commentsOnPost[currComment]).removeClass('keyselected').children('h4').trigger('mouseleave');			
								jQuery('#'+postsOnPage[currPost]).addClass('keyselected').children('h4').trigger('mouseenter');
								if (!isElementVisible('#'+postsOnPage[currPost]))
									jQuery.scrollTo('#'+postsOnPage[currPost], 50);
								commentLoop=false;
								return
							}
						}
					}
					break;	
							
				// "j" key
				case 74:
					removeyellow();
					if (!commentLoop) {
						if (0 != jQuery('#'+postsOnPage[currPost]).children('ul.commentlist').length && !hidecomments) {
							jQuery.scrollTo('#'+postsOnPage[currPost], 150);
							commentLoop = true;
							currComment = 0;
							commentsOnPost.length = 0;
							jQuery('#'+postsOnPage[currPost]).find("li[id^='comment']").each(function() { 
								var thisId = jQuery(this).attr("id");
								commentsOnPost.push(thisId);
							});
							jQuery('#'+postsOnPage[currPost]).removeClass('keyselected').children('h4').trigger('mouseleave');							
							jQuery('#'+commentsOnPost[currComment]).addClass('keyselected').children('h4').trigger('mouseenter');			
							return;
						}
						if (currPost < postsOnPage.length-1) {
							jQuery('#'+postsOnPage[currPost]).removeClass('keyselected').children('h4').trigger('mouseleave');							
							currPost++;
							if (!isElementVisible('#'+postsOnPage[currPost]))
								jQuery.scrollTo('#'+postsOnPage[currPost], 50);
							jQuery('#'+postsOnPage[currPost]).addClass('keyselected').children('h4').trigger('mouseenter');							
						}
						else if (currPost >= postsOnPage.length-1){
							jQuery('#'+postsOnPage[currPost]).removeClass('keyselected').children('h4').trigger('mouseleave');							
							jQuery.scrollTo('#'+postsOnPage[0], 50);
							currPost=0;
							jQuery('#'+postsOnPage[currPost]).addClass('keyselected').children('h4').trigger('mouseenter');							
							return;
						}
					}
					else {
						if (currComment < commentsOnPost.length-1) {
							jQuery('#'+commentsOnPost[currComment]).removeClass('keyselected').children('h4').trigger('mouseleave');			
							currComment++;
							if (!isElementVisible('#'+commentsOnPost[currComment]))
								jQuery.scrollTo('#'+commentsOnPost[currComment], 50);
							jQuery('#'+commentsOnPost[currComment]).addClass('keyselected').children('h4').trigger('mouseenter');			
						}
						else if (currComment == commentsOnPost.length-1){
							jQuery('#'+commentsOnPost[currComment]).removeClass('keyselected').children('h4').trigger('mouseleave');			
							currPost++;
							jQuery('#'+postsOnPage[currPost]).addClass('keyselected').children('h4').trigger('mouseenter');							
							commentLoop=false;
							return
						}
					}
					break;	

				// "r" key
				case 82:
					if (!commentLoop) {
						jQuery('#'+postsOnPage[currPost]).removeClass('keyselected').children('h4').trigger('mouseleave');
						jQuery('#'+postsOnPage[currPost]).find('a.comment-reply-link:first').click();
					} else {
						jQuery('#'+commentsOnPost[currComment]).removeClass('keyselected').children('h4').trigger('mouseleave');
						jQuery('#'+commentsOnPost[currComment]).find('a.comment-reply-link').click();
					}
					removeyellow();
					if (e.preventDefault)
						e.preventDefault();
					else
						e.returnValue = false;
					break;
				// "e" key
				case 69:
					if (!commentLoop) {
						jQuery('#'+postsOnPage[currPost]).find('a.post-edit-link:first').click();
					}
					else {
						jQuery('#'+commentsOnPost[currComment]).find('a.comment-edit-link:first').click();
					}
					if (e.preventDefault)
						e.preventDefault();
					else
						e.returnValue = false;
					break;
				// "o" key
				case 79:
					jQuery("#togglecomments").click();
					if (typeof postsOnPage[currPost] != "undefined") {
						if (!isElementVisible('#'+postsOnPage[currPost])) {
							jQuery.scrollTo('#'+postsOnPage[currPost], 150);
						}
					}
					break;
					// "t" key
				case 84:
					jumpToTop();
					break;
				// "esc" key
				case 27:
					if (element.tagName == 'INPUT' || element.tagName == 'TEXTAREA') {
						jQuery('#cancel-comment-reply-link').click();
						jQuery(element).blur();
					}
					else {
						jQuery('#'+commentsOnPost[currComment]).each(function(e) {
							jQuery(this).removeClass('keyselected');
						});
						
						jQuery('#'+postsOnPage[currPost]).each(function(e) {
							jQuery(this).addClass('keyselected');
						});

						commentLoop=false;
						jQuery('#'+postsOnPage[currPost]).each(function(e) {
							jQuery(this).removeClass('keyselected');
						});
						currPost=-1;
					}
						jQuery('#help').hide();
						
					break;
				case 0,191:
					jQuery("#help").toggle();
					if (e.preventDefault)
						e.preventDefault();
					else
						e.returnValue = false;
					break;
			}
		}
	}

	
	//check if recent comments widget is loaded
	if	(jQuery("#recentcommentstable").length != 0) {
		lcwidget = true;
	}
	 
	
	//Activate Tag Suggestions for logged users on front page
	if (isFrontPage && prologueTagsuggest && isUserLoggedIn)
		jQuery('input[name="tags"]').suggest(ajaxUrl + '?action=prologue_ajax_tag_search', { delay: 350, minchars: 2, multiple: true, multipleSep: ", " } );
	
	
	//Actvate autgrow on textareas
	if (isFrontPage) {
		jQuery('#posttext, #comment').keypress(function(e) {autgrow(this, 3);});
		jQuery('#posttext, #comment').focus(function(e) {autgrow(this, 3);});
		}
	
	//Activate tooltips on recent-comments widget
	tooltip(jQuery("#recentcommentstable a.tooltip")); 
	

	// Catch new posts submit
	jQuery("#new_post").submit(function(trigger) { 
		newPost(trigger);
		trigger.preventDefault();
	});
	
	// Catch new comment submit
	if (isUserLoggedIn && !isSingle)
		jQuery("#commentform").submit(function(trigger) { 
			newComment(trigger);
			trigger.preventDefault();
			jQuery(this).parents("li").removeClass('replying');
			jQuery(this).parents('#respond').prev("li").removeClass('replying');
		});
	
	// Hide error messages on load
	jQuery('#posttext_error, #commenttext_error').hide();  
	

 //check if new comments or updates appear on scroll and fade out
	jQuery(window).scroll(function() { removeyellow(); });
});



/*
* Insert new comment inline
*/
function insertCommentInline(post_parent, comment_parent, comment_html, showNotification) {
	post_parent = "#"+post_parent;
	if (0 == comment_parent) {
			if (0 == jQuery(post_parent).children('ul.commentlist').length) {
				jQuery(post_parent).append('<ul class="commentlist inlinecomments"></ul>');
				commentsLists = jQuery("ul.commentlist");
			}
			jQuery(post_parent).children('ul.commentlist').append('<div class="temp_newComments_cnt"></div>');
			var newComment =  jQuery(post_parent).children('ul.commentlist').children('div.temp_newComments_cnt');
		}
		else {
			comment_parent='#comment-' + comment_parent;
			if (0 == jQuery(comment_parent).children('ul.children').length) {
				jQuery(comment_parent).append('<ul class="children"></ul>');
			}
			jQuery(comment_parent).children('ul.children').append('<div class="temp_newComments_cnt"></div>');
			var newComment =  jQuery(comment_parent).children('ul.children').children('div.temp_newComments_cnt');
		}
		
		newComment.html(comment_html);
		var newCommentsLi = newComment.children('li');
		newCommentsLi.addClass("newcomment");
		newCommentsLi.fadeIn();
		var cnt=newComment.contents();
		newComment.children('li.newcomment').each(function() {
			if (isElementVisible(this) && !showNotification) {
				jQuery(this).animate({backgroundColor:'transparent'}, {duration: 1500}, function(){
					jQuery(this).removeClass('newcomment');
				});
			}
		bindActions(this, 'comment');
		});
	newComment.replaceWith(cnt);
}


/*
* Insert and animate new comments into recent comments widget
*/

function insertCommentWidget(widgetHtml) {
	jQuery("#recentcommentstable tbody").prepend(widgetHtml);
	var newCommentsElement = jQuery("#recentcommentstable tbody tr:first");
	newCommentsElement.fadeIn("slow");
	jQuery("#recentcommentstable tbody tr:last").fadeOut("slow").remove();
	tooltip(jQuery("#recentcommentstable tbody tr:first td a.tooltip"));
	if (isElementVisible(newCommentsElement)) {
		jQuery(newCommentsElement).removeClass('newcomment');
	}
	//remove newcomment
}



/*
* Check for new posts and loads them inline
*/

function getPosts(showNotification){
	if (showNotification == null) {
		showNotification = true;
	}
	toggleUpdates('unewposts');
	var queryString=ajaxUrl +'?action=prologue_latest_posts&load_time=' + pageLoadTime + '&frontpage=' + isFirstFrontPage;
	ajaxCheckPosts = jQuery.getJSON(queryString, function(newPosts){
		if (typeof newPosts.numberofnewposts != "undefined") {
			pageLoadTime = newPosts.lastposttime;
			if (!isFirstFrontPage || (typeof newPosts.html == "undefined") ){
				newUnseenUpdates = newUnseenUpdates+newPosts.numberofnewposts;
				new_notification(newUnseenUpdates + " new update(s). <a href=\"" + wpUrl +"\">Go to homepage</a>");
				titleCount();
			}
			else
			{
				jQuery("#main > ul > li:first").before(newPosts.html);
				var newUpdatesLi = jQuery("#main > ul > li.newupdates");
				newUpdatesLi.slideDown();
				var counter=0;
				jQuery('#posttext_error, #commenttext_error').hide();  
				newUpdatesLi.each(function() { 
					//add post to postsOnPageQS  list
					var thisId = jQuery(this).attr("id");
					vpost_id = thisId.substring(thisId.indexOf('-')+1);
					postsOnPageQS+= "&vp[]=" + vpost_id; 
					if (!(thisId in postsOnPage))
						postsOnPage.unshift(thisId);
					//Bind actions to new elements
					bindActions(this, 'post');
					if (isElementVisible(this) && !showNotification) {
						jQuery(this).animate({backgroundColor:'transparent'}, 2500, function(){
							jQuery(this).removeClass('newupdates');
							titleCount();
						});
					}
					counter++;
				});
				if (counter >= newPosts.numberofnewposts && showNotification) {
					var updatemsg = isElementVisible('#main > ul >li:first') ? "" :  "<a href=\"#\"  onclick=\"jumpToTop();\" \">Jump to top</a>" ;
					new_notification(counter + " new update(s). " + updatemsg);
					titleCount();
				}
			}
		}
	});
	//Turn updates back on
	toggleUpdates('unewposts');
}



/*
* Check for new comments and loads them inline and into the recent-comments widgets
*/


function getComments(showNotification){
	if (showNotification == null) {
		showNotification = true;
	}
	toggleUpdates('unewcomments');
	var queryString=ajaxUrl +'?action=prologue_latest_comments&load_time=' + pageLoadTime + '&lcwidget=' + lcwidget;
	queryString += postsOnPageQS;
	ajaxCheckComments = jQuery.getJSON(queryString, function(newComments){
		if (newComments !=0) {
			jQuery.each(newComments.comments, function(i,comment){
				pageLoadTime = newComments.lastcommenttime;
				if (comment.widgetHtml) {
					insertCommentWidget(comment.widgetHtml);
				}
				if (comment.html!='') {
					var thisParentId = 'prologue-'+comment.postID;
					insertCommentInline(thisParentId, comment.commentParent, comment.html, showNotification);
				}
			});
			if (showNotification) {
				new_notification(newComments.numberofnewcomments + ' new comment(s)');
			}
		}
	});	
	toggleUpdates('unewcomments');
}


/*
* Submits a new post via ajax
*/
function newPost(trigger) {
	var thisForm=jQuery(trigger.target);
	var thisFormElements = jQuery('#posttext, #tags, :input',thisForm).not('input[type=hidden]');
	
	var submitProgress = thisForm.find('span.progress');
	
	var posttext=jQuery.trim(jQuery('#posttext').val());
	if ("" == posttext) {  
		jQuery("label#posttext_error").text('This field is required').show().focus();
		return false;
	}  
	toggleUpdates('unewposts');
	if (typeof ajaxCheckPosts != "undefined")
		ajaxCheckPosts.abort();
	jQuery("label#posttext_error").hide();  
	thisFormElements.attr('disabled', true); 
	thisFormElements.addClass('disabled');
	
	submitProgress.show();
	var tags = jQuery('#tags').val();
	var dataString = {action: 'prologue_new_post', _ajax_post:nonce, posttext: posttext, tags:tags};
	var errorMessage = '';
	jQuery.ajax({  
		type: "POST",  
		url: ajaxUrl ,  
		data: dataString,  
		success: function(result) {
			if ("0" == result)
				errorMessage="An error has occured, your post was not posted.";
			
			jQuery('#posttext').val('');
			jQuery('#tags').val('Tag it');
			if(errorMessage != '')
				new_notification(errorMessage);
			
			if (jQuery.suggest)
				jQuery('ul.ac_results').css('display', 'none'); //hide tag suggestion box if displayed
			
			if (isFirstFrontPage && result!="0") {
				getPosts(false);
			} else if (!isFirstFrontPage && result!="0") {
				new_notification('You update has been posted');
			}
			submitProgress.fadeOut();
			thisFormElements.attr('disabled', false); 
			thisFormElements.removeClass('disabled');
		  }  
	});
	thisFormElements.blur(); 
	toggleUpdates('unewposts');
}


function new_notification(message) {
	jQuery("#notify").stop(true).prepend(message + '<br/>')
		.fadeIn()
		.animate({opacity: 0.7}, 3000)
		.fadeOut('3000', function() {
			jQuery("#notify").html('');
		}).click(function() {
			jQuery(this).stop(true).fadeOut('fast').html('');
		});
}

/*
* Submits a new comment via ajax
*/
function newComment(trigger) {
	var thisForm=jQuery(trigger.target);
	var thisFormElements = jQuery('#comment, #comment-submit, :input', thisForm).not('input[type=hidden]');
	var submitProgress = thisForm.find('span.progress');
	var commenttext=jQuery.trim(jQuery('#comment').val());
	if ("" == commenttext) {  
		jQuery("label#commenttext_error").text('This field is required').show().focus();
		return false;  
	}  
	toggleUpdates('unewcomments');
	if (typeof ajaxCheckComments != "undefined")
		ajaxCheckComments.abort();
	jQuery("label#commenttext_error").hide(); 
	
	thisFormElements.attr('disabled', true); 
	thisFormElements.addClass('disabled');
	
	submitProgress.show();
	var comment_post_ID=jQuery('#comment_post_ID').val();
	var comment_parent=jQuery('#comment_parent').val();
	var dataString = {action: 'prologue_new_comment' , _ajax_post: nonce, comment: commenttext,  comment_parent: comment_parent, comment_post_ID: comment_post_ID};
	var errorMessage = '';
	jQuery.ajax({  
		type: "POST",  
		url: ajaxUrl ,  
		data: dataString,  
		success: function(result) {
			var lastComment = jQuery("#respond").prev("li");
			if (isNaN(result) || 0==result || 1==result)
				errorMessage=result;
			jQuery('#comment').val('');
			if (errorMessage != "")
				new_notification(errorMessage);
			getComments(false);
			
			submitProgress.fadeOut();
			jQuery("#respond").slideUp('fast');
			toggleUpdates('unewcomments');
			
			thisFormElements.attr('disabled', false);
			thisFormElements.removeClass('disabled');
		  }
	});
}

function new_notification(message) {
	jQuery("#notify").stop(true).prepend(message + '<br/>')
		.fadeIn()
		.animate({opacity: 0.7}, 2000)
		.fadeOut('1000', function() {
			jQuery("#notify").html('');
		}).click(function() {
			jQuery(this).stop(true).fadeOut('fast').html('');
		});
}


/*
* Handles tooltips for the recent-comment widget
* param: anchor link
*/
function tooltip(alink){	
	xOffset = 10;
	yOffset = 20;			
	alink.hover(function(e){			
		this.t = this.title;
		this.title = "";									  
		jQuery("body").append("<div id='tooltip'>"+ this.t +"</div>");
		jQuery("#tooltip")
			.css("top",(e.pageY - yOffset) + "px")
			.css("left",(e.pageX + xOffset) + "px")
			.fadeIn("fast");		
    },
	function(){
		this.title = this.t;		
		jQuery("#tooltip").remove();
    });	
	alink.mousemove(function(e){
		jQuery("#tooltip")
			.css("top",(e.pageY - yOffset) + "px")
			.css("left",(e.pageX + xOffset) + "px");
	});			
};



function isElementVisible(elem)
{
	if (!elem) {
        // Element not found.
        return false;
    }	
    var docViewTop = jQuery(window).scrollTop();
    var docViewBottom = docViewTop + jQuery(window).height();

    var elemTop = jQuery(elem).offset().top;
    var elemBottom = elemTop + jQuery(elem).height();
	var isVisible = ((elemBottom >= docViewTop) && (elemTop <= docViewBottom)  && (elemBottom <= docViewBottom) &&  (elemTop >= docViewTop) );
    return isVisible;
}


function toggleUpdates(updater){
	switch (updater) {
		case "unewposts":
			if (0 == getPostsUpdate) {
				getPostsUpdate = setInterval("getPosts()", updateRate);	
			}
			else {
				clearInterval(getPostsUpdate);
				getPostsUpdate='0'
			}
			break;
			
		case "unewcomments":
			if (0 == getCommentsUpdate) {
				getCommentsUpdate = setInterval("getComments()", updateRate);
			}
			else {
				clearInterval(getCommentsUpdate);
				getCommentsUpdate='0'
			}
			break;	
	}
}

function titleCount() {
	if (isFirstFrontPage) {
		var n = jQuery('#main ul:first li.newupdates').length;
	}
	else {
		var n=newUnseenUpdates;
	}
	if ((n) <= 0) {
		if (document.title.match(/\((\d+)\)/)) {
			document.title = document.title.substring((document.title.indexOf(')')+2));
		}
	}
	else {
		if (document.title.match(/\((\d+)\)/)) {
			document.title = document.title.replace(/\((\d+)\)/ , "(" + n + ")" );
		}
		else {
			document.title = '(1) ' + document.title;
		}
	}
}

function autgrow(textarea, min) {
	var linebreaks = textarea.value.match(/\n/g);
	if (linebreaks!=null && linebreaks.length+1 >= min) {
		textarea.rows = (linebreaks.length+1);
	}
	else {
		textarea.rows =  min;
	}
}
function jumpToTop() {
	jQuery.scrollTo('#main', 150);
}

function bindActions(element, type) {
	jQuery(element).find('a.comment-reply-link').click(function() {
		
		jQuery('#main li').removeClass('replying');
		jQuery(this).parents("li").eq(0).addClass('replying');
		jQuery("#respond").show();
		jQuery("#respond").addClass('replying').show();
		jQuery("#comment").focus();
	});	
	
	switch (type) {
		case "comment" :
			var thisCommentEditArea;
			if (inlineEditComments != 0 && isUserLoggedIn) {
				thisCommentEditArea = jQuery(element).find('div.comment-edit');
				thisCommentEditArea.editable(ajaxUrl, {event: 'edit',loadurl: ajaxUrl + '?action=prologue_load_comment&_inline_edit=' + nonce, id: 'comment_ID',name: 'comment_content', type    : 'autogrow', cssclass: 'textedit', rows: '3', indicator : '<img src="' + templateDir +'/i/indicator.gif">',loadtext: 'Loading...', cancel: 'Cancel', submit  : 'Save',	tooltip   : '', width: '90%', onblur: 'ignore', submitdata: {action:'prologue_inline_comment_save',_inline_edit: nonce}});	 
				jQuery(element).find('a.comment-edit-link').click(function() {
					thisCommentEditArea.trigger("edit");
					return false;
				});
			}	

		jQuery(".single #postlist li > div.postcontent, .single #postlist li > h4, li[id^='prologue'] > div.postcontent, li[id^='comment'] > div.commentcontent, li[id^='prologue'] > h4, li[id^='comment'] > h4").hover(function() {
			jQuery(this).parents("li").eq(0).addClass('selected');
		}, function() {
			jQuery(this).parents("li").eq(0).removeClass('selected');
		});
			break;
			
		case "post" :
			var thisPostEditArea;
			if (inlineEditPosts!=0 && isUserLoggedIn) {
				thisPostEditArea = jQuery(element).children('div.editarea');
				thisPostEditArea.editable(ajaxUrl, {event: 'edit',loadurl: ajaxUrl + '?action=prologue_load_post&_inline_edit=' + nonce, id: 'post_ID', name: 'content', type    : 'autogrow', cssclass: 'textedit',rows: '3', indicator : '<img src="' + templateDir +'/i/indicator.gif">', loadtext: 'Loading...', cancel: 'Cancel', submit  : 'Save', tooltip   : '', width: '90%', onblur: 'ignore', submitdata: {action:'prologue_inline_save', _inline_edit: nonce}});
	
				jQuery(element).find('a.post-edit-link').click(function() {
					thisPostEditArea.trigger("edit");
					return false;
				});
			}
			
			
		jQuery(".single #postlist li > div.postcontent, .single #postlist li > h4, li[id^='prologue'] > div.postcontent, li[id^='comment'] > div.commentcontent, li[id^='prologue'] > h4, li[id^='comment'] > h4").hover(function() {
			jQuery(this).parents("li").eq(0).addClass('selected');
		}, function() {
			jQuery(this).parents("li").eq(0).removeClass('selected');
		});

		break
	}


}
