/**
 * Module's JavaScript.
 */
function initConvTags(remove_title)
{
	$(document).ready(function(){
		// Add tag
		/*$('#add-tag-wrap input').on('keyup', function(e) {
			if(e.keyCode == 13) {
		        addTag(remove_title);
		    }
		});*/

		initTagSelector('.conv-add-tags:first', remove_title);

		// Remove tag
		initRemoveTags();
	});
}

function initTagSelector(selector, remove_title)
{
	$(selector).click(function(e) {
		$("#add-tag-wrap .tag-input:not(.select2-hidden-accessible)").select2({
     		dropdownCssClass: "select2-multi-dropdown",
     		containerCssClass: "select2-tag-input",
			multiple: true,
			//maximumSelectionLength: 1,
			//placeholder: input.attr('placeholder'),
			width: '100%',
			minimumInputLength: 1,
			tags: true,
			tokenSeparators: [",", ", "],
			ajax: {
				url: laroute.route('tags.ajax'),
				dataType: 'json',
				delay: 250,
				cache: true,
				data: function (params) {
					return {
						q: params.term,
						action: 'autocomplete'
					};
				}
			},
			createTag: function (params) {
			    return {
					id: params.term,
					text: params.term,
					newOption: true
			    }
			},
			templateResult: function (data) {
			    var $result = $("<span></span>");

			    $result.text(data.text);

			    if (data.newOption) {
			     	$result.append(" <em>("+Lang.get("messages.add_lower")+")</em>");
			    }

			    return $result;
			},
		}).on('select2:closing', function(e) {
			var params = e.params;
			var select = $(e.target);

			var value = select.next('.select2:first').children().find('.select2-search__field:first').val();
			value = value.trim();
			if (!value) {
				return;
			}

			// Don't select an item if the close event was triggered from a select or
			// unselect event
		    if (params && params.args && params.args.originalSelect2Event != null) {
				var event = params.args.originalSelect2Event;

				if (event._type === 'select' || event._type === 'unselect') {
					return;
				}
		    }

			var data = select.select2('data');

			// Check if select already has such option
		    for (i in data) {
		    	if (data[i].id == value) {
		    		return;
		    	}
		    }
			
		    addSelect2Option(select, {
		        id: value,
		        text: value,
		        selected: true
		    });
		});

		// To avoid closing dropdown
		$("#add-tag-wrap .select2-selection").on("click", function(){
		    return false; // prevent propagation
		});

		// To save new conversation
		if ($('#form-create').length) {
			fs_reply_changed = true;
			saveDraft(false, true);
		}

		// Set focus
		setTimeout(function(){
		    $("#add-tag-wrap .tag-input").select2('open');
		    $('.select2-search__field').focus();
		}, 100);
	});

	$('#add-tag-wrap .btn').click(function(e) {
		addTag(remove_title);
		e.preventDefault();
		e.stopPropagation();
		$('.conv-add-tags:visible:first').dropdown('toggle');
	});
}

function initRemoveTags()
{
	$('#conv_tags .tag-remove').click(function(e) {
		var tag_name = $(this).parent().children('.tag-name:first').text();
		
		$(this).parent().remove();
		fsAjax({
				action: 'remove',
				tag_name: tag_name,
				conversation_id: getGlobalAttr('conversation_id')
			}, 
			laroute.route('tags.ajax'), 
			function(response) {
				if (typeof(response.status) != "undefined" && response.status == 'success') {
					// Do nothing
				} else {
					showAjaxError(response);
				}
			},
			true
		);
		e.preventDefault();
	});		
}

