/**
 * Module's JavaScript.
 */

// Run translation
function translateThread(el)
{
	var button = $(el);

	button.button('loading');

	var thread_id = button.parents('.modal-content:first').children().find('.input-translate-thread-id:first').val();
	var from = button.parents('.modal-content:first').children().find('.input-translate-from:first').val();
	var into = button.parents('.modal-content:first').children().find('.input-translate-into:first').val();

	fsAjax({
			action: 'translate',
			thread_id: thread_id,
			from: from,
			into: into
		}, 
		laroute.route('ticket_translator.ajax'), 
		function(response) {
			if (isAjaxSuccess(response)) {
				window.location.href = '';
			} else {
				showAjaxError(response);
				ajaxFinish();
			}
		}, true
	);
}

// Togle translations
function toggleTranslation(thread_id, locale)
{
	var trigger = $('#thread-'+thread_id+' .translation-trigger-'+locale+':first');
	var text = $('#thread-'+thread_id+' .translation-'+locale+':first');

	if (text.is(':visible')) {
		// Hide
		text.addClass('hidden');
		trigger.removeClass('selected');
		trigger.children('.caret:first').addClass('hidden');
	} else {
		// Show
		$('#thread-'+thread_id+' .translation-text').addClass('hidden');
		$('#thread-'+thread_id+' .translation-triggers .caret').addClass('hidden');
		$('#thread-'+thread_id+' .translation-triggers a').removeClass('selected');
		text.removeClass('hidden');
		trigger.addClass('selected');
		trigger.children('.caret:first').removeClass('hidden');
	}
}