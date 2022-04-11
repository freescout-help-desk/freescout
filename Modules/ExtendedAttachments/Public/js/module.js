/**
 * Module's JavaScript.
 */

function eaInitDeleteModal()
{
	$(document).ready(function(){

		$('.ea-confirm-delete:visible:first').click(function(e) {
			
			var attachment_id = $(this).attr('data-attachment-id');

			var button = $(this);
	    	button.button('loading');

			fsAjax({
					action: 'delete_attachment',
					attachment_id: attachment_id
				}, 
				laroute.route('extendedattachments.ajax'), 
				function(response) {
					if (isAjaxSuccess(response)) {
						$('.thread-attachments li[data-attachment-id="'+attachment_id+'"').hide();
						$('.attachments-list li[data-attachment-id="'+attachment_id+'"').hide();
						button.parents('.modal:first').modal('hide');
					} else {
						showAjaxError(response);
					}
					button.button('reset');
				}, true
			);

			return false;
		});

	});
}
