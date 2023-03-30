(function () {
    var module_routes = [
    {
        "uri": "customers\/fields\/ajax-search",
        "name": "crm.ajax_search"
    },
    {
        "uri": "crm\/ajax",
        "name": "crm.ajax"
    },
    {
        "uri": "crm\/ajax-admin",
        "name": "crm.ajax_admin"
    }
];

    if (typeof(laroute) != "undefined") {
        laroute.add_routes(module_routes);
    } else {
        contole.log('laroute not initialized, can not add module routes:');
        contole.log(module_routes);
    }
})();