$(document).ready(function () {
	// ARMS-13: the paid Reports module and this module each register their
	// own top-level nav dropdown via the same menu.append hook, so they end
	// up as visually-duplicate siblings ("Reports" next to "ARMS Reports").
	// Fold this module's two links into the native Reports dropdown instead
	// of shipping a second one — a DOM move only, no dependency on the paid
	// module's PHP/Blade internals. Matched by the dropdown toggle's exact
	// text rather than a module-specific class, since that's the only part
	// of its markup we can rely on without its source. If a future Reports
	// update changes that text or drops the dropdown shape, this just
	// silently no-ops back to two separate menus rather than breaking
	// anything.
	var findDropdownByLabel = function (label) {
		return $('.navbar-nav > li.dropdown').filter(function () {
			return $(this).find('> a.dropdown-toggle').text().trim() === label;
		});
	};

	var $armsDropdown = findDropdownByLabel('ARMS Reports');
	var $reportsDropdown = findDropdownByLabel('Reports');

	if (!$armsDropdown.length || !$reportsDropdown.length) {
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
