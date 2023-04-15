/**
 * Module's JavaScript.
 */

fsAddAction('reports.before_refresh', function(params) {
	var filters = params.data.filters;
	
	var route_params = {action:'export'};
	for (var param_name in filters) {
		var value = filters[param_name];
		if (param_name == 'tag') {
			value = $('#rpt-filters select[name="tag"] option[value="'+value+'"]').text();
		}
		if (param_name == 'from') {
			param_name = 'after';
		}
		if (param_name == 'to') {
			param_name = 'before';
		}
		if (param_name == 'custom_field') {
			for (var custom_field_id in value) {
				if (value[custom_field_id] !== '') {
					custom_field_name = $('#rpt-filters :input[name="custom_field['+custom_field_id+']"]:first').prev().text();
					route_params['f[%23'+custom_field_name+']'] = value[custom_field_id];
				}
			}
			continue;
		}
		route_params['f['+param_name+']'] = value;
	}

	// Apply default reports filters
	route_params['f[state][]'] = 2;
	route_params['f[status][0]'] = 1;
	route_params['f[status][1]'] = 2;
	route_params['f[status][2]'] = 3;
	
	var url = laroute.route('exportconversations.ajax_html', route_params);
	$('#rpt-btn-export').attr('data-remote', url);
});