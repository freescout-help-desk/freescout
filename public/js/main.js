var fs_sidebar_menu_applied = false;
var fs_loader_timeout;
var fs_processing_send_reply = false;
var fs_processing_save_draft = false;
var fs_connection_errors = 0;
var fs_editor_change_timeout = -1;
// For how long to remember conversation note drafts
var fs_keep_conversation_notes = 30; // days
var fs_draft_autosave_period = 12; // seconds
var fs_reply_changed = false;

// Ajax based notifications;
var poly;

// Default validation options
// https://devhints.io/parsley
window.ParsleyConfig = window.ParsleyConfig || {};
$.extend(window.ParsleyConfig, {
	excluded: '.note-codable, [data-parsley-exclude]',
    errorClass: 'has-error',
    //successClass: 'has-success',
    classHandler: function(ParsleyField) {
        return ParsleyField.$element.parents('.form-group:first');
    },
    errorsContainer: function(ParsleyField) {
    	var help_block = ParsleyField.$element.parent().children('.help-block:first');

    	if (help_block) {
    		return help_block;
    	} else {
    		return ParsleyField.$element;
    		//return ParsleyField.$element.parents('.form-group:first');
    	}
    },
    errorsWrapper: '<div class="help-block"></div>',
    errorTemplate: '<div></div>'
});

// Push notifications
/*Push.config({
    serviceWorker: './customServiceWorker.js', // Sets a custom service worker script
    fallback: function(payload) {
        // Code that executes on browsers with no notification support
        // "payload" is an object containing the 
        // title, body, tag, and icon of the notification 
    }
});*/

// Configuring editor

var EditorAttachmentButton = function (context) {
	var ui = $.summernote.ui;

	// create button
	var button = ui.button({
		contents: '<i class="glyphicon glyphicon-paperclip"></i>',
		tooltip: Lang.get("messages.upload_attachments"),
		container: 'body',
		click: function () {
			var element = document.createElement('div');
			element.innerHTML = '<input type="file" multiple>';
			var fileInput = element.firstChild;

			fileInput.addEventListener('change', function() {
				if (fileInput.files) {
					for (var i = 0; i < fileInput.files.length; i++) {
						editorSendFile(fileInput.files[i], true);	
		            }
			    }
			});

			fileInput.click();
		}
	});

	return button.render();   // return button as jquery object
}
var EditorSavedRepliesButton = function (context) {
	var ui = $.summernote.ui;

	// create button
	var button = ui.button({
		contents: '<i class="glyphicon glyphicon-comment"></i>',
		tooltip: Lang.get("messages.saved_replies"),
		container: 'body',
		click: function () {
			alert('todo: implement saved replies');
		}
	});

	return button.render();   // return button as jquery object
}
var EditorSaveDraftButton = function (context) {
	var ui = $.summernote.ui;

	// create button
	var button = ui.button({
		className: 'note-btn-save-draft',
		contents: '<i class="glyphicon glyphicon-ok"></i>',
		tooltip: Lang.get("messages.save_draft"),
		container: 'body',
		click: function () {
			saveDraft(true);
		}
	});

	return button.render();   // return button as jquery object
}
var EditorDiscardButton = function (context) {
	var ui = $.summernote.ui;

	// create button
	var button = ui.button({
		contents: '<i class="glyphicon glyphicon-trash"></i>',
		tooltip: Lang.get("messages.discard"),
		container: 'body',
		click: function () {
			discardDraft();
		}
	});

	return button.render();   // return button as jquery object
}
var EditorInsertVarButton = function (context) {
	var ui = $.summernote.ui;

	// todo: fallback=
	var contents = 
		'<select class="form-control summernote-inservar" tabindex="-1">'+
		    '<option value="">'+Lang.get("messages.insert_var")+' ...</option>'+
		    '<optgroup label="'+Lang.get("messages.mailbox")+'">'+
		        '<option value="{%mailbox.email%}">'+Lang.get("messages.email")+'</option>'+
		        '<option value="{%mailbox.name%}">'+Lang.get("messages.name")+'</option>'+
		    '</optgroup>'+
		    '<optgroup label="'+Lang.get("messages.conversation")+'">'+
		        '<option value="{%conversation.number%}">'+Lang.get("messages.number")+'</option>'+
		    '</optgroup>'+
		    '<optgroup label="'+Lang.get("messages.customer")+'">'+
		        '<option value="{%customer.fullName%}">'+Lang.get("messages.full_name")+'</option>'+
		        '<option value="{%customer.firstName%}">'+Lang.get("messages.first_name")+'</option>'+
		        '<option value="{%customer.lastName%}">'+Lang.get("messages.last_name")+'</option>'+
		        '<option value="{%customer.email%}">'+Lang.get("messages.email_addr")+'</option>'+
		    '</optgroup>'+
	    '</select>';

	// create button
	var button = ui.button({
		contents: contents,
		tooltip: Lang.get("messages.insert_var"),
		container: 'body'
	});

	return button.render();   // return button as jquery object
}

$(document).ready(function(){

	triggersInit();

    // Submenu
    $('.sidebar-menu-toggle').click(function(event) {
    	event.stopPropagation();
		$(this).parent().children('.sidebar-menu:first').toggleClass('active');
		$(this).toggleClass('active');
		if (!fs_sidebar_menu_applied) {
			$('body').click(function() {
				$('.sidebar-menu, .sidebar-menu-toggle').removeClass('active');
			});
		}
	});

	// Floating alerts
	fsFloatingAlertsInit();

	// Editor
	(function($) {
		if (typeof($.summernote) != "undefined") {
			// DIV instead of P
			// https://github.com/summernote/summernote/issues/546
			// https://github.com/summernote/summernote/issues/702
			// This causes TypeError: Cannot read property 'childNodes' of undefined
			//$.summernote.dom.emptyPara = "<div><br></div>";

			$.summernote.lang['en-US'].image.dragImageHere = Lang.get("messages.drag_image_file");
			$.summernote.lang['en-US'].image.dropImage = Lang.get("messages.drag_image_file");
		}
	})(jQuery);

	polycastInit();
	webNotificationsInit();
});

function triggersInit()
{
	// Tooltips
    $('[data-toggle="tooltip"]').tooltip({container: 'body'});

    // Popover
    $('[data-toggle="popover"]').popover({
	    container: 'body'
	});

	// Modal windows
	$('a[data-trigger="modal"]').click(function(e) {
    	showModal($(this));
    	e.preventDefault();
	});
}

function mailboxUpdateInit(from_name_custom)
{
	$(document).ready(function(){
		
		summernoteInit('#signature');

	    $('#from_name').change(function(event) {
			if ($(this).val() == from_name_custom) {
				$('#from_name_custom_container').removeClass('hidden');
			} else {
				$('#from_name_custom_container').addClass('hidden');
			}
		});
	});
}

// Delete mailbox
function deleteMailboxModal(modal)
{
	modal.children().find('.button-delete-mailbox:first').click(function(e) {
		var button = $(this);
	    button.button('loading');
		fsAjax(
			{
				action: 'delete_mailbox',
				mailbox_id: getGlobalAttr('mailbox_id'),
				password: $('.delete-mailbox-pass:visible:first').val()
			}, 
			laroute.route('mailboxes.ajax'),
			function(response) {
				if (typeof(response.status) != "undefined" && response.status == "success") {
					window.location.href = laroute.route('mailboxes');
					return;
				} else {
					showAjaxError(response);
					button.button('reset');
				}
			}, true
		);
		e.preventDefault();
	});
}

// Init summernote editor with default settings
// 
// https://github.com/Studio-42/elFinder/wiki/Integration-with-Multiple-Summernote-%28fixed-functions%29
// https://stackoverflow.com/questions/21628222/summernote-image-upload
// https://www.kerneldev.com/2018/01/11/using-summernote-wysiwyg-editor-with-laravel/
// https://gist.github.com/abr4xas/22caf07326a81ecaaa195f97321da4ae
function summernoteInit(selector)
{
	$(selector).summernote({
		minHeight: 120,
		dialogsInBody: true,
		disableResizeEditor: true,
		followingToolbar: false,
		disableDragAndDrop: true,
		toolbar: [
		    // [groupName, [list of button]]
		    ['style', ['bold', 'italic', 'underline', 'color', 'ul', 'ol', 'link', 'codeview']],
		    ['actions-select', ['insertvar']]
		],
		buttons: {
		    insertvar: EditorInsertVarButton
		},
	    callbacks: {
		    onInit: function() {
		    	// Remove statusbar
		    	$('.note-statusbar').remove();

		    	// Insert variables
				$(selector).parent().children().find('.summernote-inservar:first').on('change', function(event) {
					$(selector).summernote('insertText', $(this).val());
					$(this).val('');
				});
		    }
	    }
	});
}

