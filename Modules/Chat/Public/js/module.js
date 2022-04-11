/**
 * Module's JavaScript.
 */

function chatInitSettings()
{
	$(document).ready(function(){

		$('#chat-show-preview').click(function(e) {
			$('body:first').append($('#chat-widget-code').val());

			e.preventDefault();

			$(this).fadeOut();
		});

		$('#chat-widget-form input:visible,#chat-widget-form select:visible').on('change keyup', function(e) {
			$('#chat-widget-code-wrapper').addClass('hidden');
			$('#chat-widget-save-wrapper').removeClass('hidden');
		});

		$(".chat-colorpicker").colorpicker({
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
            $('#chat-widget-code-wrapper').addClass('hidden');
			$('#chat-widget-save-wrapper').removeClass('hidden');
			 return true;
        }).trigger("change");
	});
}