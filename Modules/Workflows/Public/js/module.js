/**
 * Module's JavaScript.
 */

function initUpdateWorkflow(type_automatic, msg_confirm_delete)
{
	$(document).ready(function(){
		$('#type').change(function(e) {
			$('.wf-type').addClass('hidden');
			if ($(this).val() == type_automatic) {
				$('.wf-type-auto').removeClass('hidden');
				$('#wf-conditions-tab').removeClass('hidden');
			} else {
				$('.wf-type-manual').removeClass('hidden');
				$('#wf-conditions-tab').addClass('hidden');
			}
			wfShowProperSaveButtons();
		});

		$('#apply_to_prev').change(function(e) {
			if ($(this).is(':checked')) {
				$('#apply-to-prev-alert').removeClass('hidden');
			} else {
				$('#apply-to-prev-alert').addClass('hidden');
			}
			wfShowProperSaveButtons();
		});

		$('#active').change(function(e) {
			wfShowProperSaveButtons();
		});

		wfShowProperSaveButtons();

		// AND
		$('.wf-block-and-trigger').click(function(e) {
			var clone = $(this).parents('.wf-blocks:first').children().find('.wf-and-block:first').clone();

			clone.removeAttr('data-applied');
			var children = clone.children();
			children.find('.wf-or-block:gt(0)').remove();
			children.find('.wf-or-block').removeAttr('data-applied').removeClass('has-error');
			wfResetInputs(children);
			children.find('.wf-block-operator').addClass('hidden');
			children.find('.wf-block-value').addClass('hidden');
			children.find('.select2').remove();

			clone.appendTo($(this).parent().prev());

			wfChangeNames(children.find('.wf-or-block'), true);

			wfApplyBlockListeners();
		});

		wfApplyBlockListeners();

		// Show current
		$('.wf-or-block .wf-block-type').change();

		// Delete
		$("#wf-delete-trigger").click(function(e){
			var button = $(this);

			showModalConfirm(msg_confirm_delete, 'wf-delete-ok', {
				on_show: function(modal) {
					var workflow_id = button.attr('data-wf_id');
					if (!workflow_id) {
						return;
					}
					modal.children().find('.wf-delete-ok:first').click(function(e) {
						button.button('loading');
						modal.modal('hide');
						fsAjax(
							{
								action: 'delete',
								workflow_id: workflow_id
							}, 
							laroute.route('mailboxes.workflows.ajax'), 
							function(response) {
								if (isAjaxSuccess(response)) {
									window.location.href = response.redirect_url;
								} else {
									showAjaxResult(response);
									button.button('reset');
								}
							}
						);
					});
				}
			}, Lang.get("messages.delete"));
			e.preventDefault();
		});
	});
}

