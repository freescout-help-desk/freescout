/**
 * FreeScout Contact Form Widget.
 * FreeScout.net - Free open source helpdesk & shared mailbox.
 */
if (typeof(FreeScoutW) != "undefined" && FreeScoutW && typeof(FreeScoutW.s) != "undefined") {

    FreeScoutW.show = function (e) {

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

    	iframe.style.display = 'block';

    	// Hide button
    	var btn = e.target;

    	if (btn.parentElement && btn.parentElement.id == 'fsw-btn') {
    		btn = btn.parentElement;
    	}
        btn.style.display = 'none';
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

        btn.innerHTML = '<img src="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20512%20512%22%3E%3Cpath%20fill%3D%22white%22%20d%3D%22M256%2032C114.6%2032%200%20125.1%200%20240c0%2047.6%2019.9%2091.2%2052.9%20126.3C38%20405.7%207%20439.1%206.5%20439.5c-6.6%207-8.4%2017.2-4.6%2026S14.4%20480%2024%20480c61.5%200%20110-25.7%20139.1-46.3C192%20442.8%20223.2%20448%20256%20448c141.4%200%20256-93.1%20256-208S397.4%2032%20256%2032zm0%20368c-26.7%200-53.1-4.1-78.4-12.1l-22.7-7.2-19.5%2013.8c-14.3%2010.1-33.9%2021.4-57.5%2029%207.3-12.1%2014.4-25.7%2019.9-40.2l10.6-28.1-20.6-21.8C69.7%20314.1%2048%20282.2%2048%20240c0-88.2%2093.3-160%20208-160s208%2071.8%20208%20160-93.3%20160-208%20160z%22%3E%3C%2Fpath%3E%3C%2Fsvg%3E" style="transform: scaleX(-1);" alt=""/>';

        btn.onclick = function(e) {
        	FreeScoutW.show(e);
       	};

        document.body.appendChild(btn);

        // Determine form url
        var script = document.getElementById('freescout-w');

        if (script) {
            FreeScoutW.form_url = script.src.replace(/modules\/enduserportal\/.*/, '');
            FreeScoutW.form_url += 'help/widget/form/'+FreeScoutW.s.id;
            FreeScoutW.form_url += '?'+Object.keys(FreeScoutW.s)
                .map(function(k) {return encodeURIComponent(k) + '=' + encodeURIComponent(FreeScoutW.s[k]);})
                .join('&');

            if (typeof(FreeScoutW.f) != "undefined") {
                FreeScoutW.form_url += '&'+Object.keys(FreeScoutW.f)
                    .map(function(k) {return 'f['+encodeURIComponent(k) + ']=' + encodeURIComponent(FreeScoutW.f[k]);})
                    .join('&');
            }
        }

        // Listen to minimize
        if (window.addEventListener){
            window.addEventListener("message", function(event) {
                if (typeof(event.data) != "undefined" && event.data == 'fsw.minimize')  {
                    FreeScoutW.minimize();
                }
            }, false);
        } else if (element.attachEvent) {
            window.attachEvent("onmessage", function(event) {
                if (typeof(event.data) != "undefined" && event.data == 'fsw.minimize')  {
                    FreeScoutW.minimize();
                }
            });
        }
    };

    FreeScoutW.minimize = function () {
        document.getElementById('fsw-btn').style.display = 'block';
        document.getElementById('fsw-iframe').style.display = 'none';
    }

    FreeScoutW.init();
}