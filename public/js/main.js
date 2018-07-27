var fs_sidebar_menu_applied = false;

// Configuring editor
var EditorAttachmentButton = function (context) {
	var ui = $.summernote.ui;

	// create button
	var button = ui.button({
		contents: '<i class="glyphicon glyphicon-paperclip"></i>',
		tooltip: Lang.get("messages.upload_attachments"),
		container: 'body',
		click: function () {
			alert('todo: implement attachments');
		}
	});

	return button.render();   // return button as jquery object
}
var EditorSavedRepliesButton = function (context) {
	var ui = $.summernote.ui;

	// create button
	var button = ui.button({
		contents: '<i class="glyphicon glyphicon-floppy-open"></i>',
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
		contents: '<i class="glyphicon glyphicon-ok-circle"></i>',
		tooltip: Lang.get("messages.save_draft"),
		container: 'body',
		click: function () {
			alert('todo: implement saving draft');
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
			alert('todo: implement discarding a draft');
		}
	});

	return button.render();   // return button as jquery object
}

$(document).ready(function(){

	// Tooltips
    $('[data-toggle="tooltip"]').tooltip({container: 'body'});

    // Popover
    $('[data-toggle="popover"]').popover({
	    container: 'body'
	});

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
});

function mailboxUpdateInit(from_name_custom)
{
	$(document).ready(function(){
		// https://github.com/Studio-42/elFinder/wiki/Integration-with-Multiple-Summernote-%28fixed-functions%29
		// https://stackoverflow.com/questions/21628222/summernote-image-upload
		// https://www.kerneldev.com/2018/01/11/using-summernote-wysiwyg-editor-with-laravel/
		// https://gist.github.com/abr4xas/22caf07326a81ecaaa195f97321da4ae
		$('#signature').summernote({
			height: 120,
			dialogsInBody: true,
			toolbar: [
			    // [groupName, [list of button]]
			    ['style', ['bold', 'italic', 'underline', 'ul', 'ol', 'link', 'codeview']],
			]
		});

	    $('#from_name').change(function(event) {
			if ($(this).val() == from_name_custom) {
				$('#from_name_custom_container').removeClass('hidden');
			} else {
				$('#from_name_custom_container').addClass('hidden');
			}
		});
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
	    $('#table-logs').DataTable();
	} );
}

function multiInputInit()
{
	$(document).ready(function() {
	    $('.multi-add').click(function() {
	    	var clone = $(this).parents('.multi-container:first').children('.multi-item:first').clone(true, true);
	    	clone.find(':input').val('');
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

function fsAjax(data, success_callback, error_callback, no_loader)
{
	var url = laroute.route('ajax');

    // Setup AJAX
	$.ajaxSetup({
		headers: {
	    	'X-CSRF-TOKEN': $('meta[name="csrf-token"]:first').attr('content')
		}
	});
	// Show loader
	if (typeof(no_loader) == "undefined" || !no_loader) {
		fsLoaderShow();
	}

	if (typeof(error_callback) == "undefined" || !error_callback) {
		error_callback = function() {
			fsShowFloatingAlert('error', Lang.get("messages.ajax_error"));
			fsLoaderHide();
		};
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

function fsLoaderShow()
{
	$("#loader-main").show();
}

function fsLoaderHide()
{
	$("#loader-main").hide();
}

// Display floating alerts
function fsFloatingAlertsInit()
{
	var alerts = $(".alert-floating:hidden");
			
	alerts.each(function(i, obj) {
		$(obj).css('display', 'flex');
		setTimeout(function(){
		    obj.remove(); 
		}, 7000);
	});

	if (alerts.length) {
		$('body').click(function() {
			alerts.remove();
		});
	}
}

function fsShowFloatingAlert(type, msg)
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
					conversation_id: fsGetGlobalAttr('conversation_id')
				}, function(response) {
					if (typeof(response.status) != "undefined" && response.status == 'success') {
						if (typeof(response.redirect_url) != "undefined") {
							window.location.href = response.redirect_url;
						} else {
							window.location.href = '';
						}
					} else if (typeof(response.msg) != "undefined") {
						fsShowFloatingAlert('error', response.msg);
					} else {
						fsShowFloatingAlert('error', Lang.get("messages.error_occured"));
					}
					fsLoaderHide();
				});
			}
			e.preventDefault();
		});

		// Change conversation status
	    jQuery(".conv-status li > a").click(function(e){
			if (!$(this).hasClass('active')) {
				fsAjax({
					action: 'conversation_change_status',
					status: $(this).attr('data-status'),
					conversation_id: fsGetGlobalAttr('conversation_id')
				}, function(response) {
					if (typeof(response.status) != "undefined" && response.status == 'success') {
						if (typeof(response.redirect_url) != "undefined") {
							window.location.href = response.redirect_url;
						} else {
							window.location.href = '';
						}
					} else if (typeof(response.msg) != "undefined") {
						fsShowFloatingAlert('error', response.msg);
					} else {
						fsShowFloatingAlert('error', Lang.get("messages.error_occured"));
					}
					fsLoaderHide();
				});
			}
			e.preventDefault();
		});
	});
}

function fsGetGlobalAttr(attr)
{
	return $("body:first").attr('data-'+attr);
}

// New conversation page
function newConversationInit()
{
	$(document).ready(function() {

		$('#body').summernote({
			height: 120,
			dialogsInBody: true,
			dialogsFade: true,
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
			}
		});

	    $('.toggle-cc a:first').click(function() {
			$('.field-cc').removeClass('hidden');
			$(this).parent().remove();
		});
	});
}