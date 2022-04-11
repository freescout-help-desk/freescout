/**
 * Module's JavaScript.
 */

var ks_keys = {
	a: 65,
	c: 67,
	d: 68,
	e: 69,
	f: 70,
	j: 74,
	k: 75,
	m: 77,
	n: 78,
	o: 79,
	p: 80,
	q: 81,
	r: 82,
	s: 83,
	t: 84,
	v: 86,
	w: 87
}

$(document).ready(function(){
	if ($('.table-conversations').length == 1) {
		ksConversationList();
	}
});

function ksConversation()
{
	// Reply
	ksBind(ks_keys['r'], function() {
		$('.conv-reply:visible').not('.inactive').click();
	});

	// Add Note or Not Spam
	ksBind(ks_keys['n'], function() {
		if (ksConvStatusMenuOpen()) {
			$("#conv-status .conv-status a[data-status='not_spam']:first").click();
		} else {
			$('.conv-add-note:visible').not('.inactive').click();
		}
	});

	// Forward
	ksBind(ks_keys['f'], function() {
		$('.conv-action:visible').not('.inactive').children().find('.conv-forward').click();
	});

	// Edit Draft
	ksBind(ks_keys['e'], function() {
		if ($('.form-reply:visible').length) {
			return;
		}
		$('.edit-draft-trigger:visible:first').click();
	});

	// Delete Conversation
	ksBind(ks_keys['d'], function() {
		if ($('.form-reply:visible').length) {
			return;
		}
		$('.conv-delete:visible:first').click();
	});

	// Next Conversation
	ksBind(ks_keys['k'], function() {
		if ($('.form-reply:visible').length) {
			return;
		}
		window.location.href = $('.conv-next-prev:visible a').eq(1).attr('href');
	});

	// Previous Conversation
	ksBind(ks_keys['j'], function() {
		if ($('.form-reply:visible').length) {
			return;
		}
		window.location.href = $('.conv-next-prev:visible a').eq(0).attr('href');
	});

	// Assign or Active
	ksBind(ks_keys['a'], function () {
		if (ksConvStatusMenuOpen()) {
			$("#conv-status .conv-status a[data-status='1']:first").click();
		} else {
			$('#conv-assignee:visible .btn:first').click();
		}
	});

	// Follow
	ksBind(ks_keys['o'], function() {
		$('.conv-follow:first').not('.hidden').click();
	});

	// Status or Spam
	ksBind(ks_keys['s'], function () {
		if (ksConvStatusMenuOpen()) {
			$("#conv-status .conv-status a[data-status='4']:first").click();
		} else {
			$("#conv-status:visible .btn:first").click();
	    }
	});

	// Closed
	ksBind(ks_keys['c'], function () {
		if (ksConvStatusMenuOpen()) {
			$("#conv-status .conv-status a[data-status='3']:first").click();
		}
	});

	// Pending
	ksBind(ks_keys['p'], function () {
		if (ksConvStatusMenuOpen()) {
			$("#conv-status .conv-status a[data-status='2']:first").click();
		}
	});

	// Add Tag
	ksBind(ks_keys['t'], function() {
		$('.conv-add-tags:visible').not('.inactive').click();
	});

	// New Conversation
	ksBind(ks_keys['q'], function() {
		if ($('.form-reply:visible').length) {
			return;
		}
		window.location.href =  $('.sidebar-buttons:visible:first .btn-trans:last').attr('href');
	});

	// Merge
	ksBind(ks_keys['m'], function() {
		$('.conv-action:visible').not('.inactive').children().find('[data-modal-on-show="initMergeConv"]:first').click();
	});

	// Move
	ksBind(ks_keys['v'], function() {
		$('.conv-action:visible').not('.inactive').children().find('[data-modal-on-show="initMoveConv"]:first').click();
	});

	// Workflow
	ksBind(ks_keys['w'], function() {
		$('.conv-action:visible').not('.inactive').children().find('[data-modal-on-show="initRunWorkflow"]:first').click();
	});
}

function ksConversationList()
{
	// Next Page
	ksBind(ks_keys['k'], function() {
		$('.table-conversations:visible .pager-next:first').not('.disabled').click();
	});

	// Previous Page
	ksBind(ks_keys['j'], function() {
		$('.table-conversations:visible .pager-prev:first').not('.disabled').click();
	});

	// New Conversation
	ksBind(ks_keys['c'], function() {
		if ($('.form-reply:visible').length) {
			return;
		}
		window.location.href =  $('.sidebar-buttons:visible:first .btn-trans:last').attr('href');
	});
}

function ksConvStatusMenuOpen() {
	return $("#conv-status.open:visible").length > 0;
}

function ksBind(key, callback)
{
	$(document).on('keyup', function(e) {
		// Skip inputs and editable areas.
		if (!e.target
			|| $(e.target).is(':input')
			|| $(e.target).attr('contentEditable') == 'true'
			|| e.ctrlKey
			|| e.altKey
			|| e.shiftKey
			|| e.metaKey
			|| $('.modal:visible').length
		) {
			if (!$('#conv-status.open:first').length) {
				return;
			}
		}
		//console.log(e.which);
		if (e.which == key) {
			callback();
		}
	});
}