/**
 * Module's JavaScript.
 */

function eupInitSettings()
{
	$(document).ready(function(){

        summernoteInit('#eup-settings-footer', {
            insertVar: false,
            disableDragAndDrop: false,
            callbacks: {
                onImageUpload: function(files) {
                    if (!files) {
                        return;
                    }
                    for (var i = 0; i < files.length; i++) {
                        editorSendFile(files[i], false, false, '#eup-settings-footer');
                    }
                }
            }
        });
        
        summernoteInit('#eup-privacy', {
            toolbar: [
                ['style', ['style', 'bold', 'italic', 'underline', 'color', 'lists', 'paragraph', 'removeformat', 'link', 'table']],
                ['insert', ['picture']],
                ['view', ['codeview']]
            ],
            insertVar: false,
            disableDragAndDrop: false,
            callbacks: {
                onImageUpload: function(files) {
                    if (!files) {
                        return;
                    }
                    for (var i = 0; i < files.length; i++) {
                        editorSendFile(files[i], false, false, '#eup-privacy');
                    }
                }
            }
        });

		$('#eup-show-preview').click(function(e) {
			$('body:first').append($('#eup-widget-code').val());

			e.preventDefault();

			$(this).fadeOut();
		});

		$('#eup-widget-form input:visible,#eup-widget-form select:visible').on('change keyup', function(e) {
			$('#eup-widget-code-wrapper').addClass('hidden');
			$('#eup-widget-save-wrapper').removeClass('hidden');
		});

		$(".eup-colorpicker").colorpicker({
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
            $('#eup-widget-code-wrapper').addClass('hidden');
			$('#eup-widget-save-wrapper').removeClass('hidden');
			 return true;
        }).trigger("change");

        $('#eup_consent').change(function(e) {
            $('#eup-privacy-container').toggleClass('hidden'); 
        });
	});
}