function permissionsInit()
{
	$(document).ready(function(){
	    $('.sel-all').click(function(event) {
			$("#permissions-fields input").attr('checked', 'checked');
		});
		$('.sel-none').click(function(event) {
			$("#permissions-fields input").removeAttr('checked');
		});
	});
}

function mailboxConnectionInit(out_method_smtp)
{
	$(document).ready(function(){
	    $(':input[name="out_method"]').on('change', function(event) {
	    	var method = $(':input[name="out_method"]:checked').val();
			$('.out_method_options').addClass('hidden');
			$('#out_method_'+method+'_options').removeClass('hidden');

			if (parseInt(method) == parseInt(out_method_smtp)) {
				$('#out_method_'+out_method_smtp+'_options :input').attr('required', 'required');
			} else {
				$('#out_method_'+out_method_smtp+'_options :input').removeAttr('required');
			}
		});

	    $('#send-test-trigger').click(function(event) {
	    	var button = $(this);
	    	button.button('loading');
	    	fsAjax(
				{
					action: 'send_test',
					mailbox_id: getGlobalAttr('mailbox_id'),
					to: $('#send_test').val()
				}, 
				laroute.route('mailboxes.ajax'),
				function(response) {
					if (typeof(response.status) != "undefined" && response.status == 'success') {
						showFloatingAlert('success', Lang.get("messages.email_sent"));
					} else {
						showAjaxError(response);
					}
					button.button('reset');
				}, 
				true
			);
		});
	});
}

function mailboxConnectionIncomingInit()
{
	$(document).ready(function(){
	    $('#check-connection').click(function(event) {
	    	var button = $(this);
	    	button.button('loading');
	    	fsAjax(
				{
					action: 'fetch_test',
					mailbox_id: getGlobalAttr('mailbox_id')
				}, 
				laroute.route('mailboxes.ajax'),
				function(response) {
					if (typeof(response.status) != "undefined" && response.status == 'success') {
						showFloatingAlert('success', Lang.get("messages.connection_established"));
					} else {
						showAjaxError(response);
					}
					button.button('reset');
				}, 
				true
			);
		});
	});
}

function mailSettingsInit()
{
	$(document).ready(function(){
	    $(':input[name="settings[mail_driver]"]').on('change', function(event) {
	    	var method = $(':input[name="settings[mail_driver]"]').val();

			$('.mail_driver_options').addClass('hidden');
			$('#mail_driver_options_'+method).removeClass('hidden');

			if (parseInt(method) == 'smtp') {
				$('#mail_driver_options_smtp :input').attr('required', 'required');
			} else {
				$('#mail_driver_options_smtp :input').removeAttr('required');
			}
		});
	});
}

function userCreateInit()
{
	$(document).ready(function(){
	    $('#send_invite').on('change', function(event) {
	    	if ($(this).is(':checked')) {
	    		$('.no-send-invite').addClass('hidden');
	    		$('#password').removeAttr('required');
	    	} else {
				$('.no-send-invite').removeClass('hidden');
				$('#password').attr('required', 'required');
	    	}
		});
	});
}

function logsInit()
{
	$(document).ready(function() {
	    $('#table-logs').DataTable({
	   		"ordering": false,
	   		"paging": false,
	   		"info": false,
	   		"searching": false
	    });
	} );
}

function multiInputInit()
{
	$(document).ready(function() {
	    $('.multi-add').click(function() {
	    	var clone = $(this).parents('.multi-container:first').children('.multi-item:first').clone(true, true);
	    	clone.find(':input').val('');
	    	clone.find('.help-block').remove();
	    	clone.removeClass('has-error');
	    	clone.insertAfter($(this).parents('.multi-container:first').children('.multi-item:last'));
		});
		$('.multi-remove').click(function() {
	    	if ($(this).parents('.multi-container:first').children('.multi-item').length > 1) {
	    		$(this).parents('.multi-item:first').remove();
	    	} else {
	    		$(this).parents('.multi-item:first').children(':input').val('');
	    	}
		});
	} );
}

function fsAjax(data, url, success_callback, no_loader, error_callback)
{
    // Setup AJAX
	ajaxSetup();

	// Show loader
	if (typeof(no_loader) == "undefined" || !no_loader) {
		loaderShow(true);
	}

	if (typeof(error_callback) == "undefined" || !error_callback) {
		error_callback = function() {
			showFloatingAlert('error', Lang.get("messages.ajax_error"));
			loaderHide();
			// Buttons
			$(".btn[data-loading-text!='']:disabled").button('reset');
			// Links
			$(".btn.disabled[data-loading-text!='']").button('reset');
		};
	}

	// If this is conversation ajax request, add folder_id to the URL
	if (url.indexOf('/conversation/') != -1) {
		var folder_id = getQueryParam('folder_id');
		if (folder_id) {
			url += '?folder_id='+folder_id;
		}
	}

	$.ajax({
		url: url,
		method: 'post',
		dataType: 'json',
		data: data,
		success: success_callback,
		error: error_callback
   });
}

// Show loader
function loaderShow(delay)
{
	if (typeof(delay) != "undefined" && delay) {
		fs_loader_timeout = setTimeout(function() {
			$("#loader-main").fadeIn();
	    }, 1000);
	} else {
		$("#loader-main").fadeIn();
	}
}

function loaderHide()
{
	$("#loader-main").hide();
	clearTimeout(fs_loader_timeout);
}

// Display floating alerts
function fsFloatingAlertsInit()
{
	var alerts = $(".alert-floating:hidden");
			
	alerts.each(function(i, el) {
		// Stack alerts
		var top = 0;
		$(".alert-floating:visible").each(function(sub_i, sub_el) {
			top = top + $(sub_el).position().top + $(sub_el).outerHeight(true);
		});

		if (top) {
			$(el).css('top', top+'px');
		}
		$(el).css('display', 'flex');


		var close_after = 7000;
		if (!$(el).hasClass('alert-danger')) {
			// This has to be less than Conversation::UNDO_TIMOUT
			close_after = 10000;
		}
		setTimeout(function(){
		    el.remove(); 
		}, close_after);
	});

	if (alerts.length) {
		setTimeout(function(){
		    $('body').click(function() {
				alerts.remove();
			});
		}, 2000);
	}
}

function showFloatingAlert(type, msg)
{
	var icon = 'ok';
	var alert_class = 'success';

	if (type == 'error') {
		icon = 'exclamation-sign';
		alert_class = 'danger';
	}

	var html = '<div class="alert alert-'+alert_class+' alert-floating">'+
    	'<i class="glyphicon glyphicon-'+icon+'"></i>'+
        '<div>'+msg+'</div>'+
        '</div>';
    $('body:first').append(html);
    fsFloatingAlertsInit();
}

