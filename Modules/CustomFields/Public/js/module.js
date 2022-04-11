/**
 * Module's JavaScript.
 */
var cf_save_period = 5; // seconds
var cf_save_fields = [];

function initCustomFieldsAdmin()
{
	$(document).ready(function() {

		initCustomFieldsForm('.custom-field-form', 'update');

		// Delete
		$(".cf-delete-trigger").click(function(e){
			var button = $(this);

			showModalConfirm(Lang.get("messages.confirm_delete_custom_field"), 'cf-delete-ok', {
				on_show: function(modal) {
					var custom_field_id = button.attr('data-custom_field_id');
					modal.children().find('.cf-delete-ok:first').click(function(e) {
						button.button('loading');
						modal.modal('hide');
						fsAjax(
							{
								action: 'delete',
								custom_field_id: custom_field_id
							}, 
							laroute.route('mailboxes.custom_fields.ajax_admin'), 
							function(response) {
								showAjaxResult(response);
								button.button('reset');
								$('#custom-field-'+custom_field_id).remove();
							}
						);
					});
				}
			}, Lang.get("messages.delete"));
			e.preventDefault();
		});

		if ($('#custom-fields-index').length) {
			sortable('#custom-fields-index', {
			    handle: '.handle',
			    //forcePlaceholderSize: true 
			})[0].addEventListener('sortupdate', function(e) {
			    // ui.item contains the current dragged element.
			    var custom_fields = [];
			    $('#custom-fields-index > .panel').each(function(idx, el){
				    custom_fields.push($(this).attr('data-custom-field-id'));
				});
				fsAjax({
						action: 'update_sort_order',
						custom_fields: custom_fields,
					}, 
					laroute.route('mailboxes.custom_fields.ajax_admin'), 
					function(response) {
						showAjaxResult(response);
					}
				);
			});
		}
	});
}

// Create custom field
function initNewCustomField(jmodal)
{
	$(document).ready(function(){
		initCustomFieldsForm('.new-custom-field-form', 'create');
	});
}

// Backend form
function initCustomFieldsForm(selector, action)
{
	$(selector).on('submit', function(e) {
		var custom_field_id = $(this).attr('data-custom_field_id');
		var data = $(this).serialize();
		data += '&mailbox_id='+getGlobalAttr('mailbox_id');
		data += '&action='+action;
		if (custom_field_id) {
			data += '&custom_field_id='+custom_field_id;
		}
		
		var button = $(this).children().find('button:first');
    	button.button('loading');

		fsAjax(data, 
			laroute.route('mailboxes.custom_fields.ajax_admin'), 
			function(response) {
				if (typeof(response.status) != "undefined" && response.status == 'success') {
					if (typeof(response.msg_success) != "undefined") {
						// Update
						button.button('reset');
						showFloatingAlert('success', response.msg_success);
						var html = response.name;
						if (response.required) {
							html += '<i class="required-asterisk"></i>';
						}
						$('#custom-field-'+custom_field_id+' .panel-title a:first span:first').html(html);
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

	cfApplyOptionsListeners(selector);
}

function cfApplyOptionsListeners(selector)
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
		showModalConfirm(Lang.get("messages.confirm_delete_cf_option"), 'cf-option-remove-ok', {
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
function initCustomFields()
{
	$(document).ready(function() {
		if (!$('#custom-fields-form').length) {
			return;
		}
		$('#custom-fields-form .cf-type-date').flatpickr({allowInput: true});

		$('#custom-fields-form').on('submit', function(e) {
			e.preventDefault();
		});

		$('#custom-fields-form .custom-field :input').on('keyup keypress change', function(e) {
			$(this).attr('data-dirty', 1);
			cf_save_fields[$(this).attr('name')] = 1;
		});

		$('#custom-fields-form .custom-field :input').on('focusout', function(e) {
			if (!$(this).attr('data-dirty')) {
				return;
			}
			cf_save_fields[$(this).attr('name')] = 1;
			saveCustomFields();
		});

		$('#custom-fields-form select').on('change', function(e) {
			if ($(this).attr('required')) {
				return;
			}
			if ($(this).val()) {
				$(this).removeClass('placeholdered');
			} else {
				$(this).addClass('placeholdered');
			}
		});

		setInterval(saveCustomFields, cf_save_period*1000);

		fsAddFilter('conversation.can_submit', function(value) {
			return cfFilter(value);
		});

		fsAddFilter('conversation.can_change_user', function(value) {
			return cfFilter(value);
		});

		fsAddFilter('conversation.can_change_status', function(value) {
			return cfFilter(value);
		});

		cfInitAutosuggest();

		if (isNewConversation()) {
			$('#custom-fields-form .cf-show-fields').click(function(e) {
				$('#custom-fields-form .form-group.hidden').removeClass('hidden');
				cfInitAutosuggest();
				$(this).remove();
				e.preventDefault();
			});

			if (!getGlobalAttr('conversation_id')) {
				fs_reply_changed = true;
				saveDraft(false);
			}
			$('#custom-fields-form').addClass('cf-mode-create');
		}
	});
}

function cfInitAutosuggest()
{
	$('#custom-fields-form .cf-autosuggest:visible').each(function(index, el) {
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
				url: laroute.route('mailboxes.customfields.ajax_search'),
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

		// Force save on select
		input.on('select2:selecting', function (e) {
			cfSaveField(input)
		});
	});
}

function cfSaveField(input)
{
	input.attr('data-dirty', 1);
	cf_save_fields[input.attr('name')] = 1;
	saveCustomFields();
}

function cfFilter(value)
{
	if (!value) {
		return value;
	}
	var form = $('form#custom-fields-form');
	if (!form.length) {
		//form = $('#custom-fields-form').parent('form:first');
		return value;
	}
	if (!form[0].checkValidity()) {
		// var invisible_fields = form.children().find(':input[required]:hidden');

		// invisible_fields.removeAttr('required');
		$('#custom-fields-form :submit:first').click();
		//invisible_fields.attr('required', 'required');
		return false;
	}
	return value;
}

function saveCustomFields()
{
	if (!cf_save_fields.length) {
		return;
	}

	var fields = cf_save_fields;
	cf_save_fields = [];

	var data = {
		fields: {},
		action: 'save_fields',
		conversation_id: getGlobalAttr('conversation_id')
	};

	for (name in fields) {
		var input = $('#custom-fields-form :input[name="'+name+'"]');
		data['fields'][name] = input.val();
		input.removeAttr('data-dirty');
	}

	fsAjax(data, 
		laroute.route('mailboxes.custom_fields.ajax'), 
		function(response) {
			//var html = '<span class="text-danger"><i class="glyphicon glyphicon-ok"></i></span>';
			var html = '';

			if (typeof(response.status) != "undefined" && response.status == 'success') {
				html = '<span class="cf-result text-success"><i class="glyphicon glyphicon-ok"></i> '+Lang.get("messages.saved")+'</span>';
				for (name in fields) {
					el = $('#custom-field-'+name+' div:first .cf-result:first');
					if (el.length) {
						el.show();
					} else {
						$('#custom-field-'+name+' div:first').append(html);
						el = $('#custom-field-'+name+' div:first .cf-result:first');
					}
					el.fadeOut(3000);
				}
			} else {
				// Retry
				for (name in fields) {
					cf_save_fields[name] = 1;
				}
			}
		}, true
	);
}