function wfApplyBlockListeners()
{
	// Type
	$('.wf-or-block[data-applied!="1"] .wf-block-type').change(function(e) {
		var val = $(this).val();

		var block = $(this).parents('.wf-block').children();

		// Show operators
		block.find('.wf-block-operator').addClass('hidden');
		block.find('.wf-block-operator :input').attr('disabled', 'disabled');
		block.find('.wf-block-operator-'+val).removeClass('hidden');
		block.find('.wf-block-operator-'+val+' :input').removeAttr('disabled');

		// Clear values
		//wfResetInputs(block.find('.wf-block-value'));

		// Show values
		block.find('.wf-block-value').addClass('hidden');
		block.find('.wf-block-value :input').attr('disabled', 'disabled');
		block.find('.wf-block-value-'+val).removeClass('hidden');
		block.find('.wf-block-value-'+val+' :input').removeAttr('disabled');

		block.find('.wf-block-operator-'+val+' select').change();
	});

	$('.wf-or-block[data-applied!="1"] .wf-block-operator select').change(function(e) {
		var block = $(this).parents('.wf-block').children();
		var type = block.find('.wf-block-type:first').val();
		var val_wrapper = block.find('.wf-block-value-'+type);
		var values_visible_if = val_wrapper.attr('data-values-visible-if');

		if (values_visible_if) {
			var operators = values_visible_if.split(',');
			
			if ($.inArray($(this).val(), operators) !== -1) {
				block.find('.wf-block-value-'+type).removeClass('hidden');
				block.find('.wf-block-value-'+type+' :input').removeAttr('disabled');
			} else {
				block.find('.wf-block-value-'+type).addClass('hidden');
				block.find('.wf-block-value-'+type+' :input').attr('disabled');
			}
		}

		var multi_values = block.find('.wf-multi-value');
		if (multi_values.length) {
			multi_values.addClass('hidden').attr('disabled', 'disabled');
			block.find('.wf-multi-value-'+$(this).val()).removeClass('hidden').removeAttr('disabled');
		}
	}).change();

	// Remove OR block
	$('.wf-or-block[data-applied!="1"] .wf-or-block-remove').click(function(e) {
		if ($(this).parents('.wf-and-block:first').children().find('.wf-block').length == 1) {
			// Remove AND block
			if ($(this).parents('.wf-and-blocks:first').children('.wf-and-block').length > 1) {
				$(this).parents('.wf-and-block:first').remove();
			}	
		} else {
			$(this).parents('.wf-block:first').remove();
		}
	});
	$('.wf-or-block[data-applied!="1"] .wf-block-value :input').on('keyup keypress', function(e) {
		if ($(this).val()) {
			$(this).parents('.wf-or-block:first').removeClass('has-error');
		}
	});

	// Calendars
	$('.wf-or-block[data-applied!="1"] :input.input-date').flatpickr({allowInput: true});

	$('.wf-or-block').attr('data-applied', '1');

	// Add OR block
	$('.wf-and-block[data-applied!="1"] .wf-block-or-trigger').click(function(e) {
		var clone = $(this).parents('.wf-blocks:first').children().find('.wf-or-block:first:visible').clone();
		clone.removeAttr('data-applied').removeClass('has-error');
		wfResetInputs(clone.children());
		clone.children().find('.wf-block-operator').addClass('hidden');
		clone.children().find('.wf-block-value').addClass('hidden');
		clone.children().find('.select2').remove();
		clone.appendTo($(this).parent().prev());

		wfChangeNames(clone, false);

		wfApplyBlockListeners();
	});

	$('.wf-and-block').attr('data-applied', '1');

	// Email modal
	$('.wf-email-modal').off('click').on('click', function (e) {
		triggerModal($(this));
    	e.preventDefault();
	});

	$('.wf-multiselect').select2({
		multiple: true,
		tags: true
	});
}

function wfResetInputs(parent)
{
	parent.find(':input').val('');
	parent.find('select').prop("selectedIndex", 0);
}

function wfChangeNames(block, adding_and)
{
	var and_index = 0;
	if (typeof(adding_and) != "undefined" && adding_and) {
		and_index = parseInt(block.parents('.wf-and-blocks:first').attr('data-max-index'));
		and_index++;
		block.parents('.wf-or-blocks:first').attr('data-max-index', '-1');
	} else {
		// Use current and_index
		and_index = block.parents('.wf-and-block:first').index();
	}
	if (isNaN(and_index) || and_index < 0) {
		and_index = 0;
	}
	var or_index = parseInt(block.parents('.wf-or-blocks:first').attr('data-max-index'))+1;

	block.find('input,select').each(function(index, el){
		var name = $(this).attr('name');
		if (!name) {
			return;
		}
	    name = name.replace(/^([^\[]+)\[[^\]]+\]\[[^\]]+\]/, '$1['+and_index+']['+or_index+']');
	    $(this).attr('name', name);
	});
	block.parents('.wf-and-blocks:first').attr('data-max-index', and_index);
	block.parents('.wf-or-blocks:first').attr('data-max-index', or_index);
}

