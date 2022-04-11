function noreplyInitConv(noreply_emails, alert_message)
{
	fsAddFilter('conversation.recipient_selector', function(result, params) {
		result.on('select2:select', function(e) {

			var select = $(e.target);
			var selected_noreply_emails = noreplyGetNoreplyEmails(select, noreply_emails);

			for (var i in selected_noreply_emails) {
				// Show alert
				noreplyAddAlert(select, selected_noreply_emails[i], alert_message);
			}
		});
		result.on('select2:unselect', function(e) {
			var select = $(e.target);
			noreplyHideAlerts(select, noreply_emails);
		});
		result.on('select2:clear', function(e) {
			var select = $(e.target);
			noreplyHideAlerts(select, noreply_emails);
		});
		return result;
	});

	fsAddAction('conversation.recipient_selector_initialized', function(params) {
		params.selector.each(function(i, el) {
			var select = $(el);
			var selected_noreply_emails = noreplyGetNoreplyEmails(select, noreply_emails);

			for (var i in selected_noreply_emails) {			
				noreplyAddAlert(select, selected_noreply_emails[i], alert_message);
			}
		});
	});
}


function noreplyAddAlert(select, email, text)
{
	// if (select.parent().children('.noreply-alert').length) {
	// 	return;
	// }
	var has_alert = false;

	select.parent().children('.noreply-alert').each(function(i, el) {
		if ($(el).attr('data-noreply-email') == email) {
			has_alert = true;
		}
	});

	if (has_alert) {
		return;
	}

	text = text.replace(':email', '<strong>&lt;'+email+'&gt;</strong>');
	var html = '<div class="alert alert-warning alert-narrow margin-bottom-0 noreply-alert" data-noreply-email="'+email+'">'+text+'</div>';
	select.parent().append(html);
}

function noreplyHideAlerts(select, noreply_emails)
{
	var selected_noreply_emails = noreplyGetNoreplyEmails(select, noreply_emails);

	if (selected_noreply_emails.length) {
		select.parent().children('.noreply-alert').each(function(i, el) {
			if (selected_noreply_emails.indexOf($(el).attr('data-noreply-email')) == -1) {
				$(el).remove();
			}
		});
	} else {
		select.parent().children('.noreply-alert').remove();
	}
}

function noreplyGetNoreplyEmails(select, noreply_emails)
{
	var result = [];
	var emails = select.val();

	for (var i in emails) {
		for (var j in noreply_emails) {
			var pattern = noreply_emails[j];
			pattern = pattern.replace(/\-/g, '\\-?');
			var re = new RegExp('.*'+pattern+'.*');
			if (re.test(emails[i])) {
				// Show alert
				result.push(emails[i]);
			}
		}
	}

	return result;
}