function conversationInit()
{
	$(document).ready(function(){

		// Change conversation assignee
	    jQuery(".conv-user li > a").click(function(e){
			if (!$(this).hasClass('active')) {
				fsAjax({
					action: 'conversation_change_user',
					user_id: $(this).attr('data-user_id'),
					conversation_id: getGlobalAttr('conversation_id')
				}, 
				laroute.route('conversations.ajax'),
				function(response) {
					if (typeof(response.status) != "undefined" && response.status == 'success') {
						if (typeof(response.redirect_url) != "undefined") {
							window.location.href = response.redirect_url;
						} else {
							window.location.href = '';
						}
					} else if (typeof(response.msg) != "undefined") {
						showFloatingAlert('error', response.msg);
						loaderHide();
					} else {
						showFloatingAlert('error', Lang.get("messages.error_occured"));
						loaderHide();
					}
				});
			}
			e.preventDefault();
		});

		// Change conversation status
	    jQuery(".conv-status li > a").click(function(e){
			if (!$(this).hasClass('active')) {
				var status = $(this).attr('data-status');
				// Restore conversation button does not have a status
				if (!status) {
					return;
				}
				fsAjax({
					action: 'conversation_change_status',
					status: status,
					conversation_id: getGlobalAttr('conversation_id')
				}, 
				laroute.route('conversations.ajax'),
				function(response) {
					if (typeof(response.status) != "undefined" && response.status == 'success') {
						if (typeof(response.redirect_url) != "undefined") {
							window.location.href = response.redirect_url;
						} else {
							window.location.href = '';
						}
					} else if (typeof(response.msg) != "undefined") {
						showFloatingAlert('error', response.msg);
					} else {
						showFloatingAlert('error', Lang.get("messages.error_occured"));
					}
					loaderHide();
				});
			}
			e.preventDefault();
		});

	    // Reply
	    jQuery(".conv-reply").click(function(e){
	    	// We don't allow to switch between reply and note, as it creates multiple drafts
	    	if ($(".conv-reply-block").hasClass('hidden') /* || $(this).hasClass('inactive')*/) {
	    		// Show
	    		// To prevent browser autocomplete, clean body
	    		if (!$('.conv-action.inactive:first').length) {
	    			setReplyBody('');
	    		}

	    		// Set assignee in case it has been changed in the Note editor
	    		var default_assignee = $(".conv-reply-block").children().find(":input[name='user_id']:first option[data-default='true']").attr('value');
	    		if (default_assignee) {
	    			$(".conv-reply-block").children().find(":input[name='user_id']:first").val(default_assignee);
	    		}

				showReplyForm();
			} /*else {
				// Hide
				$(".conv-action-block").addClass('hidden');
				$(".conv-action").removeClass('inactive');
			}*/
			e.preventDefault();
		});

		// Add note
	    jQuery(".conv-add-note").click(function(e){
	    	if ($(".conv-reply-block").hasClass('hidden')  /*|| $(this).hasClass('inactive')*/) {
	    		// Show
				$(".conv-action-block").addClass('hidden');
				$(".conv-reply-block").removeClass('hidden')
					.addClass('conv-note-block')
					.children().find(":input[name='is_note']:first").val(1);
				$(".conv-reply-block").children().find(":input[name='thread_id']:first").val('');
				//$(".conv-reply-block").children().find(":input[name='body']:first").val('');
				
				// If this is unassigned conversation, we need to set Assignee=Anyone
				if (getConvData('user_id') == '-1') {
					$(".conv-reply-block").children().find(":input[name='user_id']:first").val('-1');
				}

				$(".conv-action").addClass('inactive');
				$(this).removeClass('inactive');
				//$('#body').summernote("code", '');
				$('#body').summernote('focus');
			} /*else {
				// Hide
				$(".conv-action-block").addClass('hidden');
				$(".conv-action").removeClass('inactive');
			}*/
			e.preventDefault();
		});

		// View Send Log
	    jQuery(".thread-send-log-trigger").click(function(e){
	    	var thread_id = $(this).parents('.thread:first').attr('data-thread_id');
	    	if (!thread_id) {
	    		return;
	    	}
			e.preventDefault();
		});

		// Edit draft
		jQuery(".edit-draft-trigger").click(function(e){
			var button = $(this);
			var thread_container = button.parents('.thread:first');

			fsAjax({
					action: 'load_draft',
					thread_id: thread_container.attr('data-thread_id')
				}, 
				laroute.route('conversations.ajax'),
				function(response) {
					loaderHide();
					if (typeof(response.status) != "undefined" && response.status == 'success') {
						response.data.is_note = '';
						showReplyForm(response.data);
						// Show all drafts
						$('.thread.thread-type-draft').show();
						// Hide current draft
						thread_container.hide();
						$("html, body").animate({ scrollTop: $('.navbar:first').height() }, "slow");
					} else {
						showAjaxError(response);
					}
				}
			);
			e.preventDefault();
		});
		
		// Discard draft
		jQuery(".discard-draft-trigger").click(function(e){
			discardDraft($(this).parents('.thread:first').attr('data-thread_id'));
			e.preventDefault();
		});

	    // Delete conversation
	    jQuery(".conv-delete").click(function(e){
	    	var confirm_html = '<div>'+
			'<div class="text-center">'+
			'<div class="text-larger margin-top-10">'+Lang.get("messages.confirm_delete_conversation")+'</div>'+
			'<div class="form-group margin-top">'+
    		'<button class="btn btn-primary delete-conversation-ok">'+Lang.get("messages.delete")+'</button>'+
    		'<button class="btn btn-link" data-dismiss="modal">'+Lang.get("messages.cancel")+'</button>'+
    		'</div>'+
    		'</div>'+
    		'</div>';

			showModalDialog(confirm_html, {
				on_show: function(modal) {
					modal.children().find('.delete-conversation-ok:first').click(function(e) {
						modal.modal('hide');
						fsAjax(
							{
								action: 'delete_conversation',
								conversation_id: getGlobalAttr('conversation_id')
							}, 
							laroute.route('conversations.ajax'),
							function(response) {
								if (typeof(response.status) != "undefined" && response.status == "success" 
									&& typeof(response.redirect_url) != "undefined"
								) {
									window.location.href = response.redirect_url;
									return;
								} else {
									showAjaxError(response);
								}
								loaderHide();
							}
						);
						e.preventDefault();
					});
				}
			});
		});

		starConversationInit();
		maybeShowStoredNote();
		maybeShowDraft();
	});
}

// Get current conversation assignee
function getConvData(field)
{
	if (field == 'user_id') {
		return $('.conv-user:first li.active a:first').attr('data-user_id');
	}
	return null;
}

function showReplyForm(data)
{
	$(".conv-action-block").addClass('hidden');
	$(".conv-reply-block").removeClass('hidden')
		.removeClass('conv-note-block')
		.children().find(":input[name='is_note']:first").val('');
	$(".conv-reply-block :input[name='thread_id']:first").val('');
	
	// When switching from note to reply, body has to be preserved
	//$(".conv-reply-block").children().find(":input[name='body']:first").val(body_val);
	$(".conv-action").addClass('inactive');
	$(".conv-reply:first").removeClass('inactive');

	if (typeof(data) != "undefined" && data) {
		for (field in data) {
			$(".conv-reply-block form:first :input[name='"+field+"']").val(data[field]);
			if (field == 'body') {
				// Display body value in editor
				$('#body').summernote("code", data[field]);
			}
		}
	}

	if (!$('#to').length) {
		$('#body').summernote('focus');
	}
}

function getGlobalAttr(attr)
{
	return $("body:first").attr('data-'+attr);
}

// Initialize conversation body editor
function convEditorInit()
{
	$('#body').summernote({
		minHeight: 120,
		dialogsInBody: true,
		dialogsFade: true,
		disableResizeEditor: true,
		followingToolbar: false,
		toolbar: [
		    // [groupName, [list of button]]
		    ['style', ['attachment', 'bold', 'italic', 'underline', 'ul', 'ol', 'link', 'picture', 'codeview', 'savedreplies']],
		    ['actions', ['savedraft', 'discard']],
		],
		buttons: {
		    attachment: EditorAttachmentButton,
		    savedreplies: EditorSavedRepliesButton,
		    savedraft: EditorSaveDraftButton,
		    discard: EditorDiscardButton
		},
		callbacks: {
	 		onImageUpload: function(files) {
	 			if (!files) {
	 				return;
	 			}
	            for (var i = 0; i < files.length; i++) {
					editorSendFile(files[i]);	
	            }
	        },
	        onBlur: function() {
	        	onReplyBlur();
		    },
		    onChange: function(contents, $editable) {
		    	// Return if reply body is empty and never changed before
		    	if (!contents && !fs_reply_changed) {
		    		return;
		    	}
		    	onReplyChange();
		    }
	    }
	});
	var html = $('#editor_bottom_toolbar').html();
	$('.note-statusbar').addClass('note-statusbar-toolbar form-inline').html(html);

	// Track changes to save draft
	$("#to, #cc, #bcc, #subject").on('keyup keypress', function(event) {
		onReplyChange();
	});
	$("#to, #cc, #bcc, #subject").blur(function(event) {
	    onReplyBlur();
	});

	// Autosave draft periodically
	autosaveDraft();
}

function autosaveDraft()
{
	saveDraft(false, true);
	setTimeout(function(){ autosaveDraft() }, fs_draft_autosave_period*1000);
}

