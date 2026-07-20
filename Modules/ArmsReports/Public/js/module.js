$(document).ready(function () {
	// ARMS-13: the paid Reports module and this module each register their
	// own top-level nav dropdown via the same menu.append hook, so they end
	// up as visually-duplicate siblings ("Reports" next to "ARMS Reports").
	// Fold this module's two links into the native Reports dropdown instead
	// of shipping a second one - a DOM move only, no dependency on the paid
	// module's PHP/Blade internals.
	//
	// Our own dropdown is found via a data attribute we control, not its
	// label text, since that text is translated per-locale (__('ARMS
	// Reports')). The native Reports dropdown has to be matched by label
	// text (it's someone else's markup), but menu.blade.php renders that
	// label server-side too (__('Reports')), through the same translation
	// files the native module's own label goes through, so the two stay in
	// sync across locales without this script hardcoding English text
	// (gemini-code-assist review, PR #18).
	//
	// If a future Reports update changes its own label or dropdown shape,
	// this just silently no-ops back to two separate menus rather than
	// breaking anything.
	var $armsDropdown = $('[data-arms-reports-dropdown]');
	if (!$armsDropdown.length) {
		return;
	}

	var reportsLabel = $armsDropdown.data('reports-label');
	var $reportsDropdown = $('.navbar-nav > li.dropdown').filter(function () {
		return $(this).find('> a.dropdown-toggle').text().trim() === reportsLabel;
	});
	if (!$reportsDropdown.length) {
		return;
	}

	var $armsItems = $armsDropdown.find('> ul.dropdown-menu > li');
	var $reportsMenu = $reportsDropdown.find('> ul.dropdown-menu');
	if (!$armsItems.length || !$reportsMenu.length) {
		return;
	}

	$reportsMenu.append('<li role="separator" class="divider"></li>').append($armsItems);
	$armsDropdown.remove();
});
