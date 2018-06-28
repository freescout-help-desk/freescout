var fs_sidebar_menu_applied = false;

$(document).ready(function(){

	// Tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Popover
    $('[data-toggle="popover"]').popover({
	    container: 'body'
	});

    // Submenu
    $('.sidebar-menu-toggle').click(function(event) {
    	event.stopPropagation();
		$(this).parent().children('.sidebar-menu:first').toggleClass('active');
		$(this).toggleClass('active');
		if (!fs_sidebar_menu_applied) {
			$('body').click(function() {
				$('.sidebar-menu, .sidebar-menu-toggle').removeClass('active');
			});
		}
	});
});

function mailboxUpdateInit(from_name_custom)
{
	// https://github.com/Studio-42/elFinder/wiki/Integration-with-Multiple-Summernote-%28fixed-functions%29
	// https://stackoverflow.com/questions/21628222/summernote-image-upload
	// https://www.kerneldev.com/2018/01/11/using-summernote-wysiwyg-editor-with-laravel/
	// https://gist.github.com/abr4xas/22caf07326a81ecaaa195f97321da4ae
	$('#signature').summernote({
		height: 120,
		toolbar: [
		    // [groupName, [list of button]]
		    ['style', ['bold', 'italic', 'underline', 'ul', 'ol', 'link', 'codeview']],
		]
	});

    $('#from_name').change(function(event) {
		if ($(this).val() == from_name_custom) {
			$('#from_name_custom_container').removeClass('hidden');
		} else {
			$('#from_name_custom_container').addClass('hidden');
		}
	});
}