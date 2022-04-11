(function () {
    var module_routes = [
    {
        "uri": "mailbox\/saved-replies\/ajax-html\/{action}",
        "name": "mailboxes.saved_replies.ajax_html"
    },
    {
        "uri": "mailbox\/saved-replies\/ajax",
        "name": "mailboxes.saved_replies.ajax"
    }
];

    if (typeof(laroute) != "undefined") {
        laroute.add_routes(module_routes);
    } else {
        contole.log('laroute not initialized, can not add module routes:');
        contole.log(module_routes);
    }
})();