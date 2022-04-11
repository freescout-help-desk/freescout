/**
 * Module's JavaScript.
 */
var timetracking_time = -1;
var timetracking_total_time = -1;
var timetracking_interval = null;

function initTimeTracking(timelog_dialog)
{
	$(document).ready(function(){
		$('#timelogs-trigger').click(function(e) {
			$('#timelogs').toggleClass('hidden');
			e.preventDefault();
		});

		$('#timetr-pause').click(function(e) {
			var button = $(this);
			if (button.hasClass('disabled')) {
				return;
			}
			fsAjax({
					action: 'pause',
					conversation_id: getGlobalAttr('conversation_id')
				}, 
				laroute.route('time_tracking.ajax'), 
				function(response) {
					if (typeof(response.status) != "undefined" && response.status == 'success') {
						timetrStopTimer();
						$('#timetr-pause').addClass('disabled').attr('disabled', 'disabled');
						$('#timetr-start').removeClass('disabled').removeAttr('disabled');
					} else {
						showAjaxError(response);
					}
					loaderHide();
				}
			);
		});

		$('#timetr-start').click(function(e) {
			var button = $(this);
			if (button.hasClass('disabled')) {
				return;
			}
			fsAjax({
					action: 'start',
					conversation_id: getGlobalAttr('conversation_id')
				}, 
				laroute.route('time_tracking.ajax'), 
				function(response) {
					if (typeof(response.status) != "undefined" && response.status == 'success') {
						timetrStartTimer();
						$('#timetr-start').addClass('disabled').attr('disabled', 'disabled');
						$('#timetr-pause').removeClass('disabled').removeAttr('disabled');
					} else {
						showAjaxError(response);
					}
					loaderHide();
				}
			);
		});

		$('#timetr-reset').click(function(e) {
			var button = $(this);
			if (button.hasClass('disabled')) {
				return;
			}
			button.button('loading');
			fsAjax({
					action: 'reset',
					conversation_id: getGlobalAttr('conversation_id')
				}, 
				laroute.route('time_tracking.ajax'), 
				function(response) {
					if (typeof(response.status) != "undefined" && response.status == 'success') {
						timetracking_time = 0;
						timetracking_total_time = response.time_spent;
						timetrStopTimer();
						timetrStartTimer();
						$('#timetr-start').addClass('disabled').attr('disabled', 'disabled');
						$('#timetr-pause').removeClass('disabled').removeAttr('disabled');
					} else {
						showAjaxError(response);
					}
					button.button('reset');
					loaderHide();
				}
			);
		});

		if (timelog_dialog) {
			fsAddFilter('conversation.can_change_user', function(value, params) {
				if (!value) {
					return value;
				}
				if (getGlobalAttr('auth_user_id') == getConvData('user_id') 
					&& getGlobalAttr('auth_user_id') != params.trigger.attr('data-user_id') 
					&& timetracking_time
				) {
					timetrShowDialog(function() {
						params.trigger.trigger('fs-conv-user-change');
					});
				} else {
					params.trigger.trigger('fs-conv-user-change');
				}
			}, 999);

			fsAddFilter('conversation.can_change_status', function(value, params) {
				if (!value) {
					return value;
				}
				var status = params.trigger.attr('data-status');
				// Closed or Spam
				if ((status == 3 || status == 4) 
					&& getGlobalAttr('auth_user_id') == getConvData('user_id')
					&& timetracking_time
				) {
					timetrShowDialog(function() {
						params.trigger.trigger('fs-conv-status-change');
					});
				} else {
					params.trigger.trigger('fs-conv-status-change');
				}
			}, 999);

			fsAddFilter('conversation.can_submit', function(value, params) {
				if (!value || typeof(params) == "undefined" || typeof(params.trigger) == "undefined") {
					return value;
				}
				
				var conv_status = convGetStatus();
				if (conv_status == 3 || conv_status == 4) {
					return value;
				}
				var status = $('.note-statusbar:first select[name="status"]:first').val();

				// Closed or Spam
				if ((status == 3 || status == 4) 
					&& getGlobalAttr('auth_user_id') == getConvData('user_id')
					&& timetracking_time
					&& !$(params.trigger).attr('data-timetr-submit')
				) {
					fs_send_reply_allowed = false;
					timetrShowDialog(function() {
						fs_send_reply_allowed = true;
						$(params.trigger).attr('data-timetr-submit', '1');
						params.trigger.click();
					});
					return false;
				} else {
					return true;
				}
			}, 999);
		}

		var block = $('#time-tracking');
		if (block && block.attr('data-paused') != '1') {
			timetrStartTimer();
		}
	});
}

