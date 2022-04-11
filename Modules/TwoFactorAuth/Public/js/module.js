/**
 * Module's JavaScript.
 */

function initUserAuthSettings(tfa_enabled, next_page)
{
	$(document).ready(function() {
		if (!tfa_enabled) {
			$('#tfa_enabled').change(function(e) {
				if ($(this).is(':checked')) {
					window.location.href = next_page;
				}
			});
		}
	});
}