(function () {
    var module_routes = [
    {
        "uri": "mailbox\/custom-fields\/ajax-html\/{action}",
        "name": "mailboxes.custom_fields.ajax_html"
    },
    {
        "uri": "custom-fields\/ajax-admin",
        "name": "mailboxes.custom_fields.ajax_admin"
    },
    {
        "uri": "custom-fields\/ajax",
        "name": "mailboxes.custom_fields.ajax"
    },
    {
        "uri": "custom-fields\/ajax-search",
        "name": "mailboxes.customfields.ajax_search"
    }
];

    if (typeof(laroute) != "undefined") {
        laroute.add_routes(module_routes);
    } else {
        contole.log('laroute not initialized, can not add module routes:');
        contole.log(module_routes);
    }
})();