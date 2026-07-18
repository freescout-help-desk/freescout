$(document).ready(function() {
	function removeDateSort(){
		var tempSort = $('.table-conversations').attr("data-sorting_sort_by")
		$('tr span[data-sort-by="'+tempSort+'"]').text(function(index, oldText) {
		 // Replace 'oldText' with 'newText' if a condition is met
		 if (oldText.includes('↑') || oldText.includes('↓')) {
			 return oldText.replace('↑', '').replace('↓', '');
		 } else {
			 return oldText;
		 }
	 }); 
	 }
	
	var originalMainFunction = convListSortingInit;
	convListSortingInit = function() {
		// Call the original function
		originalMainFunction();
		if ($('.custom-field-tr:contains("↑")').length > 0 || $('.custom-field-tr:contains("↓")').length > 0 ){
			removeDateSort()
			}
	};

	// threls fork patch addition: the "Columns" control (which custom fields
	// show as columns, and which of those are sortable, per agent).
	function scfSaveColumnPref(control, customFieldId, visible, sortable) {
		var deferred = $.Deferred();
		fsAjax(
			{
				mailbox_id: control.attr('data-mailbox_id'),
				custom_field_id: customFieldId,
				visible: visible ? 1 : 0,
				sortable: sortable ? 1 : 0
			},
			control.attr('data-save-url'),
			function() { deferred.resolve(); },
			true,
			function() { deferred.reject(); }
		);
		return deferred.promise();
	}

	// One request that deletes every saved preference for this
	// user/mailbox, rather than one scfSaveColumnPref() call per field —
	// avoids N concurrent requests (and N redundant false/false rows) when
	// resetting a mailbox with several custom fields.
	function scfResetColumnPrefs(control) {
		var deferred = $.Deferred();
		fsAjax(
			{
				mailbox_id: control.attr('data-mailbox_id')
			},
			control.attr('data-reset-url'),
			function() { deferred.resolve(); },
			true,
			function() { deferred.reject(); }
		);
		return deferred.promise();
	}

	function scfUpdateHiddenBadge(control) {
		var hiddenCount = control.find('.scf-visible-toggle:not(:checked)').length;
		var badge = control.find('.scf-hidden-badge');
		if (hiddenCount > 0) {
			if (!badge.length) {
				badge = $('<span class="badge scf-hidden-badge"></span>');
				control.find('.scf-columns-btn .caret').before(badge);
			}
			badge.text(hiddenCount);
		} else {
			badge.remove();
		}
	}

	function scfRefreshTable() {
		if (typeof loadConversations === 'function') {
			loadConversations('', '', true);
		}
	}

	// loadConversations() (used by every core sort-header/pagination click,
	// not just our own toggle-triggered refresh) re-renders the *entire*
	// conversations_table.blade.php partial via AJAX
	// (ConversationsController::ajaxConversationsPagination), but the client
	// only ever replaces the <table> itself:
	// public/js/main.js -> $(".table-conversations:first").replaceWith(...).
	// Since our toolbar now lives in that same partial, every such refresh
	// inserts a fresh copy without removing the old one. Prune down to the
	// most recently inserted one after any AJAX call completes.
	$(document).ajaxComplete(function () {
		var controls = $('.scf-columns-control');
		if (controls.length > 1) {
			controls.slice(0, -1).remove();
		}
	});

	$(document).on('change', '.scf-visible-toggle', function() {
		var checkbox = $(this);
		var control = checkbox.closest('.scf-columns-control');
		var row = checkbox.closest('.scf-columns-row');
		var sortToggle = row.find('.scf-sortable-toggle');
		var visible = checkbox.is(':checked');
		var sortable = sortToggle.hasClass('is-active');

		sortToggle.prop('disabled', !visible);
		scfUpdateHiddenBadge(control);

		scfSaveColumnPref(control, row.attr('data-custom_field_id'), visible, sortable)
			.done(scfRefreshTable);
	});

	$(document).on('click', '.scf-sortable-toggle', function(e) {
		e.preventDefault();
		var toggle = $(this);
		if (toggle.is(':disabled')) {
			return;
		}
		var control = toggle.closest('.scf-columns-control');
		var row = toggle.closest('.scf-columns-row');
		var visible = row.find('.scf-visible-toggle').is(':checked');
		var sortable = !toggle.hasClass('is-active');

		toggle.toggleClass('is-active', sortable);
		toggle.attr('aria-pressed', sortable ? 'true' : 'false');
		toggle.attr('title', sortable ? 'Sortable — click to make static' : 'Not sortable — click to allow sorting');

		scfSaveColumnPref(control, row.attr('data-custom_field_id'), visible, sortable)
			.done(scfRefreshTable);
	});

	$(document).on('click', '.scf-reset-columns', function(e) {
		e.preventDefault();
		var control = $(this).closest('.scf-columns-control');

		// "Default" is opt-in (nothing shown) — matches
		// isVisibleToUser()/isSortableForUser()'s server-side default for a
		// field with no saved preference. One request deletes every saved
		// preference for this mailbox rather than saving false/false per
		// field (see scfResetColumnPrefs).
		control.find('.scf-columns-row').each(function() {
			var row = $(this);
			row.find('.scf-visible-toggle').prop('checked', false);
			row.find('.scf-sortable-toggle')
				.removeClass('is-active')
				.prop('disabled', true)
				.attr('aria-pressed', 'false');
		});

		scfUpdateHiddenBadge(control);
		scfResetColumnPrefs(control).done(scfRefreshTable);
	});
});
