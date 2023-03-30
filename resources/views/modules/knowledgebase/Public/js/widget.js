/**
 * FreeScout Contact Form Widget.
 * FreeScout.net - Free open source helpdesk & shared mailbox.
 */
if (typeof(FreeScoutW) != "undefined" && FreeScoutW && typeof(FreeScoutW.s) != "undefined") {

    FreeScoutW.show = function (btn, minimized) {

        if (typeof(minimized) == "undefined") {
            minimized = false;
        }

    	var iframe = document.getElementById('fsw-iframe');
    	if (!iframe) {
    		iframe = document.createElement('iframe');
    		
    		iframe.id = 'fsw-iframe';

	        iframe.style.cssText = 'width: 342px;'+
	        	'height: 500px;'+
	        	'background-color: #ffffff;'+
	        	'position: fixed;'+
	        	'bottom: 16px;'+
                'right: 16px;'+
	        	'max-height: calc(100vh - 32px);'+
	        	'box-shadow: rgba(0, 0, 0, 0.2) 0px 0px 0.428571rem 0px;'+
	        	'overflow: hidden;'+
                'border: 0;'+
	        	'z-index: 1099;'+
	        	'border-radius: 10px;';

	        // Full
	        if (window.innerWidth < 760) {
	        	iframe.style.bottom = '0px';
	        	iframe.style.right = '0px';
	        	iframe.style.width = '100%';
	        	iframe.style.height = '100%';
	        	iframe.style.maxHeight = 'none';
	        	iframe.style.borderRadius = '0px';
	        } else {
		        if (FreeScoutW.s.position == 'br') {
		        	iframe.style.cssText += 'right: 16px;';
		        } else {
		        	iframe.style.cssText += 'left: 16px;';
		        }
	        }

            iframe.scrolling = 'no';

	        iframe.src = FreeScoutW.form_url;

    		document.body.appendChild(iframe);
    	}

        if (minimized) {
            iframe.style.display = 'none';
        } else {
    	    iframe.style.display = 'block';
            iframe.contentWindow.postMessage('fsw.onshow', '*');
        }

    	// Hide button
    	//btn = e.target;

    	if (btn.parentElement && btn.parentElement.id == 'fsw-btn') {
    		btn = btn.parentElement;
    	}
        if (!minimized) {
            btn.style.display = 'none';
        }
    };

    FreeScoutW.init = function () {

    	if (document.getElementById('fsw-btn')) {
    		return;
    	}

		var btn = document.createElement('div');
        var settings = FreeScoutW.s;

        btn.id = 'fsw-btn';

        btn.style.cssText = 'background-color:'+settings.color+';'+
        	'position: fixed;'+
        	'bottom: 12px;'+
        	'height: 50px;'+
        	'width: 50px;'+
        	'z-index: 1099;'+
        	'padding: 14px 14px 14px;'+
        	'cursor: pointer;'+
            'line-height: 21.5px;'+
        	'color: #ffffff;'+
        	'text-align: center;'+
        	'border-radius: 50%;';

        if (settings.position == 'br') {
        	btn.style.cssText += 'right: 19px;';
        } else {
        	btn.style.cssText += 'left: 19px;';
        }

        btn.innerHTML = '<img src="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2220%22%20height%3D%2220%22%20viewBox%3D%220%200%2020%2020%22%20aria-hidden%3D%22true%22%3E%3Cg%20id%3D%22Layer_4%22%3E%3Cpath%20fill%3D%22white%22%20d%3D%22M11%2C12.3V13c0%2C0-1.8%2C0-2%2C0v-0.6c0-0.6%2C0.1-1.4%2C0.8-2.1c0.7-0.7%2C1.6-1.2%2C1.6-2.1c0-0.9-0.7-1.4-1.4-1.4%20c-1.3%2C0-1.4%2C1.4-1.5%2C1.7H6.6C6.6%2C7.1%2C7.2%2C5%2C10%2C5c2.4%2C0%2C3.4%2C1.6%2C3.4%2C3C13.4%2C10.4%2C11%2C10.8%2C11%2C12.3z%22%3E%3C%2Fpath%3E%3Ccircle%20cx%3D%2210%22%20cy%3D%2215%22%20r%3D%221%22%20fill%3D%22white%22%3E%3C%2Fcircle%3E%3Cpath%20fill%3D%22white%22%20d%3D%22M10%2C2c4.4%2C0%2C8%2C3.6%2C8%2C8s-3.6%2C8-8%2C8s-8-3.6-8-8S5.6%2C2%2C10%2C2%20M10%2C0C4.5%2C0%2C0%2C4.5%2C0%2C10s4.5%2C10%2C10%2C10s10-4.5%2C10-10S15.5%2C0%2C10%2C0%20L10%2C0z%22%3E%3C%2Fpath%3E%3C%2Fg%3E%3C%2Fsvg%3E" alt=""/>';

        btn.onclick = function(e) {
        	FreeScoutW.show(e.target);
            var new_message = document.getElementById('fsw-btn-new');
            if (new_message) {
                new_message.remove();
            }
       	};

        document.body.appendChild(btn);

        // Determine form url
        var script = document.getElementById('freescout-w');
        if (script) {
            FreeScoutW.form_url = script.src.replace(/modules\/knowledgebase\/.*/, '');
            FreeScoutW.form_url += 'knowledgebase/widget/form/'+FreeScoutW.s.id;
            FreeScoutW.form_url += '?'+Object.keys(FreeScoutW.s)
                .map(function(k) {return encodeURIComponent(k) + '=' + encodeURIComponent(FreeScoutW.s[k]);})
                .join('&');
        }

        FreeScoutW.listenForEvent('fsw.minimize', function() {  FreeScoutW.minimize(); });
        FreeScoutW.listenForEvent('fsw.newmessage', function() {  FreeScoutW.newmessage(); });

        // Show minimized
        //FreeScoutW.show(btn, true);
    };

    FreeScoutW.listenForEvent = function (event_name, callback) {
        if (window.addEventListener){
            window.addEventListener("message", function(event) {
                if (typeof(event.data) != "undefined" && event.data == event_name)  {
                    callback();
                }
            }, false);
        } else if (element.attachEvent) {
            window.attachEvent("onmessage", function(event) {
                if (typeof(event.data) != "undefined" && event.data == event_name)  {
                    callback();
                }
            });
        }
    };

    FreeScoutW.minimize = function () {
        document.getElementById('fsw-btn').style.display = 'block';
        document.getElementById('fsw-iframe').style.display = 'none';
    };

    FreeScoutW.newmessage = function () {
        document.getElementById('fsw-btn').innerHTML += '<div id="fsw-btn-new" style="position:absolute; top:1px; right:3px; width:10px; height:10px; border-radius:50%; background-color: #ff734c;"></div>';
    };

    FreeScoutW.init();
}