function initWorkflows()
{
	$(document).ready(function(){
		if ($('.wf-index').length) {

			var sortables = sortable('.wf-index', {
			    handle: '.handle',
			    //forcePlaceholderSize: true 
			});

			for (var i in sortables) {
				sortables[i].addEventListener('sortupdate', function(e) {
				    // ui.item contains the current dragged element.
				    var workflows = [];
				    $('.wf-index > .panel').each(function(idx, el){
					    workflows.push($(this).attr('data-wf-id'));
					});
					fsAjax({
							action: 'update_sort_order',
							workflows: workflows,
						}, 
						laroute.route('mailboxes.workflows.ajax'), 
						function(response) {
							showAjaxResult(response);
						}
					);
				});
			}
		}
	});
}

function initWfEmailForm(modal, trigger)
{
	// Show values
	var values = trigger.next().val();
	try {
		values = JSON.parse(values);
		modal.children().find('.wf-email-input').each(function(i, el) {
			if (typeof(values[$(this).attr('name')]) != "undefined") {
				if ($(this).is(':checkbox')) {
					if (values[$(this).attr('name')] == '1') {
						$(this).prop("checked", true);
					} else {
						$(this).prop("checked", false);
					}
				} else {
					$(this).val(values[$(this).attr('name')]);
				}
			}
		});
	} catch (e) {

	}

	summernoteInit('.wf-email-form-editor:first', {
		insertVar: true,
		disableDragAndDrop: false,
		callbacks: {
			onImageUpload: function(files) {
				if (!files) {
					return;
				}
				for (var i = 0; i < files.length; i++) {
					editorSendFile(files[i], false, false, '.wf-email-form-editor:first');
				}
			},
			onInit: function() {
				editorProcessInsertVar($('.wf-email-form-editor:first'), $(this));
			},
		}
		//excludeVars: ['%user.']
	});

	modal.children().find('.wf-email-form:first').submit(function(e) {
		var button = $(this).children().find('.btn-primary:first');
	    button.button('loading');
		
	    var values = {};

		modal.children().find('.wf-email-input').each(function(i, el) {
			if ($(this).is(':checkbox')) {
				if ($(this).is(':checked')) {
					values[$(this).attr('name')] = $(this).val();
				}
			} else {
				values[$(this).attr('name')] = $(this).val();
			}
		});

		trigger.next().val(JSON.stringify(values));
		e.preventDefault();
		modal.modal('hide');
	});
}

function initRunWorkflow(modal)
{
	$(document).ready(function(){
		modal.children().find('.btn').click(function(e) {
			wfRunManualWorkflow($(this), [getGlobalAttr('conversation_id')]);
			e.preventDefault();
		});
	});
}

function wfRunManualWorkflow(button, conversation_ids)
{
    button.button('loading');
	fsAjax(
		{
			action: 'run',
			workflow_id: button.attr('data-wf-id'),
			conversation_id: conversation_ids,
			folder_id: getQueryParam('folder_id')
		}, 
		laroute.route('mailboxes.workflows.ajax'), 
		function(response) {
			if (isAjaxSuccess(response)) {
				if (typeof(response.redirect_url) != "undefined" && response.redirect_url) {
					window.location.href = response.redirect_url;
				} else {
					window.location.href = '';
				}
			} else {
				showAjaxResult(response);
				button.button('reset');
			}
		}, true
	);
}

function initWorkflowsBulk()
{
	$(document).ready(function(){
		$('#bulk-workflow-list a').click(function(e) {
			var conversation_ids = getSelectedConversations();
			wfRunManualWorkflow($(this), conversation_ids)
			e.preventDefault();
		});
	});
}

function wfShowProperSaveButtons()
{
	var execute = false;
	if ($('#type').val() == 1) {
		// Automatic
		if ($('#active').is(':checked') && $('#apply_to_prev').is(':checked')) {
			execute = true;
		}
	} else {
		// Manual
	}
	$('.wf-save').addClass('hidden');
	if (execute) {
		$('.wf-save-execute').removeClass('hidden');
	} else {
		$('.wf-save-save').removeClass('hidden');
	}
}