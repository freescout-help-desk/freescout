(function () {
    var module_routes = [
    {
        "uri": "mailbox\/custom-folders\/ajax-html\/{action}",
        "name": "mailboxes.custom_folders.ajax_html"
    },
    {
        "uri": "mailbox\/custom-folders\/ajax",
        "name": "mailboxes.custom_folders.ajax"
    }
];

    if (typeof(laroute) != "undefined") {
        laroute.add_routes(module_routes);
    } else {
        contole.log('laroute not initialized, can not add module routes:');
        contole.log(module_routes);
    }
})();