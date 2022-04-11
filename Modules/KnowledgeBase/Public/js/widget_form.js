/**
 * Widget form JavaScript.
 */

$(document).ready(function(){

	$('#kb-minimize').click(function(e) {
        if (typeof(window.parent) != "undefined") {
            window.parent.postMessage('fsw.minimize', '*');
        }

		e.preventDefault();
	});

	$('#kb-search .input-group-addon').click(function(e) {
        $(this).parents('form:first').submit();
	});

	// Open all links in articles in a new window
	$('.kb-article-text a').each(function(i, el) {
		$(el).attr('target', '_blank');
	});
});

function kbBack()
{
	if (document.referrer) {
		window.location.href = document.referrer;
	} else {
		history.back();
	}
}

function kbBackSearch(q)
{
	$('#kb-ticket-form input[name="q"]:first').val(q);
	$('#kb-ticket-form').submit();
}