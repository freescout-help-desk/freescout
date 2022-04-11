/**
 * Widget form JavaScript.
 */

var chat_threads = false;
var chat_conversation_id = '';
var chat_customer_id = '';
// Last thread id
var chat_thread_id = '';

var chat_thread_status_ok = 1;
var chat_thread_status_error = 2;

var chat_thread_person_customer = 1;
var chat_thread_person_user = 2;

var chat_slow_polling = true;
var chat_poll_timer = null;

$(document).ready(function(){

	chatLoad();

	$('#chat-message').keyup(function(e){
	    var keycode = (e.keyCode ? e.keyCode : e.which);

	    // 10 - CTRL+ENTER
	    if (keycode == 13 || keycode == 10) {
		    if (e.ctrlKey || e.shiftKey || e.metaKey) {
		    	if (!e.shiftKey) {
		    		$(this).val($(this).val()+"\n");
		    	}
		        return;
		    }
		    chatDoSubmit($(this));
		}
	});

	$('#chat-send-trigger').click(function() {
		chatDoSubmit($('#chat-message'));
	});

	// Attachments
	$('#chat-attachment-trigger').click(function() {
		var element = document.createElement('div');
		element.innerHTML = '<input type="file" multiple>';
		var fileInput = element.firstChild;

		fileInput.addEventListener('change', function() {
			if (fileInput.files) {
				chatAddAttachments(fileInput.files);
		    }
		});

		fileInput.click();
	});

	$('#chatw-minimize').click(function(e) {
        if (typeof(window.parent) != "undefined") {
            window.parent.postMessage('fsw.minimize', '*');
        }

		e.preventDefault();
	});

	chatListenForEvent('fsw.onshow', function() {
		chatScrollDown();
	});
});

function chatListenForEvent(event_name, callback) {
    if (window.addEventListener){
        window.addEventListener("message", function(event) {
            if (typeof(event.data) != "undefined" && event.data == event_name)  {
                callback();
            }
        }, false);
    } else if (element.attachEvent) {
        window.attachEvent("onmessage", function(event) {
            if (typeof(event.data) != "undefined" && event.data == event_name)  {
                callback();
            }
        });
    }
};

function chatDoSubmit(textarea)
{
	var body = textarea.val();
    if (!body.replace(/[\r\n]/g, '')) {
    	return;
    }
    body = htmlEscape(body);

    textarea.val('').focus();

    chatSubmit(body);
}

function chatSubmit(body, attachments, callback_success, callback_error, add_thread)
{
	var pass_settings = ['visitor_name', 'visitor_email', 'visitor_phone'];

	if (typeof(attachments) == "undefined") {
		attachments = [];
	}
	if (typeof(add_thread) == "undefined") {
		add_thread = true;
	}

	var data = new FormData();
	data.append("action", 'submit');
	data.append("mailbox_id_encoded", getGlobalAttr('mailbox_id_encoded'));
	data.append("body", body);
	data.append("conversation_id", chat_conversation_id);
	data.append("customer_id", chat_customer_id);
	data.append("locale", chatGetSetting('locale'));
	for (var i in pass_settings) {
		if (chatGetSetting(pass_settings[i]) && !chatStorageGet(pass_settings[i].replace('visitor_', 'chat_customer_'))) {
			data.append(pass_settings[i], chatGetSetting(pass_settings[i]));
		}
	}
	
	for (var i in attachments) {
		if (typeof(attachments[i].type) == "undefined") {
			continue;
		}
		data.append("attachments[]", attachments[i]);
	}

	fsAjax(/*{
			action: 'submit',
			mailbox_id_encoded: getGlobalAttr('mailbox_id_encoded'),
			body: body,
			conversation_id: chat_conversation_id,
			customer_id: chat_customer_id,
			attachments: attachments
		}, */
		data,
		laroute.route('chat.ajax'), 
		function(response) {
			if (isAjaxSuccess(response)) {
				if (add_thread) {
					chatAddCustomerThread(body, chat_thread_status_ok, response.thread_id);
				}
				if (response.thread_id) {
					chat_thread_id = response.thread_id;
					localStorageSet('chat_thread_id_'+getGlobalAttr('mailbox_id_encoded'), response.thread_id);
				}
				if (response.customer_id) {
					chat_customer_id = response.customer_id;
					localStorageSet('chat_customer_id_'+getGlobalAttr('mailbox_id_encoded'), response.customer_id);
				}
				if (response.conversation_id) {
					chat_conversation_id = response.conversation_id;
					localStorageSet('chat_conversation_id_'+getGlobalAttr('mailbox_id_encoded'), response.conversation_id);
				}
				chatSpeedUpPolling(true);
			} else {
				if (add_thread) {
					chatAddCustomerThread(body, chat_thread_status_error);
				}
			}
			if (typeof(callback_success) != "undefined") {
				callback_success(response);
			}
		},
		true,
		function() {
			// Error
			if (add_thread) {
				chatAddCustomerThread(body, chat_thread_status_error);
			}
			if (typeof(callback_success) != "undefined") {
				callback_error(callback_error);
			}
		}, {
			data: data,
			cache: false,
			contentType: false,
			processData: false
		}
	);
}

