// https://github.com/Bernardo-Castilho/dragdroptouch
//let DragDropTouch;
if (typeof(DragDropTouch) == "undefined") {
    DragDropTouch = {};
}
(function (DragDropTouch_1) {
    'use strict';
    /**
     * Object used to hold the data that is being dragged during drag and drop operations.
     *
     * It may hold one or more data items of different types. For more information about
     * drag and drop operations and data transfer objects, see
     * <a href="https://developer.mozilla.org/en-US/docs/Web/API/DataTransfer">HTML Drag and Drop API</a>.
     *
     * This object is created automatically by the @see:DragDropTouch singleton and is
     * accessible through the @see:dataTransfer property of all drag events.
     */
    let DataTransfer = (function () {
        function DataTransfer() {
            this._dropEffect = 'move';
            this._effectAllowed = 'all';
            this._data = {};
        }
        Object.defineProperty(DataTransfer.prototype, "dropEffect", {
            /**
             * Gets or sets the type of drag-and-drop operation currently selected.
             * The value must be 'none',  'copy',  'link', or 'move'.
             */
            get: function () {
                return this._dropEffect;
            },
            set: function (value) {
                this._dropEffect = value;
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(DataTransfer.prototype, "effectAllowed", {
            /**
             * Gets or sets the types of operations that are possible.
             * Must be one of 'none', 'copy', 'copyLink', 'copyMove', 'link',
             * 'linkMove', 'move', 'all' or 'uninitialized'.
             */
            get: function () {
                return this._effectAllowed;
            },
            set: function (value) {
                this._effectAllowed = value;
            },
            enumerable: true,
            configurable: true
        });
        Object.defineProperty(DataTransfer.prototype, "types", {
            /**
             * Gets an array of strings giving the formats that were set in the @see:dragstart event.
             */
            get: function () {
                return Object.keys(this._data);
            },
            enumerable: true,
            configurable: true
        });
        /**
         * Removes the data associated with a given type.
         *
         * The type argument is optional. If the type is empty or not specified, the data
         * associated with all types is removed. If data for the specified type does not exist,
         * or the data transfer contains no data, this method will have no effect.
         *
         * @param type Type of data to remove.
         */
        DataTransfer.prototype.clearData = function (type) {
            if (type !== null) {
                delete this._data[type.toLowerCase()];
            }
            else {
                this._data = {};
            }
        };
        /**
         * Retrieves the data for a given type, or an empty string if data for that type does
         * not exist or the data transfer contains no data.
         *
         * @param type Type of data to retrieve.
         */
        DataTransfer.prototype.getData = function (type) {
            let lcType = type.toLowerCase(),
                data = this._data[lcType];
            if (lcType === "text" && data == null) {
                data = this._data["text/plain"]; // getData("text") also gets ("text/plain")
            }
            return data || "";
        };
        /**
         * Set the data for a given type.
         *
         * For a list of recommended drag types, please see
         * https://developer.mozilla.org/en-US/docs/Web/Guide/HTML/Recommended_Drag_Types.
         *
         * @param type Type of data to add.
         * @param value Data to add.
         */
        DataTransfer.prototype.setData = function (type, value) {
            this._data[type.toLowerCase()] = value;
        };
        /**
         * Set the image to be used for dragging if a custom one is desired.
         *
         * @param img An image element to use as the drag feedback image.
         * @param offsetX The horizontal offset within the image.
         * @param offsetY The vertical offset within the image.
         */
        DataTransfer.prototype.setDragImage = function (img, offsetX, offsetY) {
            let ddt = DragDropTouch._instance;
            ddt._imgCustom = img;
            ddt._imgOffset = { x: offsetX, y: offsetY };
        };
        return DataTransfer;
    }());
    DragDropTouch_1.DataTransfer = DataTransfer;
    /**
     * Defines a class that adds support for touch-based HTML5 drag/drop operations.
     *
     * The @see:DragDropTouch class listens to touch events and raises the
     * appropriate HTML5 drag/drop events as if the events had been caused
     * by mouse actions.
     *
     * The purpose of this class is to enable using existing, standard HTML5
     * drag/drop code on mobile devices running IOS or Android.
     *
     * To use, include the DragDropTouch.js file on the page. The class will
     * automatically start monitoring touch events and will raise the HTML5
     * drag drop events (dragstart, dragenter, dragleave, drop, dragend) which
     * should be handled by the application.
     *
     * For details and examples on HTML drag and drop, see
     * https://developer.mozilla.org/en-US/docs/Web/Guide/HTML/Drag_operations.
     */
    let DragDropTouch = (function () {
        /**
         * Initializes the single instance of the @see:DragDropTouch class.
         */
        function DragDropTouch() {
            this._lastClick = 0;
            // enforce singleton pattern
            if (DragDropTouch._instance) {
                throw 'DragDropTouch instance already created.';
            }
            // detect passive event support
            // https://github.com/Modernizr/Modernizr/issues/1894
            let supportsPassive = false;
            document.addEventListener('test', function () { }, {
                get passive() {
                    supportsPassive = true;
                    return true;
                }
            });
            // listen to touch events
            if (navigator.maxTouchPoints) {
                let d = document, 
                    ts = this._touchstart.bind(this), 
                    tm = this._touchmove.bind(this), 
                    te = this._touchend.bind(this), 
                    opt = supportsPassive ? { passive: false, capture: false } : false;
                d.addEventListener('touchstart', ts, opt);
                d.addEventListener('touchmove', tm, opt);
                d.addEventListener('touchend', te);
                d.addEventListener('touchcancel', te);
            }
        }
        /**
         * Gets a reference to the @see:DragDropTouch singleton.
         */
        DragDropTouch.getInstance = function () {
            return DragDropTouch._instance;
        };
        // ** event handlers
        DragDropTouch.prototype._touchstart = function (e) {
            let _this = this;
            if (this._shouldHandle(e)) {
                // clear all variables
                this._reset();
                // get nearest draggable element
                let src = this._closestDraggable(e.target);
                if (src) {
                    // give caller a chance to handle the hover/move events
                    if (!this._dispatchEvent(e, 'mousemove', e.target) &&
                        !this._dispatchEvent(e, 'mousedown', e.target)) {
                        // get ready to start dragging
                        this._dragSource = src;
                        this._ptDown = this._getPoint(e);
                        this._lastTouch = e;

                        // do not prevent default (so input elements keep working)
                        //e.preventDefault();

                        // show context menu if the user hasn't started dragging after a while
                        setTimeout(function () {
                            if (_this._dragSource === src && _this._img === null) {
                                if (_this._dispatchEvent(e, 'contextmenu', src)) {
                                    _this._reset();
                                }
                            }
                        }, DragDropTouch._CTXMENU);
                        if (DragDropTouch._ISPRESSHOLDMODE) {
                            this._pressHoldInterval = setTimeout(function () {
                                _this._isDragEnabled = true;
                                _this._touchmove(e);
                            }, DragDropTouch._PRESSHOLDAWAIT);
                        }
                    }
                }
            }
        };
        DragDropTouch.prototype._touchmove = function (e) {
            if (this._shouldCancelPressHoldMove(e)) {
              this._reset();
              return;
            }
            if (this._shouldHandleMove(e) || this._shouldHandlePressHoldMove(e)) {
                // see if target wants to handle move
                let target = this._getTarget(e);
                if (this._dispatchEvent(e, 'mousemove', target)) {
                    this._lastTouch = e;
                    e.preventDefault();
                    return;
                }
                // start dragging
                if (this._dragSource && !this._img && this._shouldStartDragging(e)) {
                    if (this._dispatchEvent(this._lastTouch, 'dragstart', this._dragSource)) {
                        // target canceled the drag event
                        this._dragSource = null;
                        return;
                    }
                    this._createImage(e);
                    this._dispatchEvent(e, 'dragenter', target);
                }
                // continue dragging
                if (this._img) {
                    this._lastTouch = e;
                    e.preventDefault(); // prevent scrolling
                    this._dispatchEvent(e, 'drag', this._dragSource);
                    if (target !== this._lastTarget) {
                        this._dispatchEvent(this._lastTouch, 'dragleave', this._lastTarget);
                        this._dispatchEvent(e, 'dragenter', target);
                        this._lastTarget = target;
                    }
                    this._moveImage(e);
                    this._isDropZone = this._dispatchEvent(e, 'dragover', target);
                }
            }
        };
        DragDropTouch.prototype._touchend = function (e) {
            if (this._shouldHandle(e)) {
                // see if target wants to handle up
                if (this._dispatchEvent(this._lastTouch, 'mouseup', e.target)) {
                    e.preventDefault();
                    return;
                }
                // user clicked the element but didn't drag, so clear the source and simulate a click
                if (!this._img) {
                    this._dragSource = null;
                    this._dispatchEvent(this._lastTouch, 'click', e.target);
                    this._lastClick = Date.now();
                }
                // finish dragging
                this._destroyImage();
                if (this._dragSource) {
                    if (e.type.indexOf('cancel') < 0 && this._isDropZone) {
                        this._dispatchEvent(this._lastTouch, 'drop', this._lastTarget);
                    }
                    this._dispatchEvent(this._lastTouch, 'dragend', this._dragSource);
                    this._reset();
                }
            }
        };
        // ** utilities
        // ignore events that have been handled or that involve more than one touch
        DragDropTouch.prototype._shouldHandle = function (e) {
            var j_target = $(e.target);

            // console.log(e.target);
            // console.log(j_target.parent().parent().parent());
            // console.log(j_target.parent().parent().parent().hasClass('panel-sortable'));

            // This function runs only on mobile devices
            // Apply DragDropTouch only when dragging an element with draggable="true"
            // https://github.com/freescout-helpdesk/freescout/issues/3800
            if (!j_target.attr('draggable') 
                && (typeof(e.target.parentElement) != "undefined" && !$(e.target.parentElement).attr('draggable'))
                && (typeof(e.target.parentElement.parentElement) != "undefined" && !$(e.target.parentElement.parentElement).attr('draggable'))
                && !j_target.parent().parent().parent().hasClass('panel-sortable')
            ) {
                return false;
            }
            
            // Fix Bootstrap submenu.
            // https://github.com/freescout-helpdesk/freescout/issues/3708
            /*if (typeof(e.target) != "undefined") {
                if (e.target.classList.contains('dropdown-toggle')) {
                    return false;
                }
                if (e.target.tagName == 'A'
                    && typeof(e.target.parentElement) != "undefined"
                    && typeof(e.target.parentElement.parentElement) != "undefined"
                    && e.target.parentElement.parentElement.classList.contains('dropdown-menu')
                ) {
                    return false;
                }
            }*/
            // Conversations list fix
            // https://github.com/freescout-helpdesk/freescout/issues/3724
            /*if ($('table.table-conversations:first').length) {
                return false;
            }*/
            return e &&
                !e.defaultPrevented &&
                e.touches && e.touches.length < 2;
        };

        // use regular condition outside of press & hold mode
        DragDropTouch.prototype._shouldHandleMove = function (e) {
          return !DragDropTouch._ISPRESSHOLDMODE && this._shouldHandle(e);
        };

        // allow to handle moves that involve many touches for press & hold
        DragDropTouch.prototype._shouldHandlePressHoldMove = function (e) {
          return DragDropTouch._ISPRESSHOLDMODE &&
              this._isDragEnabled && e && e.touches && e.touches.length;
        };

        // reset data if user drags without pressing & holding
        DragDropTouch.prototype._shouldCancelPressHoldMove = function (e) {
          return DragDropTouch._ISPRESSHOLDMODE && !this._isDragEnabled &&
              this._getDelta(e) > DragDropTouch._PRESSHOLDMARGIN;
        };

        // start dragging when specified delta is detected
        DragDropTouch.prototype._shouldStartDragging = function (e) {
            let delta = this._getDelta(e);
            return delta > DragDropTouch._THRESHOLD ||
                (DragDropTouch._ISPRESSHOLDMODE && delta >= DragDropTouch._PRESSHOLDTHRESHOLD);
        }

        // clear all members
        DragDropTouch.prototype._reset = function () {
            this._destroyImage();
            this._dragSource = null;
            this._lastTouch = null;
            this._lastTarget = null;
            this._ptDown = null;
            this._isDragEnabled = false;
            this._isDropZone = false;
            this._dataTransfer = new DataTransfer();
            clearInterval(this._pressHoldInterval);
        };
        // get point for a touch event
        DragDropTouch.prototype._getPoint = function (e, page) {
            if (e && e.touches) {
                e = e.touches[0];
            }
            return { x: page ? e.pageX : e.clientX, y: page ? e.pageY : e.clientY };
        };
        // get distance between the current touch event and the first one
        DragDropTouch.prototype._getDelta = function (e) {
            if (DragDropTouch._ISPRESSHOLDMODE && !this._ptDown) { return 0; }
            let p = this._getPoint(e);
            return Math.abs(p.x - this._ptDown.x) + Math.abs(p.y - this._ptDown.y);
        };
        // get the element at a given touch event
        DragDropTouch.prototype._getTarget = function (e) {
            let pt = this._getPoint(e),
                el = document.elementFromPoint(pt.x, pt.y);
            while (el && getComputedStyle(el).pointerEvents == 'none') {
                el = el.parentElement;
            }
            return el;
        };
        // create drag image from source element
        DragDropTouch.prototype._createImage = function (e) {
            // just in case...
            if (this._img) {
                this._destroyImage();
            }
            // create drag image from custom element or drag source
            let src = this._imgCustom || this._dragSource;
            this._img = src.cloneNode(true);
            this._copyStyle(src, this._img);
            this._img.style.top = this._img.style.left = '-9999px';
            // if creating from drag source, apply offset and opacity
            if (!this._imgCustom) {
                let rc = src.getBoundingClientRect(),
                    pt = this._getPoint(e);
                this._imgOffset = { x: pt.x - rc.left, y: pt.y - rc.top };
                this._img.style.opacity = DragDropTouch._OPACITY.toString();
            }
            // add image to document
            this._moveImage(e);
            document.body.appendChild(this._img);
        };
        // dispose of drag image element
        DragDropTouch.prototype._destroyImage = function () {
            if (this._img && this._img.parentElement) {
                this._img.parentElement.removeChild(this._img);
            }
            this._img = null;
            this._imgCustom = null;
        };
        // move the drag image element
        DragDropTouch.prototype._moveImage = function (e) {
            let _this = this;
            requestAnimationFrame(function () {
                if (_this._img) {
                    let pt = _this._getPoint(e, true),
                        s = _this._img.style;
                    s.position = 'absolute';
                    s.pointerEvents = 'none';
                    s.zIndex = '999999';
                    s.left = Math.round(pt.x - _this._imgOffset.x) + 'px';
                    s.top = Math.round(pt.y - _this._imgOffset.y) + 'px';
                }
            });
        };
        // copy properties from an object to another
        DragDropTouch.prototype._copyProps = function (dst, src, props) {
            for (let i = 0; i < props.length; i++) {
                let p = props[i];
                dst[p] = src[p];
            }
        };
        DragDropTouch.prototype._copyStyle = function (src, dst) {
            // remove potentially troublesome attributes
            DragDropTouch._rmvAtts.forEach(function (att) {
                dst.removeAttribute(att);
            });
            // copy canvas content
            if (src instanceof HTMLCanvasElement) {
                let cSrc = src,
                    cDst = dst;
                cDst.width = cSrc.width;
                cDst.height = cSrc.height;
                cDst.getContext('2d').drawImage(cSrc, 0, 0);
            }
            // copy style (without transitions)
            let cs = getComputedStyle(src);
            for (let i = 0; i < cs.length; i++) {
                let key = cs[i];
                if (key.indexOf('transition') < 0) {
                    dst.style[key] = cs[key];
                }
            }
            dst.style.pointerEvents = 'none';
            // and repeat for all children
            for (let i = 0; i < src.children.length; i++) {
                this._copyStyle(src.children[i], dst.children[i]);
            }
        };
        // compute missing offset or layer property for an event
        DragDropTouch.prototype._setOffsetAndLayerProps = function (e, target) {
            let rect = undefined;
            if (e.offsetX === undefined) {
                rect = target.getBoundingClientRect();
                e.offsetX = e.clientX - rect.x;
                e.offsetY = e.clientY - rect.y;
            }
            if (e.layerX === undefined) {
                rect = rect || target.getBoundingClientRect();
                e.layerX = e.pageX - rect.left;
                e.layerY = e.pageY - rect.top;
            }
        }
        DragDropTouch.prototype._dispatchEvent = function (e, type, target) {
            if (e && target) {
                //let evt = document.createEvent('Event'), t = e.touches ? e.touches[0] : e; // deprecated
                //evt.initEvent(type, true, true); // deprecated
                let evt = new Event(type, { bubbles: true, cancelable: true }),
                    touch = e.touches ? e.touches[0] : e;
                evt.button = 0;
                evt.which = evt.buttons = 1;
                this._copyProps(evt, e, DragDropTouch._kbdProps);
                this._copyProps(evt, touch, DragDropTouch._ptProps);
                this._setOffsetAndLayerProps(evt, target);
                evt.dataTransfer = this._dataTransfer;
                target.dispatchEvent(evt);
                return evt.defaultPrevented;
            }
            return false;
        };
        // gets an element's closest draggable ancestor
        // <img> and <a> elements are draggable by default
        DragDropTouch.prototype._closestDraggable = function (e) {
            for (; e; e = e.parentElement) {
                if (/*e.hasAttribute('draggable') &&*/ e.draggable) {
                    return e;
                }
            }
            return null;
        };
        return DragDropTouch;
    }());
    /*private*/ DragDropTouch._instance = new DragDropTouch(); // singleton
    // constants
    DragDropTouch._THRESHOLD = 5; // pixels to move before drag starts
    DragDropTouch._OPACITY = 0.5; // drag image opacity
    DragDropTouch._DBLCLICK = 500; // max ms between clicks in a double click
    DragDropTouch._CTXMENU = 900; // ms to hold before raising 'contextmenu' event
    DragDropTouch._ISPRESSHOLDMODE = false; // decides of press & hold mode presence
    DragDropTouch._PRESSHOLDAWAIT = 400; // ms to wait before press & hold is detected
    DragDropTouch._PRESSHOLDMARGIN = 25; // pixels that finger might shiver while pressing
    DragDropTouch._PRESSHOLDTHRESHOLD = 0; // pixels to move before drag starts
    // copy styles/attributes from drag source to drag image element
    DragDropTouch._rmvAtts = 'id,class,style,draggable'.split(',');
    // synthesize and dispatch an event
    // returns true if the event has been handled (e.preventDefault == true)
    DragDropTouch._kbdProps = 'altKey,ctrlKey,metaKey,shiftKey'.split(',');
    DragDropTouch._ptProps = 'pageX,pageY,clientX,clientY,screenX,screenY,offsetX,offsetY'.split(',');
    DragDropTouch_1.DragDropTouch = DragDropTouch;
})(DragDropTouch || (DragDropTouch = {}));


/*
 * HTML5Sortable package
 * https://github.com/lukasoppermann/html5sortable
 *
 * Maintained by Lukas Oppermann <lukas@vea.re>
 *
 * Released under the MIT license.
 */
var sortable=function(){"use strict";function d(e,t,n){if(void 0===n)return e&&e.h5s&&e.h5s.data&&e.h5s.data[t];e.h5s=e.h5s||{},e.h5s.data=e.h5s.data||{},e.h5s.data[t]=n}var v=function(e,t){if(!(e instanceof NodeList||e instanceof HTMLCollection||e instanceof Array))throw new Error("You must provide a nodeList/HTMLCollection/Array of elements to be filtered.");return"string"!=typeof t?Array.from(e):Array.from(e).filter(function(e){return 1===e.nodeType&&e.matches(t)})},y=new Map,t=function(){function e(){this._config=new Map,this._placeholder=void 0,this._data=new Map}return Object.defineProperty(e.prototype,"config",{get:function(){var n={};return this._config.forEach(function(e,t){n[t]=e}),n},set:function(e){if("object"!=typeof e)throw new Error("You must provide a valid configuration object to the config setter.");var t=Object.assign({},e);this._config=new Map(Object.entries(t))},enumerable:!1,configurable:!0}),e.prototype.setConfig=function(e,t){if(!this._config.has(e))throw new Error("Trying to set invalid configuration item: "+e);this._config.set(e,t)},e.prototype.getConfig=function(e){if(!this._config.has(e))throw new Error("Invalid configuration item requested: "+e);return this._config.get(e)},Object.defineProperty(e.prototype,"placeholder",{get:function(){return this._placeholder},set:function(e){if(!(e instanceof HTMLElement)&&null!==e)throw new Error("A placeholder must be an html element or null.");this._placeholder=e},enumerable:!1,configurable:!0}),e.prototype.setData=function(e,t){if("string"!=typeof e)throw new Error("The key must be a string.");this._data.set(e,t)},e.prototype.getData=function(e){if("string"!=typeof e)throw new Error("The key must be a string.");return this._data.get(e)},e.prototype.deleteData=function(e){if("string"!=typeof e)throw new Error("The key must be a string.");return this._data.delete(e)},e}(),E=function(e){if(!(e instanceof HTMLElement))throw new Error("Please provide a sortable to the store function.");return y.has(e)||y.set(e,new t),y.get(e)};function i(e,t,n){if(e instanceof Array)for(var r=0;r<e.length;++r)i(e[r],t,n);else e.addEventListener(t,n),E(e).setData("event"+t,n)}function a(e,t){if(e instanceof Array)for(var n=0;n<e.length;++n)a(e[n],t);else e.removeEventListener(t,E(e).getData("event"+t)),E(e).deleteData("event"+t)}function l(e,t,n){if(e instanceof Array)for(var r=0;r<e.length;++r)l(e[r],t,n);else e.setAttribute(t,n)}function r(e,t){if(e instanceof Array)for(var n=0;n<e.length;++n)r(e[n],t);else e.removeAttribute(t)}var w=function(e){if(!e.parentElement||0===e.getClientRects().length)throw new Error("target element must be part of the dom");var t=e.getClientRects()[0];return{left:t.left+window.pageXOffset,right:t.right+window.pageXOffset,top:t.top+window.pageYOffset,bottom:t.bottom+window.pageYOffset}},c=function(n,r){var o;return void 0===r&&(r=0),function(){for(var e=[],t=0;t<arguments.length;t++)e[t]=arguments[t];clearTimeout(o),o=setTimeout(function(){n.apply(void 0,e)},r)}},b=function(e,t){if(!(e instanceof HTMLElement&&(t instanceof NodeList||t instanceof HTMLCollection||t instanceof Array)))throw new Error("You must provide an element and a list of elements.");return Array.from(t).indexOf(e)},f=function(e){if(!(e instanceof HTMLElement))throw new Error("Element is not a node element.");return null!==e.parentNode},n=function(e,t,n){if(!(e instanceof HTMLElement&&e.parentElement instanceof HTMLElement))throw new Error("target and element must be a node");e.parentElement.insertBefore(t,"before"===n?e:e.nextElementSibling)},T=function(e,t){return n(e,t,"before")},C=function(e,t){return n(e,t,"after")},o=function(t,n,e){if(void 0===n&&(n=function(e,t){return e}),void 0===e&&(e=function(e){return e}),!(t instanceof HTMLElement)||!0==!t.isSortable)throw new Error("You need to provide a sortableContainer to be serialized.");if("function"!=typeof n||"function"!=typeof e)throw new Error("You need to provide a valid serializer for items and the container.");var r=d(t,"opts").items,o=v(t.children,r),a=o.map(function(e){return{parent:t,node:e,html:e.outerHTML,index:b(e,o)}});return{container:e({node:t,itemCount:a.length}),items:a.map(function(e){return n(e,t)})}},u=function(e,t,n){var r;if(void 0===n&&(n="sortable-placeholder"),!(e instanceof HTMLElement))throw new Error("You must provide a valid element as a sortable.");if(!(t instanceof HTMLElement)&&void 0!==t)throw new Error("You must provide a valid element as a placeholder or set ot to undefined.");return void 0===t&&(["UL","OL"].includes(e.tagName)?t=document.createElement("li"):["TABLE","TBODY"].includes(e.tagName)?(t=document.createElement("tr")).innerHTML='<td colspan="100"></td>':t=document.createElement("div")),"string"==typeof n&&(r=t.classList).add.apply(r,n.split(" ")),t},L=function(e){if(!(e instanceof HTMLElement))throw new Error("You must provide a valid dom element");var n=window.getComputedStyle(e);return"border-box"===n.getPropertyValue("box-sizing")?parseInt(n.getPropertyValue("height"),10):["height","padding-top","padding-bottom"].map(function(e){var t=parseInt(n.getPropertyValue(e),10);return isNaN(t)?0:t}).reduce(function(e,t){return e+t})},x=function(e){if(!(e instanceof HTMLElement))throw new Error("You must provide a valid dom element");var n=window.getComputedStyle(e);return["width","padding-left","padding-right"].map(function(e){var t=parseInt(n.getPropertyValue(e),10);return isNaN(t)?0:t}).reduce(function(e,t){return e+t})},s=function(e,t){if(!(e instanceof Array))throw new Error("You must provide a Array of HTMLElements to be filtered.");return"string"!=typeof t?e:e.filter(function(e){return e.querySelector(t)instanceof HTMLElement||e.shadowRoot&&e.shadowRoot.querySelector(t)instanceof HTMLElement}).map(function(e){return e.querySelector(t)||e.shadowRoot&&e.shadowRoot.querySelector(t)})},p=function(e){return e.composedPath&&e.composedPath()[0]||e.target},m=function(e,t,n){return{element:e,posX:n.pageX-t.left,posY:n.pageY-t.top}},g=function(e,t,n){if(!(e instanceof Event))throw new Error("setDragImage requires a DragEvent as the first argument.");if(!(t instanceof HTMLElement))throw new Error("setDragImage requires the dragged element as the second argument.");if(n||(n=m),e.dataTransfer&&e.dataTransfer.setDragImage){var r=n(t,w(t),e);if(!(r.element instanceof HTMLElement)||"number"!=typeof r.posX||"number"!=typeof r.posY)throw new Error("The customDragImage function you provided must return and object with the properties element[string], posX[integer], posY[integer].");e.dataTransfer.effectAllowed="copyMove",e.dataTransfer.setData("text/plain",p(e).id),e.dataTransfer.setDragImage(r.element,r.posX,r.posY)}},D=function(e,t){if(!0===e.isSortable){var n=E(e).getConfig("acceptFrom");if(null!==n&&!1!==n&&"string"!=typeof n)throw new Error('HTML5Sortable: Wrong argument, "acceptFrom" must be "null", "false", or a valid selector string.');if(null!==n)return!1!==n&&0<n.split(",").filter(function(e){return 0<e.length&&t.matches(e)}).length;if(e===t)return!0;if(void 0!==E(e).getConfig("connectWith")&&null!==E(e).getConfig("connectWith"))return E(e).getConfig("connectWith")===E(t).getConfig("connectWith")}return!1},M={items:null,connectWith:null,disableIEFix:null,acceptFrom:null,copy:!1,placeholder:null,placeholderClass:"sortable-placeholder",draggingClass:"sortable-dragging",hoverClass:!1,dropTargetContainerClass:!1,debounce:0,throttleTime:100,maxItems:0,itemSerializer:void 0,containerSerializer:void 0,customDragImage:null,orientation:"vertical"};var H,I,A,S,Y,_,O,P,z,h=function(e,t){if("string"==typeof E(e).getConfig("hoverClass")){var o=E(e).getConfig("hoverClass").split(" ");!0===t?(i(e,"mousemove",function(r,o){var a=this;if(void 0===o&&(o=250),"function"!=typeof r)throw new Error("You must provide a function as the first argument for throttle.");if("number"!=typeof o)throw new Error("You must provide a number as the second argument for throttle.");var i=null;return function(){for(var e=[],t=0;t<arguments.length;t++)e[t]=arguments[t];var n=Date.now();(null===i||o<=n-i)&&(i=n,r.apply(a,e))}}(function(r){0===r.buttons&&v(e.children,E(e).getConfig("items")).forEach(function(e){var t,n;e===r.target||e.contains(r.target)?(t=e.classList).add.apply(t,o):(n=e.classList).remove.apply(n,o)})},E(e).getConfig("throttleTime"))),i(e,"mouseleave",function(){v(e.children,E(e).getConfig("items")).forEach(function(e){var t;(t=e.classList).remove.apply(t,o)})})):(a(e,"mousemove"),a(e,"mouseleave"))}},W=function(e){a(e,"dragstart"),a(e,"dragend"),a(e,"dragover"),a(e,"dragenter"),a(e,"drop"),a(e,"mouseenter"),a(e,"mouseleave")},N=function(e,t){e&&a(e,"dragleave"),t&&t!==e&&a(t,"dragleave")},j=function(e,t){var n=e;return!0===E(t).getConfig("copy")&&(l(n=e.cloneNode(!0),"aria-copied","true"),e.parentElement.appendChild(n),n.style.display="none",n.oldDisplay=e.style.display),n},F=function(e){var t;(t=e).h5s&&delete t.h5s.data,r(e,"aria-dropeffect")},q=function(e){r(e,"aria-grabbed"),r(e,"aria-copied"),r(e,"draggable"),r(e,"role")};function R(e,t){if(t.composedPath)return t.composedPath().find(function(e){return e.isSortable});for(;!0!==e.isSortable;)e=e.parentElement;return e}function X(e,t){var n=d(e,"opts"),r=v(e.children,n.items).filter(function(e){return e.contains(t)||e.shadowRoot&&e.shadowRoot.contains(t)});return 0<r.length?r[0]:t}var B=function(e){var t=d(e,"opts"),n=v(e.children,t.items),r=s(n,t.handle);(l(e,"aria-dropeffect","move"),d(e,"_disabled","false"),l(r,"draggable","true"),h(e,!0),!1===t.disableIEFix)&&("function"==typeof(document||window.document).createElement("span").dragDrop&&i(r,"mousedown",function(){if(-1!==n.indexOf(this))this.dragDrop();else{for(var e=this.parentElement;-1===n.indexOf(e);)e=e.parentElement;e.dragDrop()}}))},k=function(e){var t=d(e,"opts"),n=v(e.children,t.items),r=s(n,t.handle);d(e,"_disabled","false"),W(n),N(S,P),a(r,"mousedown"),a(e,"dragover"),a(e,"dragenter"),a(e,"drop")};function U(e,h){var a=String(h);return h=h||{},"string"==typeof e&&(e=document.querySelectorAll(e)),e instanceof HTMLElement&&(e=[e]),e=Array.prototype.slice.call(e),/serialize/.test(a)?e.map(function(e){var t=d(e,"opts");return o(e,t.itemSerializer,t.containerSerializer)}):(e.forEach(function(s){if(/enable|disable|destroy/.test(a))return U[a](s);["connectWith","disableIEFix"].forEach(function(e){Object.prototype.hasOwnProperty.call(h,e)&&null!==h[e]&&console.warn('HTML5Sortable: You are using the deprecated configuration "'+e+'". This will be removed in an upcoming version, make sure to migrate to the new options when updating.')}),h=Object.assign({},M,E(s).config,h),E(s).config=h,d(s,"opts",h),s.isSortable=!0,k(s);var e,t=v(s.children,h.items);if(null!==h.placeholder&&void 0!==h.placeholder){var n=document.createElement(s.tagName);h.placeholder instanceof HTMLElement?n.appendChild(h.placeholder):n.innerHTML=h.placeholder,e=n.children[0]}E(s).placeholder=u(s,e,h.placeholderClass),d(s,"items",h.items),h.acceptFrom?d(s,"acceptFrom",h.acceptFrom):h.connectWith&&d(s,"connectWith",h.connectWith),B(s),l(t,"role","option"),l(t,"aria-grabbed","false"),i(s,"dragstart",function(e){var t=p(e);if(!0!==t.isSortable&&(e.stopImmediatePropagation(),(!h.handle||t.matches(h.handle))&&"false"!==t.getAttribute("draggable"))){var n=R(t,e),r=X(n,t);O=v(n.children,h.items),Y=O.indexOf(r),_=b(r,n.children),S=n,g(e,r,h.customDragImage),I=L(r),A=x(r),r.classList.add(h.draggingClass),l(H=j(r,n),"aria-grabbed","true"),n.dispatchEvent(new CustomEvent("sortstart",{detail:{origin:{elementIndex:_,index:Y,container:S},item:H,originalTarget:t}}))}}),i(s,"dragenter",function(e){var n=p(e),r=R(n,e);r&&r!==P&&(z=v(r.children,d(r,"items")).filter(function(e){return e!==E(s).placeholder}),h.dropTargetContainerClass&&r.classList.add(h.dropTargetContainerClass),r.dispatchEvent(new CustomEvent("sortenter",{detail:{origin:{elementIndex:_,index:Y,container:S},destination:{container:r,itemsBeforeUpdate:z},item:H,originalTarget:n}})),i(r,"dragleave",function(e){var t=e.relatedTarget||e.fromElement;e.currentTarget.contains(t)||(h.dropTargetContainerClass&&r.classList.remove(h.dropTargetContainerClass),r.dispatchEvent(new CustomEvent("sortleave",{detail:{origin:{elementIndex:_,index:Y,container:r},item:H,originalTarget:n}})))})),P=r}),i(s,"dragend",function(e){if(H){H.classList.remove(h.draggingClass),l(H,"aria-grabbed","false"),"true"===H.getAttribute("aria-copied")&&"true"!==d(H,"dropped")&&H.remove(),void 0!==H.oldDisplay&&(H.style.display=H.oldDisplay,delete H.oldDisplay);var t=Array.from(y.values()).map(function(e){return e.placeholder}).filter(function(e){return e instanceof HTMLElement}).filter(f)[0];t&&t.remove(),s.dispatchEvent(new CustomEvent("sortstop",{detail:{origin:{elementIndex:_,index:Y,container:S},item:H}})),A=I=H=P=null}}),i(s,"drop",function(e){if(D(s,H.parentElement)){e.preventDefault(),e.stopPropagation(),d(H,"dropped","true");var t=Array.from(y.values()).map(function(e){return e.placeholder}).filter(function(e){return e instanceof HTMLElement}).filter(f)[0];if(t){t.replaceWith(H),void 0!==H.oldDisplay&&(H.style.display=H.oldDisplay,delete H.oldDisplay),s.dispatchEvent(new CustomEvent("sortstop",{detail:{origin:{elementIndex:_,index:Y,container:S},item:H}}));var n=E(s).placeholder,r=v(S.children,h.items).filter(function(e){return e!==n}),o=!0===this.isSortable?this:this.parentElement,a=v(o.children,d(o,"items")).filter(function(e){return e!==n}),i=b(H,Array.from(H.parentElement.children).filter(function(e){return e!==n})),l=b(H,a);h.dropTargetContainerClass&&o.classList.remove(h.dropTargetContainerClass),_===i&&S===o||s.dispatchEvent(new CustomEvent("sortupdate",{detail:{origin:{elementIndex:_,index:Y,container:S,itemsBeforeUpdate:O,items:r},destination:{index:l,elementIndex:i,container:o,itemsBeforeUpdate:z,items:a},item:H}}))}else d(H,"dropped","false")}});var o=c(function(t,e,n,r){if(H)if(h.forcePlaceholderSize&&(E(t).placeholder.style.height=I+"px",E(t).placeholder.style.width=A+"px"),-1<Array.from(t.children).indexOf(e)){var o=L(e),a=x(e),i=b(E(t).placeholder,e.parentElement.children),l=b(e,e.parentElement.children);if(I<o||A<a){var s=o-I,d=a-A,c=w(e).top,f=w(e).left;if(i<l&&("vertical"===h.orientation&&r<c||"horizontal"===h.orientation&&n<f))return;if(l<i&&("vertical"===h.orientation&&c+o-s<r||"horizontal"===h.orientation&&f+a-d<n))return}void 0===H.oldDisplay&&(H.oldDisplay=H.style.display),"none"!==H.style.display&&(H.style.display="none");var u=!1;try{var p=w(e).top+e.offsetHeight/2,m=w(e).left+e.offsetWidth/2;u="vertical"===h.orientation&&p<=r||"horizontal"===h.orientation&&m<=n}catch(e){u=i<l}u?C(e,E(t).placeholder):T(e,E(t).placeholder),Array.from(y.values()).filter(function(e){return void 0!==e.placeholder}).forEach(function(e){e.placeholder!==E(t).placeholder&&e.placeholder.remove()})}else{var g=Array.from(y.values()).filter(function(e){return void 0!==e.placeholder}).map(function(e){return e.placeholder});-1!==g.indexOf(e)||t!==e||v(e.children,h.items).length||(g.forEach(function(e){return e.remove()}),e.appendChild(E(t).placeholder))}},h.debounce),r=function(e){var t=e.target,n=!0===t.isSortable?t:R(t,e);if(t=X(n,t),H&&D(n,H.parentElement)&&"true"!==d(n,"_disabled")){var r=d(n,"opts");parseInt(r.maxItems)&&v(n.children,d(n,"items")).length>parseInt(r.maxItems)&&H.parentElement!==n||(e.preventDefault(),e.stopPropagation(),e.dataTransfer.dropEffect=!0===E(n).getConfig("copy")?"copy":"move",o(n,t,e.pageX,e.pageY))}};i(t.concat(s),"dragover",r),i(t.concat(s),"dragenter",r)}),e)}return U.destroy=function(e){var t,n,r,o;n=d(t=e,"opts")||{},r=v(t.children,n.items),o=s(r,n.handle),h(t,!1),a(t,"dragover"),a(t,"dragenter"),a(t,"dragstart"),a(t,"dragend"),a(t,"drop"),F(t),a(o,"mousedown"),W(r),q(r),N(S,P),t.isSortable=!1},U.enable=function(e){B(e)},U.disable=function(e){var t,n,r,o;n=d(t=e,"opts"),r=v(t.children,n.items),o=s(r,n.handle),l(t,"aria-dropeffect","none"),d(t,"_disabled","true"),l(o,"draggable","false"),a(o,"mousedown"),h(t,!1)},U.__testing={data:d,removeItemEvents:W,removeItemData:q,removeSortableData:F,removeContainerEvents:N},U}();
//# sourceMappingURL=html5sortable.min.js.map