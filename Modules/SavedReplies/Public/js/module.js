/**
 * Module's JavaScript.
 */

// Saved Replies button in reply editor
var EditorSavedRepliesButton = function (context) {
	var ui = $.summernote.ui;

 	//var items = [];
 	// $('.sr-dropdown-list:first li').each(function(i, el) {
 	// 	items.push({
 	// 		value: $(el).attr('data-id'),
	 //        text: $(el).text(),
	 //        is_parent: $(el).attr('data-is-parent')
 	// 	});
 	// });

	// create button
	var button = ui.buttonGroup([
		// We have to create button inside button group to have tooltip separate for button
	    ui.button({
	        className: 'dropdown-toggle',
	        contents: '<i class="glyphicon glyphicon-comment"></i>',
	        tooltip: Lang.get("messages.saved_replies"),
	        container: 'body',
	        data: {
	            toggle: 'dropdown'
	        }
	    }),
	    ui.dropdown({
	        className: 'dropdown-menu-right dropdown-saved-replies',
	        //checkClassName: ui.options.icons.menuCheck,
	        //items: items,
	        contents: $('#sr-dropdown-list').html(),
	        /*template: function (item) {
	            var html = item.text;
	            if (item.is_parent == '1') {
	            	html += ' <span class="caret"></span>';
	            }
	            return html;
	        },*/
	        click: function(e) {

	        	if (typeof(e.target) == "undefined") {
	        		return;
	        	}
	        	
	        	var target = $(e.target);

	        	if ($(e.target).hasClass('caret')) {
	        		target = target.parent();
	        	}

	        	var saved_reply_id = target.attr('data-value');
	        	if (saved_reply_id) {
	        		if (target.children('.caret:first').length) {
	        			// Expand children
	        			target.parent().parent().children('li[data-parent-id="'+saved_reply_id+'"]').toggleClass('hidden');

	        			// Hide all children
	        			if (target.parent().parent().children('li[data-parent-id="'+saved_reply_id+'"]:first').hasClass('hidden')) {
		        			target.parent().parent().children('li[data-parents]:visible').each(function(index, el){
		        				var parents = $(el).attr('data-parents').split(',');
		        				for (var i in parents) {
									if (parents[i] == saved_reply_id) {
		        						$(el).addClass('hidden');
		        						return;
		        					}
		        				}
		        			});
		        		}
	        			e.stopPropagation();
	        		} else {
		        		// Load saved reply
		        		fsAjax({
								action: 'get',
								saved_reply_id: saved_reply_id,
								conversation_id: getGlobalAttr('conversation_id')
							}, 
							laroute.route('mailboxes.saved_replies.ajax'), 
							function(response) {
								if (typeof(response.status) != "undefined" && response.status == 'success' &&
									typeof(response.text) != "undefined" && response.text) 
								{
									// Without wrapping div, <div><br></div> are added
									context.invoke('editor.pasteHTML', '<div>'+response.text+'</div>');
									//$('#body').summernote('pasteHTML', response.text);
									$('.form-reply:visible:first :input[name="saved_reply_id"]:first').val(saved_reply_id);
								} else {
									showAjaxError(response);
								}
								loaderHide();
							}
						);
					}
	        	} else {
	        		// Save this reply
	        		showModal({
	        			'remote': laroute.route('mailboxes.saved_replies.ajax_html', {'action': 'create'}),
	        			'size': 'lg',
	        			'title': Lang.get("messages.new_saved_reply"),
	        			'no_footer': true,
	        			'on_show': 'showSaveThisReply'
	        		});
	        	}

	        	e.preventDefault();
	        }
	    })
	]);

	var obj = button.render();

	// Add divider
	//obj.children().find('a[data-value="divider"]').parent().addClass('divider').children().first().remove();

	return obj;
}

fs_conv_editor_buttons['savedreplies'] = EditorSavedRepliesButton;
fs_conv_editor_toolbar[0][1].push('savedreplies');