function chatAddCustomerThread(body, thread_status, thread_id, attachments, render)
{
	if (thread_id == "undefined") {
		thread_id = null;
	}
	if (render == "undefined") {
		render = true;
	}
	chat_threads = chatGetThreads();

	var new_thread ={
		body: body.trim(),
		status: thread_status,
		person: chat_thread_person_customer,
		id: thread_id
	};

	if (attachments) {
		new_thread.attachments = attachments;
	}

	chat_threads.push(new_thread);

	localStorageSetObject('chat_threads_'+getGlobalAttr('mailbox_id_encoded'), chat_threads);

	if (typeof(render) == "undefined") {
		render = true;
	}
	if (render) {
		chatRender();
	}
}

function chatAddUserThread(body, thread_status, thread_id, user_name, user_photo, attachments)
{
	if (!body) {
		return;
	}
	if (thread_id == "undefined") {
		thread_id = null;
	}
	chat_threads = chatGetThreads();

	var thread_exists = false;
	for (var i in chat_threads) {
		if (chat_threads[i].id == thread_id) {
			thread_exists = true;
			break;
		}
	}
	if (thread_exists) {
		return;
	}

	var new_thread = {
		body: body.trim(),
		status: thread_status,
		person: chat_thread_person_user,
		id: thread_id,
		user_name: user_name,
		user_photo: user_photo
	};

	if (attachments) {
		new_thread.attachments = attachments;
	}

	chat_threads.push(new_thread);

	localStorageSetObject('chat_threads_'+getGlobalAttr('mailbox_id_encoded'), chat_threads);

	chatRender();
}

function chatGetThreads()
{
	if (chat_threads === false) {
		chat_threads = localStorageGetObject('chat_threads_'+getGlobalAttr('mailbox_id_encoded'));
	}
	if (!Array.isArray(chat_threads)) {
		chat_threads = [];
	}
	return chat_threads;
}

function chatLoad()
{
	chat_conversation_id = localStorageGet('chat_conversation_id_'+getGlobalAttr('mailbox_id_encoded'));
	chat_customer_id = localStorageGet('chat_customer_id_'+getGlobalAttr('mailbox_id_encoded'));
	chat_thread_id = localStorageGet('chat_thread_id_'+getGlobalAttr('mailbox_id_encoded'));

	$('#chat-message').focus();

	chatRender();

	// Get new threads by timer
	chatPollThreads();
}

function chatRender()
{
	var threads = chatGetThreads();
	chatShowThreads(threads);
}

function chatSimplifyThreadId(id)
{
	// if (typeof(id) == "undefined") {
	// 	return '';
	// }
	return id.replace(/[^A-Za-z0-9]/g, '').substring(0, 31);
}