function timetrShowDialog(callback)
{
	var modal_html = 
		'<div>'+
			'<div class="margin-top-10">'+Lang.get("messages.timetr_modal_text")+'</div>'+
			'<div class="timetr-edit-time">'+
				'<input type="number" class="hours input-lg" size="3" min="0" value="'+timetrFormatTime(timetracking_time, 'hours')+'"/> : '+
				'<input type="number" class="minutes input-lg" min="0" max="60" value="'+timetrFormatTime(timetracking_time, 'minutes')+'"/> : '+
				'<input type="number" class="seconds input-lg" min="0" max="60" value="'+timetrFormatTime(timetracking_time, 'seconds')+'"/>'+
				'<div class="timetr-hhmmss">'+
					'(HH:mm:ss)'+
				'</div>'+
			'</div>'+
		'</div>';

	var footer_html = '<button class="btn btn-link timetr-cancel" data-loading-text="'+Lang.get("messages.cancel")+'…">'+Lang.get("messages.cancel")+'</button>'+
					 '<button class="btn btn-primary timetr-submit-time" data-loading-text="'+Lang.get("messages.submit_time")+'…">'+Lang.get("messages.submit_time")+'</button>';

	showModalDialog(modal_html, {
		title: Lang.get("messages.time_tracking"),
		no_close_btn: Lang.get("messages.time_tracking"),
		footer: footer_html,
		no_header: false,
		no_footer: false,
		width_auto: false,
		no_close_btn: true,
		on_show: function(modal) {
			modal.children().find('.timetr-submit-time:first').click(function(e) {
				var button = $(this);
				button.button('loading');
				fsAjax(
					{
						action: 'submit_time',
						conversation_id: getGlobalAttr('conversation_id'),
						hours: modal.children().find('.hours:first').val(),
						minutes: modal.children().find('.minutes:first').val(),
						seconds: modal.children().find('.seconds:first').val()
					},
					laroute.route('time_tracking.ajax'), 
					function(response) {
						showAjaxResult(response);
						button.button('reset');
						modal.modal('hide');
						callback();
					}, 
					true, 
					function(response) {
						showAjaxResult(response);
						modal.modal('hide');
						callback();
					}
				);
			});

			modal.children().find('.timetr-cancel:first').click(function(e) {
				var button = $(this);
				button.button('loading');
				fsAjax(
					{
						action: 'cancel',
						conversation_id: getGlobalAttr('conversation_id')
					},
					laroute.route('time_tracking.ajax'), 
					function(response) {
						showAjaxResult(response);
						button.button('reset');
						modal.modal('hide');
						callback();
					}, 
					true, 
					function(response) {
						showAjaxResult(response);
						modal.modal('hide');
						callback();
					}
				);
			});
		}
	});
}


function timetrStartTimer()
{
	if (timetracking_time == -1) {
		timetracking_time = parseInt($('#time-tracking').attr('data-current-time'));
	}
	if (isNaN(timetracking_time) || timetracking_interval || !timetrIsActive()) {
		return;
	}
	if (timetracking_total_time == -1) {
		timetracking_total_time = parseInt($('#time-tracking').attr('data-total-time'));
		if (isNaN(timetracking_total_time)) {
			timetracking_total_time = 0;
		}
	}

	timetracking_interval = setInterval(function() {
		timetracking_time += 1;
		timetracking_total_time += 1;
		timetrRenderTimer();
	}, 1000);
}

function timetrIsActive()
{
	return $('#timetr-time').length;
}

function timetrStopTimer()
{
	clearInterval(timetracking_interval);
	timetracking_interval = null;
}

function timetrRenderTimer()
{
	if (timetracking_time == -1 || isNaN(timetracking_time)) {
		return;
	}

	var time = timetrFormatTime(timetracking_time);
    $('#timetr-time').text(time);

    // Total
	var time = timetrFormatTime(timetracking_total_time);
    $('#timetr-total').text(time);
}

function timetrFormatTime(timestamp, hms)
{
	var hours = Math.floor(timestamp / 3600);
    var minutes = Math.floor((timestamp / 60) % 60);
    var seconds = timestamp % 60;

    hours = timetrPadStart(hours, 2, '0');
    minutes = timetrPadStart(minutes, 2, '0');
    seconds = timetrPadStart(seconds, 2, '0');

    if (typeof(hms) != "undefined") {
    	if (hms == 'hours') {
    		return hours;
    	} else if (hms == 'minutes') {
    		return minutes;
    	} else if (hms == 'seconds') {
    		return seconds;
    	}
    }

    return hours+':'+minutes+':'+seconds;
}

function timetrPadStart(str, targetLength, padString) {
    targetLength = targetLength>>0; //truncate if number or convert non-number to 0;
    str = str + '';

    padString = String((typeof padString !== 'undefined' ? padString : ' '));
    if (str.length > targetLength) {
        return String(str);
    } else {

        targetLength = targetLength-str.length;
        if (targetLength > padString.length) {
            padString += padString.repeat(targetLength/padString.length); //append to original to ensure we are longer than needed
        }
        return padString.slice(0,targetLength) + String(str);
    }
};