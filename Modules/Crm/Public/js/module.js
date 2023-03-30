/**
 * Module's JavaScript.
 */

function initCrm()
{
	$(document).ready(function(){
		window.addEventListener("message", function(event) {
			if (typeof(event.data) != "undefined") {
				if (event.data == 'crm.refresh') {
					// Reload customers
					loadCustomers();
				}
				if (event.data == 'crm.close') {
					// Close customer modal
					$('.modal').modal('hide');
				}
			}
		});
	});
}

// Refresh customers after saving a customer in modal.
function crmTriggerRefresh()
{
	if (typeof(window.parent) != "undefined") {
		window.parent.postMessage('crm.refresh');
	}
}

function crmInitCustomerFieldsAdmin()
{
	$(document).ready(function() {

		crmInitCustomerFieldsForm('.crm-customer-field-form', 'customer_field_update');

		// Delete
		$(".crm-customer-field-delete").click(function(e){
			var button = $(this);

			showModalConfirm(Lang.get("messages.crm_confirm_delete_customer_field"), 'crm-delete-ok', {
				on_show: function(modal) {
					var customer_field_id = button.attr('data-customer_field_id');
					modal.children().find('.crm-delete-ok:first').click(function(e) {
						button.button('loading');
						modal.modal('hide');
						fsAjax(
							{
								action: 'customer_field_delete',
								customer_field_id: customer_field_id
							}, 
							laroute.route('crm.ajax_admin'), 
							function(response) {
								showAjaxResult(response);
								button.button('reset');
								$('#crm-customer-field-'+customer_field_id).remove();
							}
						);
					});
				}
			}, Lang.get("messages.delete"));
			e.preventDefault();
		});

		if ($('#cmr-customer-fields-index').length) {
			sortable('#cmr-customer-fields-index', {
			    handle: '.handle',
			    //forcePlaceholderSize: true 
			})[0].addEventListener('sortupdate', function(e) {
			    // ui.item contains the current dragged element.
			    var customer_fields = [];
			    $('#cmr-customer-fields-index > .panel').each(function(idx, el){
				    customer_fields.push($(this).attr('data-customer-field-id'));
				});
				fsAjax({
						action: 'customer_field_update_sort_order',
						customer_fields: customer_fields,
					}, 
					laroute.route('crm.ajax_admin'), 
					function(response) {
						showAjaxResult(response);
					}
				);
			});
		}
	});
}

// Create customer field
function crmInitNewCustomerField(jmodal)
{
	$(document).ready(function(){
		crmInitCustomerFieldsForm('.crm-new-customer-field-form', 'customer_field_create');
	});
}

// Backend form
function crmInitCustomerFieldsForm(selector, action)
{
	$(selector).on('submit', function(e) {
		var customer_field_id = $(this).attr('data-customer_field_id');
		var data = $(this).serialize();
		//data += '&mailbox_id='+getGlobalAttr('mailbox_id');
		data += '&action='+action;
		if (customer_field_id) {
			data += '&customer_field_id='+customer_field_id;
		}
		
		var button = $(this).children().find('button:first');
    	button.button('loading');

		fsAjax(data, 
			laroute.route('crm.ajax_admin'), 
			function(response) {
				if (isAjaxSuccess(response)) {
					if (typeof(response.msg_success) != "undefined") {
						// Update
						button.button('reset');
						showFloatingAlert('success', response.msg_success);
						var html = '';
						if (response.display) {
							html += '<small class="glyphicon glyphicon-eye-open"></small>';
						} else {
							html += '<small class="glyphicon glyphicon-eye-close "></small>';
						}
						html += ' '+response.name+' <small>(ID: '+customer_field_id+')</small>';
						if (response.required) {
							html += ' <i class="required-asterisk"></i>';
						}
						$('#crm-customer-field-'+customer_field_id+' .panel-title a:first span:first').html(html);
					} else {
						// Create
						window.location.href = '';
					}
				} else {
					showAjaxError(response);
					button.button('reset');
				}
				loaderHide();
			}
		);

		e.preventDefault();

		return false;
	});

	$(selector+' :input[name="type"]:first').on('change', function(e) {
		$(selector+' .cf-type :input').attr('disabled', 'disabled');
		$(selector+' .cf-type-'+$(this).val()+' :input').removeAttr('disabled');

		$(selector+' .cf-type').addClass('hidden');
		$(selector+' .cf-type-'+$(this).val()).removeClass('hidden');
	});

	crmApplyOptionsListeners(selector);
}