function ajaxSetup()
{
	$.ajaxSetup({
		headers: {
	    	'X-CSRF-TOKEN': getCsrfToken()
		}
	});
}

function onReplyChange()
{	
	// Mark draft as unsaved
	if (fs_editor_change_timeout && fs_editor_change_timeout != -1) {
		return;
	}	

	fs_editor_change_timeout = setTimeout(function(){
		// Do not save note
		/*if ($(".form-reply:first :input[name='is_note']:first").val()) {
			return;
		}*/
	
		$('.form-reply:first .note-actions .note-btn:first').removeClass('text-success');
		fs_editor_change_timeout = null;
		fs_reply_changed = true;
	}, 100);
}

// Save reply draft or note on form focus out
function onReplyBlur()
{
	// If start saving draft immediately, then when Send Reply is clicked
	// two ajax requests will be sent at the same time.
	setTimeout(function() {
		// Do not save if user clicked Send Reply button	
		if (fs_processing_send_reply) {
			return;
		}

		// Save only after changing
		//if (!fs_editor_change_timeout || fs_editor_change_timeout == null) {
		if ($(".form-reply:first :input[name='is_note']:first").val()) {
			// Save note
			rememberNote();
		} else {
  			saveDraft(false, true);
  		}
	  	//}
	  }, 500);
}

// Generate random unique ID
function generateDummyId()
{
	// Math.random should be unique because of its seeding algorithm.
	// Convert it to base 36 (numbers + letters), and grab the first 9 characters
	// after the decimal.
	return '_' + Math.random().toString(36).substr(2, 9);
}