function chatShowThreads(threads)
{
	var html = '';
	var last_person = null;
	var last_user_name = null;
	var last_user_photo = null;
	var check_thread_id = true;

	if (!$('#chat-threads').html()) {
		check_thread_id = false;
	}

	for (var i in threads) {
		var thread = threads[i];
		if (thread.id) {
			var thread_simplified_id = chatSimplifyThreadId(thread.id);
		} else {
			// In case of error
			var thread_simplified_id = chatSimplifyThreadId(generateDummyId());
		}

		if (check_thread_id && $('#chat-thread-'+thread_simplified_id).length) {
			last_person = thread.person;
			last_user_name = thread.user_name;
			last_user_photo = thread.user_photo;
			continue;
		}

		if (thread.person == chat_thread_person_customer) {
			html += chatGetCustomerThreadHtml(thread);

			if (i == 0 && chat_customer_id && !chatGetSetting('visitor_name')) {
				html += '<div class="chat-info-trigger"><a href="javascript:chatShowInfoModal();void(0);">'+chat_msg_info_trigger+'</a></div>';
			}
		} else {
			var user_photo = thread.user_photo;
			if (!user_photo) {
				user_photo = chat_default_user_photo;
			}

			html += '<div class="chat-thread chat-thread-user" id="chat-thread-'+thread_simplified_id+'">';
			if (last_person != thread.person
				|| last_user_name != thread.user_name
				|| last_user_photo != thread.user_photo
			) {
				html += '<div class="chat-thread-user-name">';
				html += htmlEscape(thread.user_name);
				html += '</div>';
			}

			if ((typeof(threads[parseInt(i)+1]) != "undefined" && threads[parseInt(i)+1].person != chat_thread_person_user)
				// last thread
				|| i == threads.length-1
				// another user
				|| (typeof(threads[parseInt(i)+1]) != "undefined" && (threads[parseInt(i)+1].user_name != thread.user_name || threads[parseInt(i)+1].user_photo != thread.user_photo))
			) {
				html += '<img class="chat-thread-photo" src="'+user_photo+'" />';
				if (i != 0 
					&& threads[parseInt(i)-1].person == chat_thread_person_user
					&& threads[parseInt(i)-1].user_name == thread.user_name
					&& threads[parseInt(i)-1].user_photo == thread.user_photo
				) {
					// Remove photo
					$('#chat-thread-'+chatSimplifyThreadId(threads[parseInt(i)-1].id)+' .chat-thread-photo:first').remove();
				}
			}
			html += '<div class="chat-thread-text">';
			html += thread.body;
			html += chatAttachmentsHtml(thread);
			html += '</div>';
			html += '</div>';
		}

		last_person = thread.person;
		last_user_name = thread.user_name;
		last_user_photo = thread.user_photo;
	};

	$('#chat-threads').append(html);
	chatScrollDown();
	setTimeout(chatScrollDown, 300);
}

function chatGetCustomerThreadHtml(thread)
{
	if (!thread.id) {
		thread.id = generateDummyId();
	}
	var thread_simplified_id = chatSimplifyThreadId(thread.id);

	var thread_classes = '';
	if (!thread.body && typeof(thread.attachments) != "undefined" && thread.attachments) {
		thread_classes += ' chat-thread-attachment ';
	}

	html = '<div class="chat-thread chat-thread-customer '+thread_classes+'" id="chat-thread-'+thread_simplified_id+'">';
	html += '<div class="chat-thread-text">';
	html += thread.body;
	html += chatAttachmentsHtml(thread);
	html += '</div>';
	if (thread.status == chat_thread_status_error) {
		html += '<div class="chat-thread-status chat-thread-status-error">';
		html += chat_msg_not_delivered;
		html += '</div>';
	}
	html += '</div>';

	return html;
}

function chatAttachmentsHtml(thread)
{
	var html = '';

	if (typeof(thread.attachments) == "undefined") {
		return '';
	}
	for (var i in thread.attachments) {
		var attachment = thread.attachments[i];

		html += '<div class="chat-attachment">';
		if (typeof(attachment.url) != "undefined" && attachment.url) {
			html += '<a href="'+attachment.url+'" target="_blank">'+htmlEscape(attachment.name)+'</a> ';
			html += '<nobr><small class="text-help">('+attachment.size+')</small> ';
			html += '<a href="'+attachment.url+'" target="_blank" download><i class="glyphicon glyphicon-download-alt"></i></a></nobr>';
		} else {
			html += '<span class="chat-attachment-name">'+htmlEscape(attachment.name)+'</span> ';
			html += '<nobr><small class="text-help">('+attachment.size+')</small> ';
			html += '<img src="'+chat_loader_tiny+'" /></nobr>';
		}
		html += '</div>';
	}

	return html;
}

