(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define([], factory);
	else if(typeof exports === 'object')
		exports["Polycast"] = factory();
	else
		root["Polycast"] = factory();
})(this, function() {
return /******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};

/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {

/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId])
/******/ 			return installedModules[moduleId].exports;

/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			exports: {},
/******/ 			id: moduleId,
/******/ 			loaded: false
/******/ 		};

/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);

/******/ 		// Flag the module as loaded
/******/ 		module.loaded = true;

/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}


/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;

/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;

/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";

/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ function(module, exports) {

	(function() {

	    this.Polycast = function() {

	        this.options = {};

	        this.channels = {};

	        this.timeout = null;

	        this.connected = false;

	        this.events = {};

	        var defaults = {
	            url: null,
	            polling: 5,
	            token: null
	        };

	        if (arguments[0] && typeof arguments[0] === "object") {
	            this.options = this.extend(defaults, arguments[0]);
	        }else if(arguments[0] && typeof arguments[0] === "string"){
	            if (arguments[1] && typeof arguments[1] === "object") {
	                var opts = this.extend({url: arguments[0]}, arguments[1]);
	                this.options = this.extend(defaults, opts);
	            }else{
	                this.options = this.extend(defaults, {url: arguments[0]});
	            }
	        }else{
	            throw "Polycast url must be defined!";
	        }

	        this.init();

	        return this;

	    };

	    this.Polycast.prototype = {
	        init: function(){

	            var PolycastObject = this;

	            var params = this.serialize({
	                polling: this.options.polling,
	                '_token': this.options.token
	            });

	            var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
	            xhr.open('POST', this.options.url + '/connect');
	            xhr.onreadystatechange = function() {
	                if (xhr.readyState > 3 && xhr.status === 200) {
	                    response = JSON.parse(xhr.responseText);
	                    if(response.status == 'success'){
	                        PolycastObject.connected = true;
	                        PolycastObject.setTime(response.time);
	                        PolycastObject.setTimeout();
	                        console.log('Polycast connection established!');
	                        PolycastObject.fire('connect', PolycastObject);
	                    }
	                }
	            };
	            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
	            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	            xhr.send(params);

	            return this;
	        },
	        reconnect: function(){
	            if(this.connected){
	                return;
	            }
	            this.init();
	            return this;
	        },
	        on: function(event, callback){
	            if(this.events[event] === undefined){
	                this.events[event] = [];
	            }
	            this.events[event].push(callback);
	            return this;
	        },
	        fire: function(event, data){
	            if(this.events[event] === undefined){
	                this.events[event] = [];
	            }
	            for(var callback in this.events[event]){
	                if (this.events[event].hasOwnProperty(callback)) {
	                    var func = this.events[event][callback];
	                    func(data);
	                }
	            }
	        },
	        disconnect: function(){
	            this.connected = false;
	            clearTimeout(this.timeout);
	            this.timeout = null;
	            this.fire('disconnect', this);
	            return this;
	        },
	        extend: function(source, properties) {
	            var property;
	            for (property in properties) {
	                if (properties.hasOwnProperty(property)) {
	                    source[property] = properties[property];
	                }
	            }
	            return source;
	        },
	        setTime: function(time){
	            this.options.time = time;
	        },
	        setTimeout: function(){
	            var PolycastObject = this;
	            this.timeout = setTimeout(function(){
	                PolycastObject.fetch();
	            }, (this.options.polling * 1000));
	        },
	        fetch: function(){
	            this.request();
	        },
	        request: function(){
	            var PolycastObject = this;

	            //serialize just the channel names and events attached
	            var channelData = {};
	            for (var channel in this.channels) {
	                if (this.channels.hasOwnProperty(channel)) {
	                    if(channelData[channel] === undefined){
	                        channelData[channel] = [];
	                    }
	                    for (var i = 0; i < this.channels[channel].length; i++) {
	                        var obj = this.channels[channel][i];
	                        var events = obj.events;
	                        for(var key in events){
	                            channelData[channel].push(key);
	                        }
	                    }
	                }
	            }

	            var data = {
	                time: this.options.time,
	                channels: channelData,
	                '_token': this.options.token
	            };

	            var params = this.serialize(data);

	            var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
	            xhr.open('POST', this.options.url + '/receive');
	            xhr.onreadystatechange = function() {
	                if (xhr.readyState > 3 && xhr.status === 200) {
	                    PolycastObject.parseResponse(xhr.responseText);
	                }
	            };
	            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
	            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	            xhr.send(params);

	            return xhr;
	        },
	        serialize: function(obj, prefix) {
	            var str = [];
	            for(var p in obj) {
	                if (obj.hasOwnProperty(p)) {
	                    var k = prefix ? prefix + "[" + p + "]" : p, v = obj[p];
	                    str.push(typeof v == "object" ?
	                        this.serialize(v, k) :
	                    encodeURIComponent(k) + "=" + encodeURIComponent(v));
	                }
	            }
	            return str.join("&");
	        },
	        parseResponse: function(response){
	            response = JSON.parse(response);
	            if(response.status == 'success'){
	                //do something
	                this.setTime(response.time);

	                for (var payload in response.payloads) {
	                    if (response.payloads.hasOwnProperty(payload)) {
	                        //foreach payload channels defer to channel class
	                        for (i = 0; i < response.payloads[payload]['channels'].length; ++i) {
	                            var channel = response.payloads[payload]['channels'][i];
	                            //console.log('Polycast channel: ' + channel + ' received event: ' + response.payloads[payload]['event']);
	                            for(index = 0; index < this.channels[channel].length; ++index){
	                                //console.log(response.payloads[payload]);
	                                this.channels[channel][index].fire(response.payloads[payload]);
	                                //this.channels[channel][index].fire(response.payloads[payload]['event'], response.payloads[payload]['payload'], response.payloads[payload]['delay']);
	                            }
	                        }
	                    }
	                }

	                //lets do it again!
	                this.setTimeout();
	            }
	        },
	        subscribe: function(channel){

	            var $channel = new PolycastChannel({channel: channel});
	            if(this.channels[channel] === undefined){
	                this.channels[channel] = [];
	            }
	            this.channels[channel].push($channel);
	            return $channel;
	        }
	    };

	    this.PolycastChannel = function(){

	        this.options = {};

	        this.events = {};

	        var defaults = {
	            channel: null
	        };

	        if (arguments[0] && typeof arguments[0] === "object") {
	            this.options = this.extend(defaults, arguments[0]);
	        }else{
	            throw "Polycast channel options must be defined!";
	        }
	    };

	    this.PolycastChannel.prototype = {
	        init: function(){
	            return this;
	        },
	        extend: function(source, properties) {
	            var property;
	            for (property in properties) {
	                if (properties.hasOwnProperty(property)) {
	                    source[property] = properties[property];
	                }
	            }
	            return source;
	        },
	        on: function(event, callback){
	            if(this.events[event] === undefined){
	                this.events[event] = [];
	            }
	            this.events[event].push(callback);
	            return this;
	        },
	        fire: function(event){
	            for(var e in this.events){
	                if (this.events.hasOwnProperty(e)) {
	                    if(e == event.event){
	                        var func = this.events[e];
	                        if(event.delay != 0){
	                            setTimeout(function(){
	                                func[0](event.payload, event);
	                            }, (event.delay * 1000));
	                        }else{
	                            func[0](event.payload, event);
	                        }
	                    }
	                }
	            }
	        }
	    };

	    this.Polycast.version = '1.0.0';

	    this.PolycastChannel.version = '1.0.0';

	    module.exports = this.Polycast;

	}());

/***/ }
/******/ ])
});
;