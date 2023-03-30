/**
 * Module's JavaScript.
 */

function kbInitCategories(msg_delete)
{
	$(document).ready(function() {

		if ($('.kb-category-tree').length) {
			var elements = sortable('.kb-category-tree', {
			    handle: '.handle',
			    //forcePlaceholderSize: true 
			});
			for (var i in elements) {
				elements[i].addEventListener('sortupdate', function(e) {
				    // ui.item contains the current dragged element.
				    var categories = [];
				    $(e.target).children('.panel').each(function(idx, el){
					    categories.push($(this).attr('data-category-id'));
					});
					fsAjax({
							action: 'update_categories_sort_order',
							categories: categories,
						}, 
						laroute.route('mailboxes.knowledgebase.ajax_admin'), 
						function(response) {
							showAjaxResult(response);
						}
					);
				});
			}
		}

		// Delete
		$(".kb-category-delete").click(function(e){
			var button = $(this);

			showModalConfirm(msg_delete, 'kb-category-delete-ok', {
				on_show: function(modal) {
					var category_id = button.attr('data-category_id');
					modal.children().find('.kb-category-delete-ok:first').click(function(e) {
						button.button('loading');
						modal.modal('hide');
						fsAjax(
							{
								action: 'delete_category',
								category_id: category_id
							}, 
							laroute.route('mailboxes.knowledgebase.ajax_admin'), 
							function(response) {
								showAjaxResult(response);
								button.button('reset');
								if (isAjaxSuccess(response)) {
									window.location.href = '';
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

function kbInitArticles()
{
	$(document).ready(function() {

		if ($('#kb-articles-list').length && $("#kb-category-sorting").length) {
			sortable('#kb-articles-list', {
			    handle: '.handle',
			    //forcePlaceholderSize: true 
			})[0].addEventListener('sortupdate', function(e) {
			    // ui.item contains the current dragged element.
			    var articles = [];
			    $(e.target).children('.panel').each(function(idx, el){
				    articles.push($(this).attr('data-article-id'));
				});
				fsAjax({
						action: 'update_articles_sort_order',
						articles: articles,
					}, 
					laroute.route('mailboxes.knowledgebase.ajax_admin'), 
					function(response) {
						if (isAjaxSuccess(response)) {
							$('#kb-category-sorting').val(3).attr('data-forced-change', '1').change();
						}
						showAjaxResult(response);
					}, true
				);
			});
		}

        $("#kb-categories-select").change(function(e){
            window.location.href = $(e.target).val();
        });

        $("#kb-category-sorting").change(function(e){
        	var articles_order = $(e.target).val();
        	var forced_change = $(e.target).attr('data-forced-change');
        	$(e.target).removeAttr('data-forced-change');
			fsAjax({
					action: 'update_category_articles_order',
					category_id: $('#kb-categories-select').attr('data-category-id'),
					articles_order: articles_order
				}, 
				laroute.route('mailboxes.knowledgebase.ajax_admin'), 
				function(response) {
					showAjaxResult(response);
					if (isAjaxSuccess(response) && !forced_change) {
						window.location.href = '';
					}
				}
			);
        });
	});
}

function kbInitArticle(category_placeholder, msg_delete, redirect_url)
{
	$(document).ready(function() {
		var selector = '#kb-article-text';

		summernoteInit(selector, {
			insertVar: false,
			disableDragAndDrop: false,
			toolbar: [
			    ['style', ['style', 'bold', 'italic', 'underline', 'color', 'lists', 'paragraph', 'removeformat', 'link', 'table']],
			    ['insert', ['attachment', 'picture', 'video']],
			    ['view', ['codeview']]
			],
			buttons: {
				attachment: function (context) {
					var ui = $.summernote.ui;
					// create button
					var button = ui.button({
						contents: '<i class="glyphicon glyphicon-paperclip"></i>',
						tooltip: Lang.get("messages.upload_attachments"),
						container: 'body',
						click: function () {
							var element = document.createElement('div');
							element.innerHTML = '<input type="file" multiple>';
							var fileInput = element.firstChild;

							fileInput.addEventListener('change', function() {
								if (fileInput.files) {
									for (var i = 0; i < fileInput.files.length; i++) {
										editorSendFile(fileInput.files[i], true, false, '#kb-article-text');
						            }
							    }
							});

							fileInput.click();
						}
					});

					return button.render();   // return button as jquery object
				},
				removeformat: EditorRemoveFormatButton,
				lists: EditorListsButton
			},
			callbacks: {
				onInit: function() {
					$(selector).parent().children().find('.note-statusbar').remove();
				},
				onImageUpload: function(files) {
					if (!files) {
						return;
					}
					for (var i = 0; i < files.length; i++) {
						editorSendFile(files[i], false, false, '#kb-article-text');
					}
				}
			}
		});

		// Primary
		if ($('#kb-article-text-primary').length) {
			summernoteInit('#kb-article-text-primary', {
				insertVar: false,
				disableDragAndDrop: true,
				toolbar: []
			});
			$('#kb-article-text-primary').summernote('disable')
		}
		$('#kb-article-categories').select2({
			multiple: true,
			tags: true,
			// Causes JS error on clear
			//allowClear: true,
    		placeholder: category_placeholder
		});
		
		// Delete
		$("#kb-article-delete").click(function(e){
			var button = $(this);

			showModalConfirm(msg_delete, 'kb-article-delete-ok', {
				on_show: function(modal) {
					var article_id = button.attr('data-article_id');
					modal.children().find('.kb-article-delete-ok:first').click(function(e) {
						button.button('loading');
						modal.modal('hide');
						fsAjax(
							{
								action: 'delete_article',
								article_id: article_id
							}, 
							laroute.route('mailboxes.knowledgebase.ajax_admin'), 
							function(response) {
								showAjaxResult(response);
								button.button('reset');
								if (isAjaxSuccess(response)) {
									window.location.href = redirect_url;
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

function kbInitSettings()
{
	$(document).ready(function(){

		summernoteInit('#kb-settings-footer', {
			insertVar: false,
			disableDragAndDrop: true
		});

		$('#kb-show-preview').click(function(e) {
			$('body:first').append($('#kb-widget-code').val());

			e.preventDefault();

			$(this).fadeOut();
		});

		$('#kb-locales').select2({
			multiple: true
			//tags: true
			// Causes JS error on clear
			//allowClear: true,
    		//placeholder: category_placeholder
		}).on('select2:select', function (e) {
		       //Append selected element to the end of the list, otherwise it follows the same order as the dropdown
		       var element = e.params.data.element;
		       var $element = $(element);
		       $(this).append($element);
		       $(this).trigger("change");
		});

		$('#kb-widget-form input:visible,#kb-widget-form select:visible').on('change keyup', function(e) {
			$('#kb-widget-code-wrapper').addClass('hidden');
			$('#kb-widget-save-wrapper').removeClass('hidden');
		});

		$(".kb-colorpicker").colorpicker({
            customClass: 'colorpicker-2x',
            sliders: {
                saturation: {
                    maxLeft: 200,
                    maxTop: 200
                },
                hue: {
                    maxTop: 200
                },
                alpha: {
                    maxTop: 200
                }
            }
        }).on('changeColor.colorpicker', function(event) {
            $('#kb-widget-code-wrapper').addClass('hidden');
			$('#kb-widget-save-wrapper').removeClass('hidden');
			 return true;
        }).trigger("change");
	});
}