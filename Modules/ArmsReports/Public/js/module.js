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
	// this just no-ops back to two separate menus (falling back to
	// $armsDropdown.show() below) rather than breaking anything - it just
	// won't be silent about it visually, since the item was hidden inline
	// in menu.blade.php to avoid flashing visible before this runs.
	var $armsDropdown = $('[data-arms-reports-dropdown]');
	if (!$armsDropdown.length) {
		return;
	}

	// Read the label before any DOM removal below - jQuery's remove()
	// clears its own data cache from the elements it detaches, so anything
	// read from $armsDropdown has to happen first.
	var reportsLabel = $armsDropdown.data('reports-label');

	var $reportsDropdown = $('.navbar-nav > li.dropdown').filter(function () {
		return $(this).find('> a.dropdown-toggle').text().trim() === reportsLabel;
	});
	if (!$reportsDropdown.length) {
		$armsDropdown.show();
		return;
	}

	var $armsItems = $armsDropdown.find('> ul.dropdown-menu > li');
	var $reportsMenu = $reportsDropdown.find('> ul.dropdown-menu');
	if (!$armsItems.length || !$reportsMenu.length) {
		$armsDropdown.show();
		return;
	}

	$reportsMenu.append('<li role="separator" class="divider"></li>').append($armsItems);
	$armsDropdown.remove();
});

// ARMS-40 follow-up: the native Reports pages' "Export to PDF" button
// (rendered by this module via the reports.filters_button_append hook).
// Delegated on document since the button is server-rendered as part of the
// page, not injected dynamically like the dropdown merge above.
$(document).on('click', '#arms-reports-native-pdf-export', function (e) {
	e.preventDefault();

	var $report = $('#rpt-report');
	if (!$report.length) {
		return;
	}

	var $capture = $('<div></div>');
	$capture.append($report.find('.rpt-metrics').clone());

	// dompdf can't execute Chart.js, but the browser has already drawn the
	// chart's pixels onto the canvas by the time this runs - toDataURL()
	// exports exactly that as a PNG, which dompdf renders like any other
	// image. Swapped in as an <img> in place of the (otherwise empty to
	// dompdf) <canvas>.
	var $canvas = $report.find('#rpt-chart');
	if ($canvas.length && $canvas[0].getContext) {
		try {
			// Chart.js animates its initial draw-in by default, so a click
			// shortly after a refresh could otherwise snapshot a
			// mid-transition frame instead of the finished chart. Forcing
			// an animation-free redraw right before the snapshot removes
			// that race instead of relying on the user waiting it out.
			var chart = window.Chart && window.Chart.getChart ? window.Chart.getChart($canvas[0]) : null;
			if (chart) {
				chart.options.animation = false;
				chart.update();
			}
			var chartImage = $canvas[0].toDataURL('image/png');
			$capture.append($('<div class="rpt-chart-image"></div>').append($('<img>').attr('src', chartImage)));
		} catch (err) {
			// Tainted canvas (cross-origin content drawn onto it) or an
			// unsupported browser - omit the chart rather than fail the
			// whole export, same as before this was added.
			if (window.console && window.console.warn) {
				window.console.warn('ARMS Reports: chart export skipped', err);
			}
		}
	}

	$capture.append($report.find('#rpt-tables').clone());

	if (!$capture.children().length) {
		return;
	}

	var $button = $(this);
	var title = $.trim($('.rpt-title').first().text()) || document.title;

	var $form = $('<form method="POST" target="_blank"></form>').attr('action', $button.data('export-url'));
	$form.append($('<input type="hidden" name="_token">').val($button.data('csrf')));
	$form.append($('<input type="hidden" name="title">').val(title));
	$form.append($('<input type="hidden" name="html">').val($capture.html()));
	$('body').append($form);
	$form.submit();
	$form.remove();
});