function initSavedReplies()
{
	$(document).ready(function() {
		/*$('#saved-replies-index').sortable({
		    handle: '.handle',
		    forcePlaceholderSize: true 
		}).bind('sortupdate', function(e, ui) {
		    // ui.item contains the current dragged element.
		    var saved_replies = [];
		    $('#saved-replies-index > .panel').each(function(idx, el){
			    saved_replies.push($(this).attr('data-saved-reply-id'));
			});
			fsAjax({
					action: 'update_sort_order',
					saved_replies: saved_replies,
				}, 
				laroute.route('mailboxes.saved_replies.ajax'), 
				function(response) {
					showAjaxResult(response);
				}
			);
		});*/

		//summernoteInit('.saved-reply-text', {minHeight: 250, insertVar: true});

		// Update saved reply
		$('.saved-reply-save').click(function(e) {
			var saved_reply_id = $(this).attr('data-saved_reply_id');
			var name = $('#saved-reply-'+saved_reply_id+' :input[name="name"]').val();
			var button = $(this);
	    	button.button('loading');
			fsAjax({
					action: 'update',
					saved_reply_id: saved_reply_id,
					name: name,
					parent_saved_reply_id: $('#saved-reply-'+saved_reply_id+' :input[name="parent_saved_reply_id"]').val(),
					text: $('#saved-reply-'+saved_reply_id+' :input[name="text"]').val()
				}, 
				laroute.route('mailboxes.saved_replies.ajax'), 
				function(response) {
					if (typeof(response.status) != "undefined" && response.status == 'success' &&
						typeof(response.msg_success) != "undefined")
					{
						showFloatingAlert('success', response.msg_success);
						$('#saved-reply-'+saved_reply_id+' .panel-title a:first span:first span').text(name);

						if (typeof(response.refresh) != "undefined" && response.refresh) {
							window.location.href = '';
						}
					} else {
						showAjaxError(response);
					}
					button.button('reset');
					loaderHide();
				}
			);
		});

		// Delete saved reply
		$(".sr-delete-trigger").click(function(e){
			var button = $(this);

			showModalConfirm(Lang.get("messages.confirm_delete_saved_reply"), 'sr-delete-ok', {
				on_show: function(modal) {
					var saved_reply_id = button.attr('data-saved_reply_id');
					modal.children().find('.sr-delete-ok:first').click(function(e) {
						button.button('loading');
						modal.modal('hide');
						fsAjax(
							{
								action: 'delete',
								saved_reply_id: saved_reply_id
							}, 
							laroute.route('mailboxes.saved_replies.ajax'), 
							function(response) {
								showAjaxResult(response);
								button.button('reset');
								if ($('#saved-reply-'+saved_reply_id+' .panel-sortable:first').length) {
									window.location.href = '';
								} else {
									$('#saved-reply-'+saved_reply_id).remove();
								}
							}
						);
					});
				}
			}, Lang.get("messages.delete"));
			e.preventDefault();
		});

		// Sortable panels
		if ($('.saved-replies-tree').length) {
			var elements = sortable('.saved-replies-tree', {
			    handle: '.handle',
			    //forcePlaceholderSize: true 
			});
			for (var i in elements) {
				elements[i].addEventListener('sortupdate', function(e) {
				    // ui.item contains the current dragged element.
				    var saved_replies = [];
				    $(e.target).children('.panel').each(function(idx, el){
					    saved_replies.push($(this).attr('data-saved-reply-id'));
					});
					fsAjax({
							action: 'update_sort_order',
							saved_replies: saved_replies,
						}, 
						laroute.route('mailboxes.saved_replies.ajax'), 
						function(response) {
							showAjaxResult(response);
						}
					);
				});
			}
		}

		// On panel open
		$('.panel.panel-sortable').on('show.bs.collapse', function (e) {
		    summernoteInit('#'+e.currentTarget.id+' .saved-reply-text', {minHeight: 250, insertVar: true});
		})
	});
}

// Create saved reply
function initNewSavedReply(jmodal)
{
	$(document).ready(function(){
		// Show text
		summernoteInit('.modal-dialog .new-saved-reply-editor:visible:first textarea:first', {minHeight: 250, insertVar: true});

		// Process save
		$('.modal-content .new-saved-reply-save:first').click(function(e) {
			var button = $(this);
	    	button.button('loading');
	    	var name = $(this).parents('.modal-content:first').children().find(':input[name="name"]').val();
	    	var text = $(this).parents('.modal-content:first').children().find(':input[name="text"]').val();
			fsAjax({
					action: 'create',
					mailbox_id: getGlobalAttr('mailbox_id'),
					from_reply: getGlobalAttr('conversation_id'),
					name: name,
					text: text,
					parent_saved_reply_id: $(this).parents('.modal-content:first').children().find(':input[name="parent_saved_reply_id"]').val(),
				}, 
				laroute.route('mailboxes.saved_replies.ajax'), 
				function(response) {
					if (typeof(response.status) != "undefined" && response.status == 'success' &&
						typeof(response.id) != "undefined" && response.id)
					{

						if (typeof(response.msg_success) != "undefined" && response.msg_success) {
							// Show alert (in conversation)
							jmodal.modal('hide');
							showFloatingAlert('success', response.msg_success);
							loaderHide();

							// Add newly created saved reply to the list
							var li_html = '<li><a href="#" data-value="'+response.id+'">'+htmlEscape(name)+'</a></li>';
							$('.form-reply:first:visible .dropdown-saved-replies:first').children('li:last').prev().before(li_html);
						} else {
							// Reload page (in saved replies list)
							window.location.href = '';
						}
					} else {
						showAjaxError(response);
						loaderHide();
						button.button('reset');
					}
				}
			);
		});
	});
}

// Display modal and show reply text
function showSaveThisReply(jmodal)
{
	// Show text
	$('.modal-dialog .new-saved-reply-editor:visible:first textarea[name="text"]:first').val(getReplyBody());
	initNewSavedReply(jmodal);
}