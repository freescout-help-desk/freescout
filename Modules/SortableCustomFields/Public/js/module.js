$(document).ready(function() {
	function removeDateSort(){
		var tempSort = $('.table-conversations').attr("data-sorting_sort_by")
		$('tr span[data-sort-by="'+tempSort+'"]').text(function(index, oldText) {
		 // Replace 'oldText' with 'newText' if a condition is met
		 if (oldText.includes('↑') || oldText.includes('↓')) {
			 return oldText.replace('↑', '').replace('↓', '');
		 } else {
			 return oldText;
		 }
	 }); 
	 }
	
	var originalMainFunction = convListSortingInit;
	convListSortingInit = function() {
		// Call the original function
		originalMainFunction();
		if ($('.custom-field-tr:contains("↑")').length > 0 || $('.custom-field-tr:contains("↓")').length > 0 ){
			removeDateSort()
			}
	};
});
