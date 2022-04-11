/**
 * Module's JavaScript.
 */

function initCustomization()
{
	$(document).ready(function(){
		$('.cust-img-remove').click(function(e) {
			var wrapper = $(this).parents('.cust-img-wrapper:first');

			wrapper.children('.cust-img-remove-input:first').val(1);
			wrapper.children().find('.cust-img-custom').hide();
			wrapper.children().find('.cust-img-default').removeClass('hidden');

			$(this).remove();

			e.preventDefault();
		});

		summernoteInit('#customization_footer', {
			insertVar: false,
			disableDragAndDrop: true
			/*callbacks: {
				onInit: function() {
					$(selector).parent().children().find('.note-statusbar').remove();
				}
			}*/
		});
	});
}
