(function () {
    var module_routes = [
    {
        "uri": "export-conversations\/ajax-html\/{action}",
        "name": "exportconversations.ajax_html"
    }
];

    if (typeof(laroute) != "undefined") {
        laroute.add_routes(module_routes);
    } else {
        contole.log('laroute not initialized, can not add module routes:');
        contole.log(module_routes);
    }
})();