function chatScrollDown()
{
	$('#chat-threads').scrollTop($('#chat-threads')[0].scrollHeight);
}

function chatStorageGet(param)
{
	var value = localStorageGet(param+'_'+getGlobalAttr('mailbox_id_encoded'));
	if (value) {
		return value;
	} else {
		return '';
	}
}

function chatGetSetting(name)
{
	return getQueryParam(name);
}

function chatStorageSet(param, value)
{
	localStorageSet(param+'_'+getGlobalAttr('mailbox_id_encoded'), value);
}

function chatShowInfoModal()
{
	var name = chatStorageGet('chat_customer_name');
	var email = chatStorageGet('chat_customer_email');
	var phone = chatStorageGet('chat_customer_phone');

	if (!name && chatGetSetting('visitor_name')) {
		name = chatGetSetting('visitor_name');
	}
	if (!email && chatGetSetting('visitor_email')) {
		email = chatGetSetting('visitor_email');
	}
	if (!phone && chatGetSetting('visitor_phone')) {
		phone = chatGetSetting('visitor_phone');
	}
	
	var html = 
		'<div>'+
			'<div class="form-group margin-top-10">'+
				'<label>'+Lang.get("messages.name")+'</label>'+
				'<input type="text" class="form-control chat-info-name" value="'+name+'" placeholder="'+chat_msg_optional+'" />'+
			'</div>'+
			'<div class="form-group margin-top-10">'+
				'<label>'+Lang.get("messages.email")+'</label>'+
				'<input type="text" class="form-control chat-info-email" value="'+email+'" placeholder="'+chat_msg_optional+'" />'+
			'</div>'+
			'<div class="form-group margin-top-10">'+
				'<label>'+Lang.get("messages.phone")+'</label>'+
				'<input type="text" class="form-control chat-info-phone" value="'+phone+'" placeholder="'+chat_msg_optional+'" />'+
			'</div>'+
			'<div class="form-group margin-top-20">'+
				'<button class="btn btn-primary chat-info-save" data-loading-text="'+chat_msg_save+'…" >'+chat_msg_save+'</button>'+
				'<button class="btn btn-link" data-dismiss="modal">'+Lang.get("messages.cancel")+'</button>'+
			'</div>'+
		'</div>';

		showModalDialog(html, {
			on_show: function(modal) {
				modal.children().find('.chat-info-save:first').click(function(e) {
					var name = $('.chat-info-name:visible:first').val();
					var email = $('.chat-info-email:visible:first').val();
					var phone = $('.chat-info-phone:visible:first').val();

					var button = $(e.target);

					button.button('loading');

					fsAjax({
							action: 'save_info',
							name: name,
							email: email,
							phone: phone,
							locale: chatGetSetting('locale'),
							customer_id: chat_customer_id
						}, 
						laroute.route('chat.ajax'), 
						function(response) {
							if (isAjaxSuccess(response)) {
								chatStorageSet('chat_customer_name', name);
								chatStorageSet('chat_customer_email', email);
								chatStorageSet('chat_customer_phone', phone);
								modal.modal('hide');
							} else {
								alert(Lang.get("messages.error_occured"));
							}
							button.button('reset');
						},
						true,
						function() {
							alert(Lang.get("messages.error_occured"));
							button.button('reset');
						},
					);
					e.preventDefault();
				});
			},
			no_header: false,
			title: chat_msg_info_title
		});
}

function chatPlayAudio(path)
{
	var audio = new Audio(path);
	audio.play();
}

function chatGetPollInterval()
{
	if (chat_slow_polling) {
		return 30000;
	} else {
		return 10000;
	}
}

function chatSchedulePolling()
{
	chat_poll_timer = setTimeout(chatPollThreads, chatGetPollInterval());
}

