/**
 * Module's JavaScript.
 */
function gdprInitDeleteCustomer()
{
	var customer_id = getGlobalAttr('customer_id');
	var button = $('.gdpr-delete-customer-ok:visible');

	button.click(function() {
		button.button('loading');

		var data = {
			action: 'delete_customer',
			customer_id: customer_id
		};

		fsAjax(data, 
			laroute.route('gdpr.ajax'), 
			function(response) {
				if (isAjaxSuccess(response)) {
					if (typeof(response.msg_success) != "undefined") {
						button.button('reset');
						showFloatingAlert('success', response.msg_success);
						window.parent.postMessage('crm.refresh');
						setTimeout(function(){ 
							window.parent.postMessage('crm.close');
						}, 300);
						closeAllModals();
					}
				} else {
					showAjaxError(response);
					button.button('reset');
				}
			}, true
		);
	});
}