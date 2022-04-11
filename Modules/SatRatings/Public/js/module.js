/**
 * Module's JavaScript.
 */

function initSatRatingsSettings(confirm_reset_text)
{
	$(document).ready(function(){
		summernoteInit('#ratings_text', {
			buttons: {
			    insertvar: function (context) {
					var ui = $.summernote.ui;

					// todo: fallback=
					var contents = 
						'<select class="form-control summernote-inservar" tabindex="-1">'+
						    '<option value="">'+Lang.get("messages.insert_var")+' ...</option>'+
						    '<optgroup label="'+Lang.get("messages.mailbox")+'">'+
						        '<option value="{%mailbox.email%}">'+Lang.get("messages.email")+'</option>'+
						        '<option value="{%mailbox.name%}">'+Lang.get("messages.name")+'</option>'+
						    '</optgroup>'+
					    '</select>';

					// create button
					var button = ui.button({
						contents: contents,
						tooltip: Lang.get("messages.insert_var"),
						container: 'body'
					});

					return button.render();   // return button as jquery object
				}
			}
		});

		// Reset to defaults
		$('.reset-trigger').click(function(e) {
			showModalConfirm(confirm_reset_text, 'confirm-reset', {
				on_show: function(modal) {
					modal.children().find('.confirm-reset:first').click(function(e) {
						$('#ratings_placement_1').prop("checked", true);

						var def_text = $('#default_ratings_text').val();
						$('#ratings_text').val(def_text);
						setSummernoteText($('#ratings_text'), def_text);

						modal.modal('hide');
					});
				}
			});
			e.preventDefault();
		});
	});
}

function initSatRatingsTrans(confirm_reset_text)
{
	$(document).ready(function(){
		// Reset to defaults
		$('.reset-trigger').click(function(e) {
			showModalConfirm(confirm_reset_text, 'confirm-reset', {
				on_show: function(modal) {
					modal.children().find('.confirm-reset:first').click(function(e) {
						$('#form-trans :input').each(function(i, el) {
							var jel = $(el);
							if (jel.attr('data-default')) {
								jel.val(jel.attr('data-default'));
							}
						});
						modal.modal('hide');
					});
				}
			});
			e.preventDefault();
		});
	});
}

