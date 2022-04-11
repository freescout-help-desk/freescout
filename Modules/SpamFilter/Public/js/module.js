function initSpamFilterSettings()
{
	$(document).ready(function(){
		$('#spam-filter-reset').click(function(e) {
			var button = $(this);

			var data = {
				action: 'reset',
				mailbox_id: $('#stat-memory').val()
			};

	    	button.button('loading');
			fsAjax(data, laroute.route('spam_filter.ajax'), 
				function(response) {
					showAjaxResult(response);
					button.button('reset');
					if (isAjaxSuccess(response)) {
						location.reload();
					}
				}
			);
			e.preventDefault();
		});
	});
}