function addTag(remove_title)
{
	var input = $('#add-tag-wrap .tag-input:first');
	var button = $('#add-tag-wrap .btn:first');

	//var tag_names = input.val().split(',');
	var tag_names = input.val();

	if (!tag_names.length) {
		return;
	}
	for (i in tag_names) {
		tag_names[i] = tag_names[i].trim();
	}

	// Check if there is already such tag
	$('#conv_tags .tag-name').each(function(i, el) {
		if ($(el).text() == tag_names[i]) {
			delete tag_names[i];
		}
	});

	if (!tag_names.length) {
		return;
	}

	input.attr('disabled', 'disabled');
	button.attr('disabled', 'disabled');
	
	var conversation_id = getGlobalAttr('conversation_id');
	var is_bulk = false;
	if (!conversation_id) {
		// Bulk
		conversation_id = getSelectedConversations();
		is_bulk = true;
	}

	fsAjax({
			action: 'add',
			tag_names: tag_names,
			conversation_id: conversation_id
		}, 
		laroute.route('tags.ajax'), 
		function(response) {
			if (typeof(response.status) != "undefined" && response.status == 'success' &&
				typeof(response.tags) != "undefined" && response.tags
			) {
				// Show tags
				var html = '';
				for (i in response.tags) {
					tag = response.tags[i];
					if (!is_bulk) {
						html += '<span class="tag"><a class="tag-name" href="'+tag.url+'" target="_blank">'+htmlEscape(tag.name)+'</a> <a class="tag-remove" href="#" title="'+remove_title+'">×</a></span>';
					} else {
						html += '<span class="tag">'+htmlEscape(tag.name)+'</span>';
					}
				}
				if (!is_bulk) {
					$('#conv_tags').append(html);
				} else if ($.isArray(conversation_id)) {
					// Bulk
					for (i in conversation_id) {
						var tags_container = $('.conv-row[data-conversation_id="'+conversation_id[i]+'"] .conv-tags');
						if (tags_container.length) {
							$('.conv-row[data-conversation_id="'+conversation_id[i]+'"] .conv-tags').append(html);
						} else {
							$('.conv-row[data-conversation_id="'+conversation_id[i]+'"] .conv-subject p:first').prepend('<span class="conv-tags">'+html+'</span>');
						}
					}
				}
				initRemoveTags();
			} else {
				showAjaxError(response);
			}
			input.removeAttr('disabled');
			input.children().prop("selected", false);
            input.trigger("change");
			button.removeAttr('disabled');
		},
		true,
		function(response) {
			showFloatingAlert('error', Lang.get("messages.ajax_error"));
			input.removeAttr('disabled').val('');
			button.removeAttr('disabled');
		}
	);
}

function initTagsBulk()
{
	$(document).ready(function(){
		initTagSelector('#conversations-bulk-actions .conv-add-tags:first', '');
	});
}

function initEditTag(jmodal)
{
	$(document).ready(function() {
		$('.tag-update-form').on('submit', function(e) {

			var tag_id = $(this).attr('data-tag-id');
			var data = $(this).serialize();

			data += '&tag_id='+tag_id;
			data += '&action=update';
			
			var button = $(this).children().find('button:first');
	    	button.button('loading');

			fsAjax(data, 
				laroute.route('tags.ajax'), 
				function(response) {
					if (isAjaxSuccess(response)) {
						window.location.href = '';
					} else {
						showAjaxError(response);
						button.button('reset');
					}
					loaderHide();
				}
			);

			e.preventDefault();
		});

		$('.tag-update-form .tag-delete-forever:first').click(function(e) {
			var form = $(this).parents('.tag-update-form:first');
			var tag_id = form.attr('data-tag-id');
			var button = $(this);

			showModalConfirm(form.attr('data-confirm-delete'), 'tag-confirm-delete', {
				on_show: function(modal) {

					modal.children().find('.tag-confirm-delete:first').click(function(e) {

						button.button('loading');
						modal.modal('hide');

						var data = {
							action: 'delete_forever',
							tag_id: tag_id
						};

						fsAjax(data, 
							laroute.route('tags.ajax'), 
							function(response) {
								if (isAjaxSuccess(response)) {
									window.location.href = '';
								} else {
									showAjaxError(response);
									button.button('reset');
								}
								loaderHide();
							}, true
						);
					});
				}
			}, Lang.get("messages.delete"));

			e.preventDefault();
		});

		$('.tag-update-form:visible .tag-colors a').click(function(e) {
			$('.tag-update-form:visible .tag-colors a').removeClass('active');
			$(this).addClass('active');
			$('.tag-update-form:visible [name="color"]:first').val($(this).attr('data-color'));
			e.preventDefault();
		});
	});
}