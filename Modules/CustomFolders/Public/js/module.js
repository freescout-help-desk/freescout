/**
 * Module's JavaScript.
 */
function initCustomFoldersAdmin(msg_confirm_delete)
{
	$(document).ready(function() {

		initCustomFoldersForm('.custom-folders-form', 'update');

		// Delete
		$(".folder-delete-trigger").click(function(e){
			var button = $(this);

			showModalConfirm(msg_confirm_delete, 'folder-delete-ok', {
				on_show: function(modal) {
					var folder_id = button.attr('data-folder_id');
					modal.children().find('.folder-delete-ok:first').click(function(e) {
						button.button('loading');
						modal.modal('hide');
						fsAjax(
							{
								action: 'delete',
								folder_id: folder_id
							}, 
							laroute.route('mailboxes.custom_folders.ajax'), 
							function(response) {
								showAjaxResult(response);
								button.button('reset');
								if (typeof(response.msg_success) != "undefined") {
									$('#folder-'+folder_id).remove();
								}
							}
						);
					});
				}
			}, Lang.get("messages.delete"));
			e.preventDefault();
		});

		if ($('#custom-folders-index').length) {
			sortable('#custom-folders-index', {
			    handle: '.handle',
			    //forcePlaceholderSize: true 
			})[0].addEventListener('sortupdate', function(e) {
			    // ui.item contains the current dragged element.
			    var folders = [];
			    $('#custom-folders-index > .panel').each(function(idx, el){
				    folders.push($(this).attr('data-folder-id'));
				});
				fsAjax({
						action: 'update_sort_order',
						folders: folders,
					}, 
					laroute.route('mailboxes.custom_folders.ajax'), 
					function(response) {
						showAjaxResult(response);
					}
				);
			});
		}
	});
}

// Create custom folder
function initNewFolder(jmodal)
{
	$(document).ready(function(){
		initCustomFoldersForm('.new-folder-form', 'create');
	});
}

// Backend form
function initCustomFoldersForm(selector, action)
{
	$(selector).on('submit', function(e) {
		var folder_id = $(this).attr('data-folder_id');
		var data = $(this).serialize();
		data += '&mailbox_id='+getGlobalAttr('mailbox_id');
		data += '&action='+action;
		if (folder_id) {
			data += '&folder_id='+folder_id;
		}
		
		var button = $(this).children().find('button:first');
    	button.button('loading');

		fsAjax(data, 
			laroute.route('mailboxes.custom_folders.ajax'), 
			function(response) {
				if (typeof(response.status) != "undefined" && response.status == 'success') {
					if (typeof(response.msg_success) != "undefined") {
						// Update
						button.button('reset');
						showFloatingAlert('success', response.msg_success);

						$('#folder-'+folder_id+' .panel-title a:first span:first').html(response.name);
						$('#folder-'+folder_id+' .panel-title a:first span.tag').html(response.tag_name);
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

	var options = {
		ajax: {
			url: laroute.route('tags.ajax'),
			dataType: 'json',
			delay: 250,
			cache: true,
			data: function (params) {
				return {
					q: params.term,
					action: 'autocomplete'
					//use_id: 1
				};
			}
		},
		tags: true,
		allowClear: true,
		placeholder: ' ',
		minimumInputLength: 1,
		language: {
            inputTooShort: function(args) {
                return "";
            }
        }
	};

	if (action == 'create') {
		options.dropdownParent = $('.modal-dialog:visible:first');
	}

	$(selector+' select[name="tag_name"]').select2(options);
}
