(function () {
    var module_routes = [
    {
        "uri": "help\/{mailbox_id}\/upload",
        "name": "enduserportal.upload"
    }
];

    if (typeof(laroute) != "undefined") {
        laroute.add_routes(module_routes);
    } else {
        contole.log('laroute not initialized, can not add module routes:');
        contole.log(module_routes);
    }
})();