function chatSpeedUpPolling(reschedule)
{
	chat_slow_polling = false;
	clearTimeout(chat_poll_timer);
	if (typeof(reschedule) != "undefined") {
		chatSchedulePolling();
	}
}

function chatPollThreads()
{
	if (!chat_conversation_id || !chat_thread_id) {
		return;
	}

    fsAjax({
			action: 'poll',
			conversation_id: chat_conversation_id,
			thread_id: chat_thread_id
		}, 
		laroute.route('chat.ajax'), 
		function(response) {
			chatSchedulePolling();

			if (!isAjaxSuccess(response)
				|| typeof(response.threads) == "undefined" 
				|| !Array.isArray(response.threads)
			) {
				return;
			}
			
			if (response.threads.length) {
				for (var i in response.threads) {
					var thread = response.threads[i];
					var attachments = [];
					if (typeof(thread.attachments) != "undefined") {
						attachments = thread.attachments;
					}
					chatAddUserThread(thread.body, chat_thread_status_ok, thread.id, thread.user_name, thread.user_photo, attachments);
					chatPlayAudio(chat_sound_notification);
					// Remember last thread
					chat_thread_id = thread.id;
					localStorageSet('chat_thread_id_'+getGlobalAttr('mailbox_id_encoded'), chat_thread_id);

					// Show new message indicator
					if (!$('#fsw-iframe', window.parent.document).is(":visible")) {
						window.parent.postMessage('fsw.newmessage', '*');
					}
				}
				chatSpeedUpPolling(true);
			}
		},
		true,
		function() {
			// Error
			chatSchedulePolling();
		},
	);
}

function chatAddAttachments(files)
{
	var attachments = [];

	for (var i = 0; i < files.length; i++) {

		var file = files[i];

		if (!file || typeof(file.type) == "undefined") {
			continue;
		}

		attachments.push({
			name: file.name,
			size: formatBytes(file.size)
		});
	}
	var thread_dummy_id = chatSimplifyThreadId(generateDummyId());

	$('#chat-threads').append(chatGetCustomerThreadHtml({
		id: thread_dummy_id,
		body: '',
		attachments: attachments
	}));
	
	chatScrollDown();

	chatSubmit(chat_msg_attachment, files, function(response) {
		if (isAjaxSuccess(response)) {
			var saved_attachments = [];
			if (typeof(response.attachments) != "undefined") {
				for (var i in response.attachments) {
					var attachment = response.attachments[i];

					if (attachment.url) {
						chatGetAttachmentByName(thread_dummy_id, attachment.name).children().find('img').replaceWith('<i class="glyphicon glyphicon-ok text-success"></i>');

						saved_attachments.push(attachment);
					} else {
						chatAttachmentError(thread_dummy_id, response.msg, attachment.name);
					}
				}
				// todo: show errors for other attachments
			}
			chatAddCustomerThread('', chat_thread_status_ok, response.thread_id, saved_attachments, false);
		} else {
			// Show error for all
			chatAttachmentError(thread_dummy_id, response.msg);
		}

	}, function(response) {
		// Show error for all
		chatAttachmentError(thread_dummy_id, textStatus+": "+errorThrown);
	}, false);
}

function chatGetAttachmentByName(thread_id, name)
{
	var container = $('<div></div>');

	$('#chat-thread-'+thread_id+' .chat-attachment-name').each(function(i, el) {
		if ($(el).text() == htmlEscape(name)) {
			container = $(el).parent();
		}
	});

	return container;
}

function chatAttachmentError(thread_dummy_id, msg, name)
{
	var error_text = Lang.get("messages.error_occured");
	if (msg) {
		error_text = msg;
	}
	if (typeof(name) != "undefined") {
		chatGetAttachmentByName(thread_dummy_id, attachment.name).append('<div class="chat-attachment-error text-danger">'+error_text+'</div>');
		chatGetAttachmentByName(thread_dummy_id, attachment.name).children().find('img').remove();
	} else {
		$('#chat-thread-'+thread_dummy_id+' .chat-thread-text:first').append('<div class="chat-attachment-error text-danger">'+error_text+'</div>');
		$('#chat-thread-'+thread_dummy_id+' img').remove();
	}
	chatScrollDown();
}