function crmApplyOptionsListeners(selector)
{
	$(selector+' .cf-option[data-inited!="1"] .cf-option-add').on('click', function(e) {
		var button = $(e.target);
		var container = button.parents('.cf-options:first');

		var new_option = container.children('.cf-option:first').clone();

		// Get max id
		var max_id = 1;
		container.children('.cf-option').find('input').each(function(index, el){
		    if ($(this).attr('data-option-id') > max_id) {
		    	max_id = $(this).attr('data-option-id')*1;
		    }
		});

		new_option.removeAttr('data-inited');
		new_option.children('input:first').attr('name', 'options['+(max_id+1)+']').attr('data-option-id', max_id+1).val('');
		new_option.appendTo(container);

		cfApplyOptionsListeners(selector);
	});

	$(selector+' .cf-option[data-inited!="1"] .cf-option-remove').on('click', function(e) {
		var button = $(e.target);
		var container = button.parents('.cf-options:first');
		if (container.children('.cf-option').length == 1) {
			return;
		}
		showModalConfirm(Lang.get("messages.crm_confirm_delete_option"), 'cf-option-remove-ok', {
			on_show: function(modal) {
				modal.children().find('.cf-option-remove-ok:first').click(function(e) {
					button.parents('.cf-option:first').addClass('cf-removed').children('input:first').attr('disabled', 'disabled');
					modal.modal('hide');
				});
			}
		}, Lang.get("messages.delete"));
	});

	$(selector+' .cf-option[data-inited!="1"] .cf-option-restore').on('click', function(e) {
		var button = $(e.target);
		button.parents('.cf-option:first').removeClass('cf-removed').children('input:first').removeAttr('disabled');
	});

	// To avoid double triggering
	$(selector+' .cf-option[data-inited!="1"]').attr('data-inited', '1');

	sortable(selector+' .cf-options', {
	    handle: '.cf-option-handle',
	    //forcePlaceholderSize: true 
	});
}

// Frontend
function crmInitCustomerFields()
{
	$(document).ready(function() {
		$('.crm-cf-type-date').flatpickr({allowInput: true});

		crmCfInitAutosuggest();
	});
}

function crmCfInitAutosuggest()
{
	$('.crm-cf-autosuggest:visible').each(function(index, el) {
		var input = $(this);
		if (input.data('select2')) {
			return;
		}
		
		var placeholder = '';
		if (!input.attr('required')) {
			placeholder = input.children('option:first').text();
		}
		var options = {
			ajax: {
				url: laroute.route('crm.ajax_search'),
				dataType: 'json',
				delay: 250,
				cache: true,
				data: function (params) {
					return {
						q: params.term,
						custom_field_id: input.attr('name'),
						page: params.page
					};
				}
			},
			allowClear: true,
			placeholder: placeholder,
			tags: true,
			minimumInputLength: 1,
			language: {
	            inputTooShort: function(args) {
	                return "";
	            }
	        }
		}
		input.select2(options);
	});
}

function crmInitDeleteCustomer()
{
	var customer_id = getGlobalAttr('customer_id');
	var button = $('.crm-delete-customer-ok:visible');

	button.click(function() {
		button.button('loading');

		var data = {
			action: 'delete_customer',
			customer_id: customer_id
		};

		fsAjax(data, 
			laroute.route('crm.ajax'), 
			function(response) {
				if (isAjaxSuccess(response)) {
					if (typeof(response.msg_success) != "undefined") {
						button.button('reset');
						showFloatingAlert('success', response.msg_success);
						window.parent.postMessage('crm.refresh');
						setTimeout(function(){ 
							window.parent.postMessage('crm.close');
						}, 300);
					}
				} else {
					showAjaxError(response);
					button.button('reset');
				}
			}, true
		);
	});
}