// Save file uploaded in editor
function editorSendFile(file, attach) 
{
	if (!file || typeof(file.type) == "undefined") {
		return false;
	}

	var attachments_container = $(".attachments-upload:first");
	var attachment_dummy_id = generateDummyId();

	ajaxSetup();

 	if (typeof(attach) == "undefined") {
		attach = false;
	}

	// Images are embedded by default, other files attached
	if (file.type.indexOf('image/') == -1) {
		attach = true;
	}

	// Show loader
	if (attach) {
		var attachment_html = '<li class="atachment-upload-'+attachment_dummy_id+'"><img src="'+Vars.public_url+'/img/loader-tiny.gif" width="16" height="16"/> <a href="javascript:void(0);" class="break-words disabled" target="_blank">'+file.name+'<span class="ellipsis">…</span> </a> <span class="text-help">('+formatBytes(file.size)+')</span> <i class="glyphicon glyphicon-remove" onclick="removeAttachment(\''+attachment_dummy_id+'\')"></i></li>';
		$('.attachments-upload:first ul:first').append(attachment_html);
		attachments_container.show();
	} else {
		loaderShow();
	}

	data = new FormData();
	data.append("file", file);
	if (attach) {
		data.append("attach", 1);
	} else {
		data.append("attach", 0);
	}
	$.ajax({
		url: laroute.route('conversations.upload'),
		data: data,
		cache: false,
		contentType: false,
		processData: false,
		type: 'POST',
		success: function(response){
			if (typeof(response.url) == "undefined" || !response.url) {
				showFloatingAlert('error', Lang.get("messages.error_occured"));
				loaderHide();
				removeAttachment(attachment_dummy_id);
				return;
			}
			// Finish loading
			if (attach) {
				$('li.atachment-upload-'+attachment_dummy_id+':first').addClass('attachment-loaded');
				$('li.atachment-upload-'+attachment_dummy_id+':first a').removeClass('disabled').attr('href', response.url);
			} else {
				loaderHide();
			}
			if (typeof(response.status) == "undefined" || response.status != "success") {
				showAjaxError(response);
				removeAttachment(attachment_dummy_id);
				return;
			}
			if (attach) {
				
			} else {
				// Embed image
				$('#body').summernote('insertImage', response.url, function (image) {
					var editor_width = $('.note-editable:first:visible').width();
					if (image.width() > editor_width-85) {
						image.css('width', editor_width-85);
					}
					image.attr('width', image.css('width').replace('px', ''));
				});
			}
			if (typeof(response.attachment_id) != "undefined" || response.attachment_id) {
				var input_html = '<input type="hidden" name="attachments_all[]" value="'+response.attachment_id+'" />';
				input_html += '<input type="hidden" name="attachments[]" value="'+response.attachment_id+'" class="atachment-upload-'+attachment_dummy_id+'" />';
				if (!attach) {
					input_html += '<input type="hidden" name="embeds[]" value="'+response.attachment_id+'" class="atachment-upload-'+attachment_dummy_id+'" />';
				}
				attachments_container.prepend(input_html);
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if (attach) {
				removeAttachment(attachment_dummy_id);
			} else {
				loaderHide();
			}
			console.log(textStatus+": "+errorThrown);
			showFloatingAlert('error', Lang.get("messages.error_occured"));
		}
	});
}

function removeAttachment(attachment_dummy_id)
{
	$('.atachment-upload-'+attachment_dummy_id).remove();
}


function formatBytes(size)
{
	precision = 2;
	size = parseInt(size);
    if (!isNaN(size) && size > 0) {
        base = Math.log(size) / Math.log(1024);
        suffixes = [' b', ' KB', ' MB', ' GB', ' TB'];

        return Math.round(Math.pow(1024, base - Math.floor(base)), precision)+''+suffixes[Math.floor(base)];
    } else {
        return size;
    }
}

// New conversation page
function newConversationInit()
{
	$(document).ready(function() {

		convEditorInit();

		// Show CC
	    $('.toggle-cc a:first').click(function() {
			$('.field-cc').removeClass('hidden');
			$(this).parent().remove();
		});

		// After send
		$('.dropdown-after-send a:lt(3)').click(function(e) {
			if (!$(this).parent().hasClass('active')) {
				$("#after_send").val($(this).attr('data-after-send'));
				$('.dropdown-after-send li').removeClass('active');
				$(this).parent().addClass('active');
			}
			e.preventDefault();
		});

		// After send
		$('.after-send-change').click(function(e) {
			showModal($(this));
		});

		// Send reply, new conversation or note
	    $(".btn-reply-submit").click(function(e) {
	    	// This is extra protection from double click on Send button
	    	// DOM operation are slow sometimes
	    	if (fs_processing_send_reply) {
	    		return;
	    	}
	    	fs_processing_send_reply = true;

	    	var button = $(this);

	    	// Validate before sending
	    	form = $(".form-reply:first");

	    	if (!form.parsley().validate()) {
	    		fs_processing_send_reply = false;
	    		return;
	    	}

	    	button.button('loading');

	    	data = form.serialize();
	    	data += '&action=send_reply';

			fsAjax(data, laroute.route('conversations.ajax'), function(response) {
				if (typeof(response.status) != "undefined" && response.status == 'success') {
					// Forget note
					if (button.hasClass('btn-add-note-text')) {
						forgetNote(getGlobalAttr('conversation_id'));
					}
					if (typeof(response.redirect_url) != "undefined") {
						window.location.href = response.redirect_url;
					} else {
						window.location.href = '';
					}
				} else {
					showAjaxError(response);
					button.button('reset');
				}
				loaderHide();
				fs_processing_send_reply = false;
			}, 
			true,
			function() {
				showFloatingAlert('error', Lang.get("messages.ajax_error"));
				loaderHide();
				button.button('reset');
				fs_processing_send_reply = false;
			});

			e.preventDefault();
		});
	});
}

function notificationsInit()
{
	$(document).ready(function() {
	    $('.sel-all').click(function(event) {
	    	if ($(this).is(':checked')) {
	    		if ($(this).val() == 'browser' && !Push.Permission.has()) {
	    			return;
	    		}
				$(".subscriptions-"+$(this).val()+" input").attr('checked', 'checked');
			} else {
				$(".subscriptions-"+$(this).val()+" input").removeAttr('checked');
			}
		});

		// Browser push-notifications permissions
		if (getGlobalAttr('own_profile')) {
			$('.sel-all[value="browser"], .subscriptions-browser input').click(function(event) {
				var checkbox = $(this);
		    	if ($(this).is(':checked')) {

		    		// Check protocol
		    		if (location.protocol != 'https:') {
		    			var alert_html = '<div>'+
							'<div class="text-center">'+
							'<div class="text-larger margin-top-10">'+Lang.get("messages.push_protocol_alert")+'</div>'+
							'<div class="form-group margin-top">'+
				    		'<button class="btn btn-primary reset-password-ok" data-dismiss="modal">OK</button>'+
			        		'</div>'+
			        		'</div>'+
			        		'</div>';

						showModalDialog(alert_html);

						checkbox.prop('checked', false);
						return;
		    		}

					if (!Push.Permission.has()) {
						Push.Permission.request(function() {}, function() {
							$.featherlight($('<img src="'+Vars.public_url+'/img/enable-push.png" />'), {});
							checkbox.prop('checked', false);
						});
					}
				}
			});
		}
	});
}

function getQueryParam(name, qs) {
	if (typeof(qs) == "undefined") {
		qs = document.location.search;
	}
    qs = qs.split('+').join(' ');

    var params = {},
        tokens,
        re = /[?&]?([^=]+)=([^&]*)/g;

    while (tokens = re.exec(qs)) {
        params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
    }

    if (typeof(params[name]) != "undefined") {
    	return params[name];
    } else {
    	return '';
    }
}

// Show bootstrap modal
function showModal(a, params)
{
    if (typeof(params) == "undefined") {
    	params = {};
    }

    if (typeof(a) == "undefined" || !a) {
    	// Create dummy link
    	a = $(document.createElement('a'));
    }

    var title = a.attr('data-modal-title');
    if (typeof(params.title) != "undefined") {
    	title = params.title;
    }
    if (title && title.charAt(0) == '#') {
        title = $(title).html();
    }
    if (!title) {
        title = a.text();
    }
    var remote = a.attr('data-remote');
    if (!remote) {
    	remote = a.attr('href');
    }
    var body = a.attr('data-modal-body');
    if (typeof(params.body) != "undefined") {
    	body = params.body;
    }
    var footer = a.attr('data-modal-footer');
    var no_close_btn = a.attr('data-no-close-btn');
    if (typeof(params.no_footer) == "undefined") {
    	params.no_footer = a.attr('data-modal-no-footer');
    }
    if (typeof(params.no_header) == "undefined") {
    	params.no_header = a.attr('data-modal-no-header');
    }
    if (typeof(params.no_fade) == "undefined") {
    	params.no_fade = a.attr('data-modal-no-fade');
    }
    var modal_class = a.attr('data-modal-class');
    if (typeof(modal_class) == "undefined") {
    	modal_class = '';
    }
    var on_show = a.attr('data-modal-on-show');
    if (typeof(params.on_show) != "undefined") {
    	on_show = params.on_show;
    }
    // Size: lg or sm
    if (typeof(params.size) == "undefined") {
    	params.size = a.attr('data-modal-size');
    }
    if (typeof(params.width_auto) == "undefined") {
    	params.width_auto = a.attr('data-modal-width-auto');
    }
    // Fit modal body into the screen
    var fit = a.attr('data-modal-fit');

    var modal;

    if (params.size) {
    	modal_class += ' modal-'+params.size;
    }
    if (params.width_auto) {
    	modal_class += ' modal-width-auto';
    }
    if (typeof(modal_class) == "undefined") {
    	modal_class = '';
    }

    var html = [
    '<div class="modal '+(params.no_fade == 'true' ? '' : 'fade')+'" tabindex="-1" role="dialog" aria-labelledby="jsmodal-label" aria-hidden="true">',
        '<div class="modal-dialog '+modal_class+'">',
            '<div class="modal-content">',
                '<div class="modal-header '+(params.no_header == 'true' ? 'hidden' : '')+'">',
                    '<button type="button" class="close" data-dismiss="modal" aria-label="'+Lang.get("messages.close")+'"><span aria-hidden="true">&times;</span></button>',
                    '<h3 class="modal-title" id="jsmodal-label">'+title+'</h3>',
                '</div>',
                '<div class="modal-body '+(fit == 'true' ? 'modal-body-fit' : '')+'"><div class="text-center modal-loader"><img src="'+Vars.public_url+'/img/loader-grey.gif" width="31" height="31"/></div></div>',
                '<div class="modal-footer '+(params.no_footer == 'true' ? 'hidden' : '')+'">',
                    (no_close_btn == 'true' ? '': '<button type="button" class="btn btn-default" data-dismiss="modal">'+Lang.get("messages.close")+'</button>'),
                    footer,
                '</div>',
            '</div>',
        '</div>',
    '</div>'].join('');
    modal = $(html);

    // if (typeof(onshow) !== "undefined") {
    //     modal.on('shown.bs.modal', onshow);
    // }

    modal.modal();

    if (body) {
    	var body_html = $(body).html();
    	if (!body_html) {
    		body_html = $('<div>'+body+'</div>').html()
    	}
        modal.children().find(".modal-body").html(body_html);
        if (on_show) {
        	if (typeof(window[on_show]) == "function") {
        		window[on_show](modal);
        	} else if (typeof(on_show) == "function") {
        		on_show(modal);
        	}
        }
    } else {
        setTimeout(function(){
            $.ajax({
                url: remote,
                success: function(html) {
                    modal.children().find(".modal-body").html(html);

			        if (on_show) {
			        	if (typeof(window[on_show]) == "function") {
			        		window[on_show](modal);
			        	} else if (typeof(on_show) == "function") {
			        		on_show(modal);
			        	}
			        }
                },
                error: function(data) {
                    modal.children().find(".modal-body").html('<p class="alert alert-danger">'+Lang.get("messages.error_occured")+'</p>');
                }
            });
        }, 500);
    }
}

// Show floating error message on ajax error
function showAjaxError(response)
{
	var msg = '';

	if (typeof(response.msg) != "undefined") {
		msg = response.msg;
	} else if (typeof(response.message) != "undefined") {
		// Standard Laravel error message is returned in [message]
		msg = response.message;
	}
	if (msg) {
		showFloatingAlert('error', response.msg);
	} else {
		showFloatingAlert('error', Lang.get("messages.error_occured"));
	}
}

// Save default redirect
function saveAfterSend(el)
{
	var button = $(el);
	button.button('loading');

	var value = $(el).parents('.modal-body:first').children().find('[name="after_send_default"]:first').val();
	data = {
		value: value,
		mailbox_id: getGlobalAttr('mailbox_id'),
		action: 'save_after_send'
	};

	fsAjax(data, laroute.route('conversations.ajax'), function(response) {
		if (typeof(response.status) != "undefined" && response.status == 'success') {
			// Show selected option in the dropdown
			$('.dropdown-after-send [data-after-send='+value+']:first').click();
			showFloatingAlert('success', Lang.get("messages.settings_saved"));
			$('.modal').modal('hide');
		} else {
			showAjaxError(response);
		}
		button.button('reset');
	}, true);
}

function viewMailboxInit()
{
	conversationPagination();
	starConversationInit();
}

function searchInit()
{
	// Open all links in new window
	$(".conv-row a").attr('target', '_blank');
	conversationPagination();
	starConversationInit();
}

function conversationPagination()
{
	$(".table-conversations .pager-nav").click(function(e){

		var filter = {
			q: getQueryParam('q') // For search
		};
		var table = $(this).parents('.table-conversations:first');

		var datas = table.data();
		for (data_name in datas) {
			if (/^filter_/.test(data_name)) {
				filter[data_name.replace(/^filter_/, '')] = datas[data_name];
			}
		}

		fsAjax(
			{
				action: 'conversations_pagination',
				mailbox_id: getGlobalAttr('mailbox_id'),
				folder_id: getGlobalAttr('folder_id'),
				filter: filter,
				page: $(this).attr('data-page')
			}, 
			laroute.route('conversations.ajax'),
			function(response) {
				if (typeof(response.status) != "undefined" && response.status == 'success') {
					if (typeof(response.html) != "undefined") {
						$(".table-conversations:first").html(response.html);
						conversationPagination();
						starConversationInit();
						triggersInit();
					}
				} else {
					showAjaxError(response);
				}
				loaderHide();
			}
		);
	
		e.preventDefault();
	});	
}

function changeCustomerInit()
{
	$(document).ready(function() {
		var input = $(".change-customer-input");
		input.select2({
			ajax: {
				url: laroute.route('customers.ajax_search'),
				dataType: 'json',
				delay: 250,
				cache: true,
				data: function (params) {
					return {
						q: params.term,
						exclude_email: input.attr('data-customer_email')
						//use_id: true
					};
				}/*,
				beforeSend: function(){
			    	showSelect2Loader(input);
			    },
			    complete: function(){
			    	hideSelect2Loader(input);
			    }*/
			},
			containerCssClass: "select2-multi-container", // select2-with-loader
     		dropdownCssClass: "select2-multi-dropdown",
			dropdownParent: $('.modal-dialog:visible:first'),
			multiple: true,
			maximumSelectionLength: 1,
			placeholder: input.attr('placeholder'),
			minimumInputLength: 1
		});

		// Show confirmation dialog on customer select
		input.on('select2:selecting', function (e) {
			if (typeof(e.params) == "undefined" || typeof(e.params.args.data) == "undefined") {
				console.log(e);
				return;
			}
			var data = e.params.args.data;
			//el.select2('close');

			var confirm_html = '<div>'+
				'<div class="text-center">'+
				'<div class="text-larger margin-top-10">'+Lang.get("messages.confirm_change_customer", {customer_email: data.id})+'</div>'+
				'<div class="form-group margin-top">'+
        		'<button class="btn btn-primary change-customer-ok" data-customer_email='+data.id+'>OK</button>'+
        		'<button class="btn btn-link" data-dismiss="modal">'+Lang.get("messages.cancel")+'</button>'+
        		'</div>'+
        		'</div>'+
        		'</div>';

			showModal(null, {
				body: confirm_html,
				width_auto: 'true',
				no_header: 'true',
				no_footer: 'true',
				no_fade: 'true',
				size: 'sm',
				on_show: function(modal) {
					modal.children().find('.change-customer-ok:first').click(function(e) {
						fsAjax({
								action: 'conversation_change_customer',
								customer_email: $(this).attr('data-customer_email'),
								conversation_id: getGlobalAttr('conversation_id')
							}, 
							laroute.route('conversations.ajax'), 
							function(response) {
								if (typeof(response.status) != "undefined" && response.status == 'success') {
									if (typeof(response.redirect_url) != "undefined") {
										window.location.href = response.redirect_url;
									} else {
										window.location.href = '';
									}
								} else {
									showAjaxError(response);
									loaderHide();
								}
							}
						);
					});
				}
			});
		    e.preventDefault();
		});
	});
}

function showModalDialog(body, options)
{
	var standard_options = {
		body: body,
		width_auto: 'true',
		no_header: 'true',
		no_footer: 'true',
		no_fade: 'true'
		//size: 'sm'
	};
	if (typeof(options) == "undefined") {
		options = {};
	}
	options = Object.assign(standard_options, options);
	
	showModal(null, options);
}

/*function showSelect2Loader(input)
{
	input.closest('.select2-with-loader:first').addClass('loading');
}

function hideSelect2Loader(input)
{
	input.closest('.select2-with-loader:first').removeClass('loading');
}*/

function userProfileInit()
{
	// Send or re-send invite
	$(".send-invite-trigger, .resend-invite-trigger").click(function(e){
		var button = $(this);
		var is_resend = false;

		button.button('loading');

		if (button.hasClass('resend-invite-trigger')) {
			is_resend = true;
		}

		fsAjax(
			{
				action: 'send_invite',
				user_id: getGlobalAttr('user_id')
			}, 
			laroute.route('users.ajax'),
			function(response) {
				if (typeof(response.status) != "undefined" && response.status == 'success') {
					if (is_resend) {
						showFloatingAlert('success', Lang.get("messages.invite_resent"));
					} else {
						showFloatingAlert('success', Lang.get("messages.invite_sent"));
					}
				} else {
					showAjaxError(response);
				}
				button.button('reset');
			},
			true
		);
	
		e.preventDefault();
	});

	// Reset password
	$(".reset-password-trigger").click(function(e){

		var button = $(this);

		var confirm_html = '<div>'+
			'<div class="text-center">'+
			'<div class="text-larger margin-top-10">'+Lang.get("messages.confirm_reset_password")+'</div>'+
			'<div class="form-group margin-top">'+
    		'<button class="btn btn-primary reset-password-ok">OK</button>'+
    		'<button class="btn btn-link" data-dismiss="modal">'+Lang.get("messages.cancel")+'</button>'+
    		'</div>'+
    		'</div>'+
    		'</div>';

		showModalDialog(confirm_html, {
			on_show: function(modal) {
				modal.children().find('.reset-password-ok:first').click(function(e) {
					button.button('loading');
					modal.modal('hide');
					fsAjax(
						{
							action: 'reset_password',
							user_id: getGlobalAttr('user_id')
						}, 
						laroute.route('users.ajax'),
						function(response) {
							showAjaxResult(response);
							button.button('reset');
						}
					);
				});
			}
		});

		e.preventDefault();
	});	

	// Delete profile photo
	$("#user-photo-delete").click(function(e) {
		var button = $(this);

		var confirm_html = '<div>'+
			'<div class="text-center">'+
			'<div class="text-larger margin-top-10">'+Lang.get("messages.confirm_delete_photo")+'</div>'+
			'<div class="form-group margin-top">'+
    		'<button class="btn btn-primary reset-password-ok">'+Lang.get("messages.delete")+'</button>'+
    		'<button class="btn btn-link" data-dismiss="modal">'+Lang.get("messages.cancel")+'</button>'+
    		'</div>'+
    		'</div>'+
    		'</div>';

		showModalDialog(confirm_html, {
			on_show: function(modal) {
				modal.children().find('.reset-password-ok:first').click(function(e) {
					button.button('loading');
					modal.modal('hide');
					fsAjax(
						{
							action: 'delete_photo',
							user_id: getGlobalAttr('user_id')
						}, 
						laroute.route('users.ajax'),
						function(response) {
							$('#user-profile-photo').remove();
							button.button('reset');
						}, 
						true
					);
				});
			}
		});
		e.preventDefault();
	});

	// Delete user
    jQuery("#delete-user-trigger").click(function(e){
    	var confirm_html = '<div>'+
		'<div class="text-center">'+
			'<div class="text-large margin-top-10">'+Lang.get("messages.confirm_delete_user", {delete: '<span class="text-danger">DELETE</span>'})+'</div>'+
			'<div class="col-sm-6 col-sm-offset-3 margin-top margin-bottom">'+
				'<div class="input-group">'+
				    '<input type="text" class="form-control input-delete-user" placeholder="'+Lang.get("messages.type_delete", {delete: '&quot;DELETE&quot;'})+'">'+
				    '<span class="input-group-btn">'+
				    	'<button class="btn btn-danger button-delete-user" disabled="disabled"><i class="glyphicon glyphicon-ok"></i></button>'+
				    '</span>'+
				'</div>'+
			'</div>'+
			'<div class="clearfix"></div>'+
		'</div>'+
		'</div>';

		showModalDialog(confirm_html, {
			width_auto: false,
			on_show: function(modal) {
				modal.children().find('.input-delete-user:first').on('keyup keypress', function(e) {
					if ($(this).val() == 'DELETE') {
						modal.children().find('.button-delete-user:first').removeAttr('disabled');
					} else {
						modal.children().find('.button-delete-user:first').attr('disabled', 'disabled');
					}
				});

				modal.children().find('.button-delete-user:first').click(function(e) {
					modal.modal('hide');
					fsAjax(
						{
							action: 'delete_user',
							user_id: getGlobalAttr('user_id')
						}, 
						laroute.route('users.ajax'),
						function(response) {
							if (typeof(response.status) != "undefined" && response.status == "success") {
								window.location.href = laroute.route('users');
								return;
							} else {
								showAjaxError(response);
							}
							loaderHide();
						}
					);
					e.preventDefault();
				});
			}
		});
	});
}

function showAjaxResult(response)
{
	loaderHide();
	
	if (typeof(response.status) != "undefined" && response.status == 'success') {
		if (typeof(response['success_msg']) != "undefined") {
			showFloatingAlert('success', response['success_msg']);
		}
	} else {
		showAjaxError(response);
	}
}

function getCsrfToken()
{
	return $('meta[name="csrf-token"]:first').attr('content');
}

// Real-time website notifications in the menu and browser push notifications
function polycastInit()
{
	var auth_user_id = getGlobalAttr('auth_user_id');
	if (!auth_user_id) {
		return;
	}

	// create the connection
    poly = new Polycast('/polycast', {
        token: getCsrfToken()
    });

    // register callbacks for connection events
    // poly.on('connect', function(obj){
    //     console.log('connect event fired!');
    //     console.log(obj);
    // });

    // poly.on('disconnect', function(obj){
    //     console.log('disconnect event fired!');
    //     console.log(obj);
    // });

    // subscribe to channel(s)
    var channel = poly.subscribe('private-App.User.'+auth_user_id);

    // fired when event on channel is received
    channel.on('App\\Events\\RealtimeBroadcastNotificationCreated', function(data, event){
        /*
            event.id = mysql id
            event.channels = array of channels
            event.event = event name
            event.payload = object containing event data (same as the first data argument)
            event.created_at = timestamp from mysql
            event.requested_at = when the ajax request was performed
            event.delay = the delay in seconds from when the request was made and when the event happened (used internally to delay callbacks)
        */
        // console.log(data);
        // console.log(event);
        
        if (typeof(event.data) != "undefined") {
        	// Show notification in the menu
        	if (typeof(event.data.web) != "undefined" 
	        	&& typeof(event.data.web.html) != "undefined" 
	        	&& event.data.web.html
	        ) {
	        	showMenuNotification(event.data.web.html);
	        }

	        // Browser push-notification
	        if (typeof(event.data.browser) != "undefined" 
	        	&& typeof(event.data.browser.text) != "undefined" 
	        	&& event.data.browser.text
	        ) {
	        	showBrowserNotification(event.data.browser.text, event.data.browser.url);
	        }
	    }
    });

    // at any point you can disconnect
    //poly.disconnect();

    // and when you disconnect, you can again at any point reconnect
    //poly.reconnect();
}

// Show notification in the menu
function showMenuNotification(html)
{
	$(html).prependTo($(".web-notifications-list:first"));

	var counter = $('.web-notifications-count:first');
	if (counter) {
		var count = parseInt($('.web-notifications-count:first').text());
		if (isNaN(count)) {
			count = 0;
		}
		count++;
		counter.text(count).removeClass('hidden');
	}

	$('.web-notifications-mark-read:first').removeClass('hidden');

	// Remove double TODAY
	var first_date = $('.web-notification-date:first');
	$('.web-notification-date[data-date="'+first_date.attr('data-date')+'"]:gt(0)').remove();
	$('.web-notifications .dropdown-toggle:first').addClass('has-unread');
}

// Show browser push-notification
function showBrowserNotification(text, url)
{
	// Push notification body limits: https://www.mobify.com/insights/web-push-character-limits/
	// If we place text into the body and keep title empty, it is being cropped in Chrome
	Push.create(text, {
	    body: "",
	    icon: Vars.public_url+'/img/logo-icon-white-300.png',
	    tag: url,
	    timeout: 5000,
	    //requireInteraction: true
	    onClick: function () {
	    	if (url) {
	        	var win = window.open(url, '_blank');
  				win.focus();
  				this.close();
  				//Push.close(url);
	        }
	    }
	});
}

// Take notifications bell out of the dropdown menu
function takeNotificationsOut()
{
	if ($(window).width() >= 768) {
		// Move to the menu
		var container = $('.navbar-header .web-notifications:first');
		if (container) {
			container.prependTo(".navbar-right:first");
		}
	} else {
		// Move to the header
		var container = $('.navbar-right .web-notifications:first');
		if (container) {
			container.prependTo(".navbar-header:first");
		}
	}
}

// Display notification in the menu
function webNotificationsInit()
{
	// Take notifications bell out of the dropdown menu
	takeNotificationsOut();
	$(window).on('resize', function(){
		takeNotificationsOut();
	});

	// Load more
	var button = $('.web-notification-more:first .btn:first');

	button.click(function(e) {
		button.button('loading');

		var wn_page = parseInt(button.attr('data-wn_page'));
		if (isNaN(wn_page)) {
			wn_page = 2;
		}

		fsAjax(
			{
				action: 'web_notifications',
				wn_page: wn_page
			}, 
			laroute.route('users.ajax'),
			function(response) {
				if (typeof(response.status) != "undefined" && response.status == "success") {
					$(response.html).insertBefore(button.parent());
				} else {
					showAjaxError(response);
				}
				if (typeof(response.has_more_pages) == "undefined" || !response.has_more_pages) {
					button.parent().hide();
				}
				button.button('reset');
				button.attr('data-wn_page', wn_page+1);
			},
			true
		);
		e.preventDefault();
		e.stopPropagation();
	});

	// Mark all as read
	$('.web-notifications-mark-read:first').click(function(e) {
		var mark_button = $(this);

		if (mark_button.hasClass('disabled')) {
			return;
		}

		mark_button.button('loading');

		fsAjax(
			{
				action: 'mark_notifications_as_read'
			}, 
			laroute.route('users.ajax'),
			function(response) {
				if (typeof(response.status) != "undefined" && response.status == "success") {
					mark_button.remove();
					$('.web-notifications-count:first').addClass('hidden');
					$('.web-notification.is-unread').removeClass('is-unread');
					$('.web-notifications .dropdown-toggle.has-unread:first').removeClass('has-unread');
				} else {
					showAjaxError(response);
				}
				mark_button.button('reset');
			},
			true
		);
		e.preventDefault();
		e.stopPropagation();
	});

	// Mark notification as read on click
	// $('.web-notifications:first .web-notification a').click(function(e) {
	// 	$(this).parent().removeClass('is-unread');
	// });
	
}

function systemInit()
{
	if (location.protocol == 'https:') {
		$('#system-app-protocol').text('HTTPS');
	} else {
		var html = 'HTTP'+
			'<div class="alert alert-danger margin-top">'+Lang.get("messages.push_protocol_alert")+'</div>';
		$('#system-app-protocol').html(html);
	}
}

// Called from polycast
function maybeShowConnectionError()
{
	fs_connection_errors++;
	if (fs_connection_errors == 3) {
		showFloatingAlert('error', Lang.get("messages.lost_connection"));
	}
}

function maybeShowConnectionRestored()
{
	if (fs_connection_errors >= 3) {
		showFloatingAlert('success', Lang.get("messages.connection_restored"));
	}
	fs_connection_errors = 0;
}

/**
 * Save draft automatically, on reply change or on click.
 * Validation is not needed.
 */
function saveDraft(reload_page, no_loader)
{
	if (!reload_page && fs_processing_save_draft) {
		return;
	}
	// User clicked Send Reply button
	if (fs_processing_send_reply) {
		return;
	}

	fs_processing_save_draft = true;

	// Do not autosave draft if reply form has been closed
	var form = $(".form-reply:visible:first");
	if (!form || !form.length) {
		fs_processing_save_draft = false;
		return;
	}

	var button = form.children().find('.note-actions .note-btn:first');
	var new_conversation = false;

	if (typeof(no_loader) == "undefined") {
		no_loader = false;
	}

	// Are we sasving a draft of a new conversation
	if ($('#conv-layout-main .thread:first').length == 0) {
		new_conversation = true;
	}

	// Do not save unchanged draft
	// When replying click on Save draft always reloads conversation
	if ((new_conversation || !reload_page) && !fs_reply_changed) {
		fs_processing_save_draft = false;
		return;
	}

	data = form.serialize();
	data += '&action=save_draft';

	fsAjax(data, laroute.route('conversations.ajax'), function(response) {
		if (typeof(response.status) != "undefined" && response.status == 'success') {
			if (reload_page && !new_conversation) {
				// Reload the conversation
				window.location.href = '';
			} else {
				button.addClass('text-success');
				fs_reply_changed = false;
				// Show Saved
				var saved_text = form.children().find('.draft-saved:first');
				if (!saved_text.length || !saved_text.is(':visible')) {
					if (!saved_text.length) {
						saved_text = $('<span class="draft-saved">'+Lang.get("messages.saved")+'</span>');
						saved_text.insertBefore(button);
					} else {
						saved_text.show();
					}
					
					setTimeout(function() {
						saved_text.fadeOut(1000);
				    }, 4000);
				}
				// If conversation returned, set conversation info
				if (typeof(response.conversation_id) != "undefined" && response.conversation_id) {
					form.children(':input[name="conversation_id"][value=""]').val(response.conversation_id);
					form.children(':input[name="thread_id"]').val(response.thread_id);
					$('.conv-new-number:first').text(response.conversation_id);

					// Set URL if this is a new conversation
					if (new_conversation) {
						setUrl(laroute.route('conversations.view', {id: response.conversation_id}));
					}
				}
			}
		} else {
			showAjaxError(response);
		}
		loaderHide();
		fs_processing_save_draft = false;
	}, 
	no_loader,
	function() {
		showFloatingAlert('error', Lang.get("messages.ajax_error"));
		loaderHide();
		fs_processing_save_draft = false;
	});
}

function setUrl(url)
{
	if (window.history && typeof(window.history.replaceState) != "undefined") {
        window.history.replaceState({isMine:true}, 'title',  url);
    }
}

function discardDraft(thread_id)
{
	var confirm_html = '<div>'+
		'<div class="text-center">'+
		'<div class="text-larger margin-top-10">'+Lang.get("messages.confirm_discard_draft")+'</div>'+
		'<div class="form-group margin-top">'+
		'<button class="btn btn-primary discard-draft-confirm">'+Lang.get("messages.yes")+'</button>'+
		'<button class="btn btn-link" data-dismiss="modal">'+Lang.get("messages.cancel")+'</button>'+
		'</div>'+
		'</div>'+
		'</div>';

	// Discard note
	if ($(".form-reply:first :input[name='is_note']:first").val()) {
		showModalDialog(confirm_html, {
			on_show: function(modal) {
				modal.children().find('.discard-draft-confirm:first').click(function(e) {
					hideReplyEditor();
					setReplyBody('');
					forgetNote();
					modal.modal('hide');
				});
			}
		});
		return;
	}

	if (typeof(thread_id) == "undefined" || !thread_id) {
		thread_id = $('.form-reply :input[name="thread_id"]').val();
	}

	showModalDialog(confirm_html, {
		on_show: function(modal) {
			modal.children().find('.discard-draft-confirm:first').click(function(e) {
				fsAjax(
					{
						action: 'discard_draft',
						thread_id: thread_id
					}, 
					laroute.route('conversations.ajax'),
					function(response) {
						modal.modal('hide');
						if (typeof(response.status) != "undefined" && response.status == "success") {
							if (typeof(response.redirect_url) != "undefined" && response.redirect_url) {
								window.location.href = response.redirect_url;
								return;
							}
							var thread_container = $('#thread-'+thread_id+':visible');
							if (thread_container.length) {
								// Remove draft from conversation
								thread_container.remove();
							} else {
								// Hide editor
								hideReplyEditor();
								$(".conv-reply-block :input[name='to']:first").val(
									$(".conv-reply-block :input[name='to']:first option:first").val()
								);
								$(".conv-reply-block :input[name='cc']:first").val('');
								$(".conv-reply-block :input[name='bcc']:first").val('');
								setReplyBody('');
							}
						} else {
							showAjaxError(response);
						}
						loaderHide();
					}
				);
			});
		}
	});
}

function hideReplyEditor()
{
	$(".conv-action-block").addClass('hidden');
	$(".conv-action").removeClass('inactive');
}

function setReplyBody(text)
{
	$(".conv-reply-block :input[name='body']:first").val(text);
	$('#body').summernote("code", text);
}

// Star/unstar processing from the list or conversation
function starConversationInit()
{
	$('.conv-star').click(function(event) {
		var trigger = $(this);
		var conversation_id = getGlobalAttr('conversation_id');

		if (!conversation_id) {
			// In the list
			conversation_id = trigger.parents('.conv-row:first').attr('data-conversation_id');
		}
		if (!conversation_id) {
			// Something went wrong
			return false;
		}

		var sub_action = 'star';
		if (trigger.hasClass('glyphicon-star')) {
			sub_action = 'unstar';
		}
		fsAjax(
			{
				action: 'star_conversation',
				conversation_id: conversation_id,
				sub_action: sub_action
			}, 
			laroute.route('conversations.ajax'),
			function(response) {
				if (typeof(response.status) != "undefined" && response.status == "success") {
					// In the list there are two stars for desktop and monile
					if (trigger.parents('.conv-row:first').length) {
						trigger = trigger.parents('.conv-row:first').children().find('.conv-star');
					}
					if (sub_action == 'star') {
						trigger.addClass('glyphicon-star');
						trigger.removeClass('glyphicon-star-empty');
					} else {
						trigger.addClass('glyphicon-star-empty');
						trigger.removeClass('glyphicon-star');
					}
				} else {
					showAjaxError(response);
				}
			}, true
		);
	});
}

function switchToNote()
{
	$('.conv-add-note:first').click();
}

function rememberNote()
{
	var conversation_id = getGlobalAttr('conversation_id');
	if (!conversation_id) {
		return;
	}

	var note = $('#body').val();
	var note_plain = stripTags(note);

	var conversation_notes = loadNotesFromStorage(conversation_id);

	// Remove old items from browser storage
	for (var i in conversation_notes) {
		if (conversation_notes[i].time) {
			if (conversation_notes[i].time < (new Date()).getTime() - fs_keep_conversation_notes*24*60*60*1000) {
				delete conversation_notes[i];
			}
		}
	}

	if (!note || !note_plain.trim()) {
		delete conversation_notes[conversation_id];
	} else {
		// Remember current note
		conversation_notes[conversation_id] = {
			note: note,
			time: (new Date()).getTime()
		};
	}

	saveNoteToStorage(conversation_notes);
}

function maybeShowStoredNote()
{
	var conversation_id = getGlobalAttr('conversation_id');
	if (!conversation_id) {
		return;
	}
	// Get stored note fom browser storage
	var conversation_notes = loadNotesFromStorage(conversation_id);

	if (conversation_notes) {
		if (typeof(conversation_notes[conversation_id]) != 'undefined' &&
			typeof(conversation_notes[conversation_id].note) != 'undefined' &&
			conversation_notes[conversation_id].note.trim()
		) {
			setReplyBody(conversation_notes[conversation_id].note);
			switchToNote();
		}
	}
}

// Happens after Undo
function maybeShowDraft()
{
	var thread_id = getQueryParam('show_draft');

	if (!thread_id) {
		return;
	}

	fsAjax({
			action: 'load_draft',
			thread_id: thread_id
		}, 
		laroute.route('conversations.ajax'),
		function(response) {
			loaderHide();
			if (typeof(response.status) != "undefined" && response.status == 'success') {
				response.data.is_note = '';
				showReplyForm(response.data);
				$('#thread-'+thread_id).hide();
			} else {
				showAjaxError(response);
			}
		}
	);
}

function forgetNote(conversation_id)
{
	var conversation_id = getGlobalAttr('conversation_id');
	var conversation_notes = loadNotesFromStorage(conversation_id);
	if (conversation_notes && typeof(conversation_notes[conversation_id]) != 'undefined') {
		delete conversation_notes[conversation_id];
		saveNoteToStorage(conversation_notes);
	}
}

function saveNoteToStorage(conversation_notes)
{
	localStorageSet('conversation_notes', JSON.stringify(conversation_notes));
}

function loadNotesFromStorage(conversation_id)
{
	var conversation_notes_json = localStorageGet('conversation_notes');
	
	if (conversation_notes_json) {
		var conversation_notes = {};
		try {
			conversation_notes = JSON.parse(conversation_notes_json);
		} catch (e) {}
		if (conversation_notes && typeof(conversation_notes) == 'object') {
			return conversation_notes;
		} else {
			return {};
		}
	} else {
		return {};
	}
}

function localStorageSet(key, value)
{
	if (typeof(localStorage) != "undefined") {
		localStorage.setItem(key, value);
	} else {
		return false;
	}
}

function localStorageGet(key)
{
	if (typeof(localStorage) != "undefined") {
		return localStorage.getItem(key);
	} else {
		return false;
	}
}

function localStorageRemove(key)
{
	if (typeof(localStorage) != "undefined") {
		localStorage.removeItem(key);
	} else {
		return false;
	}
}

function stripTags(html)
{
	var div = document.createElement("div");
	div.innerHTML = html;
	var text = div.textContent || div.innerText || "";
	return text;
}

function htmlDecode(input){
	var e = document.createElement('div');
	e.innerHTML = input;
	// handle case of empty input
	return e.childNodes.length === 0 ? "" : e.childNodes[0].nodeValue;
}