function crmInitDeleteWithoutConv()
{
	var button = $('.crm-delete-customers-ok:visible');

	button.click(function() {
		button.button('loading');

		var data = {
			action: 'delete_without_conv'
		};

		fsAjax(data, 
			laroute.route('crm.ajax'), 
			function(response) {
				if (isAjaxSuccess(response)) {
					if (typeof(response.msg_success) != "undefined") {
						showFloatingAlert('success', response.msg_success);
						window.location.href = "";
					} else {
						button.button('reset');
					}
				} else {
					showAjaxError(response);
					button.button('reset');
				}
			}, true
		);
	});
}

function crmImportModal()
{
	$(document).ready(function(){

		$('.crm-import-back:first').click(function(e) {
			$('.crm-import-step1').removeClass('hidden');
			$('.crm-import-step2').addClass('hidden');
			var button = $('.crm-import-form button:visible:first');
			button.addClass('hidden');
			button.prev().removeClass('hidden');

			e.preventDefault();
		});

		$('.crm-import-form:visible:first').on('submit', function(e) {
			var file_input = $('.crm-import-file:first');
			
			ajaxSetup();

			var data = new FormData();
			data.append('file', file_input[0].files[0]);
			data.append("separator", $('.crm-import-form:visible:first :input[name="separator"]').val());
			data.append("enclosure", $('.crm-import-form:visible:first :input[name="enclosure"]').val());
			data.append("encoding", $('.crm-import-form:visible:first :input[name="encoding"]').val());
			data.append("skip_header", $('.crm-import-form:visible:first :input[name="skip_header"]:checked').length);

			var button = $(this).children().find('button:visible');
	    	button.button('loading');

			if ($('.crm-import-step1:visible').length) {
				// Upload				
				data.append("action", 'import_parse');

				$.ajax({
					url: laroute.route('crm.ajax_admin'), 
					data: data,
					cache: false,
					contentType: false,
					processData: false,
					type: 'POST',
					success: function(response){
						button.button('reset');
						if (isAjaxSuccess(response)) {
							$('.crm-import-step1').addClass('hidden');
							$('.crm-import-step2').removeClass('hidden');
							button.addClass('hidden');
							button.next().removeClass('hidden');
							// Show mapping
							var options_html = '<option value=""></option>';
							for (var i in response.cols) {
								options_html += '<option value="'+i+'">'+response.cols[i]+'</option>';
							}
							$('.crm-import-mapping').html(options_html);
							$('.crm-import-mapping').each(function(i, el){
							    $(this).children('option:eq('+(i+1)+')').attr('selected', 'selected');
							});
						} else {
							showAjaxError(response);
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						showFloatingAlert('error', Lang.get("messages.error_occured"));
						button.button('reset');
					}
				});

			} else {
				// Import
				data.append("action", 'import_import');
				var mapping = {};
				$('.crm-import-mapping').each(function(i, el){
				    mapping[$(this).attr('data-field-name')] = $(this).val();
				});
				data.append("mapping", JSON.stringify(mapping));

				$.ajax({
					url: laroute.route('crm.ajax_admin'), 
					data: data,
					cache: false,
					contentType: false,
					processData: false,
					type: 'POST',
					success: function(response){
						button.button('reset');
						if (isAjaxSuccess(response)) {
							$('.crm-import-result').html(response.result_html).removeClass('hidden');
							$('.crm-import-notice').addClass('hidden');
							$('.crm-import-step2').addClass('hidden');
							button.addClass('hidden');
						} else {
							showAjaxError(response);
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						showFloatingAlert('error', Lang.get("messages.error_occured"));
						button.button('reset');
					}
				});
			}

			e.preventDefault();
			return false;
		});
	});
}

function crmInitVars(customer_vars)
{
	fsAddFilter('editor.vars', function(vars) {
		vars.customer = {...vars.customer, ...customer_vars};
		return vars;
	});
}