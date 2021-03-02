/*!
 * Title Alert 0.7
 * 
 * Copyright (c) 2009 ESN | http://esn.me
 * Jonatan Heyman | http://heyman.info
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 */
(function(a){a.titleAlert=function(e,c){if(a.titleAlert._running){a.titleAlert.stop()}a.titleAlert._settings=c=a.extend({},a.titleAlert.defaults,c);if(c.requireBlur&&a.titleAlert.hasFocus){return}c.originalTitleInterval=c.originalTitleInterval||c.interval;a.titleAlert._running=true;a.titleAlert._initialText=document.title;document.title=e;var b=true;var d=function(){if(!a.titleAlert._running){return}b=!b;document.title=(b?e:a.titleAlert._initialText);a.titleAlert._intervalToken=setTimeout(d,(b?c.interval:c.originalTitleInterval))};a.titleAlert._intervalToken=setTimeout(d,c.interval);if(c.stopOnMouseMove){a(document).mousemove(function(f){a(this).unbind(f);a.titleAlert.stop()})}if(c.duration>0){a.titleAlert._timeoutToken=setTimeout(function(){a.titleAlert.stop()},c.duration)}};a.titleAlert.defaults={interval:500,originalTitleInterval:null,duration:0,stopOnFocus:true,requireBlur:false,stopOnMouseMove:false};a.titleAlert.stop=function(){clearTimeout(a.titleAlert._intervalToken);clearTimeout(a.titleAlert._timeoutToken);document.title=a.titleAlert._initialText;a.titleAlert._timeoutToken=null;a.titleAlert._intervalToken=null;a.titleAlert._initialText=null;a.titleAlert._running=false;a.titleAlert._settings=null};a.titleAlert.hasFocus=true;a.titleAlert._running=false;a.titleAlert._intervalToken=null;a.titleAlert._timeoutToken=null;a.titleAlert._initialText=null;a.titleAlert._settings=null;a.titleAlert._focus=function(){a.titleAlert.hasFocus=true;if(a.titleAlert._running&&a.titleAlert._settings.stopOnFocus){var b=a.titleAlert._initialText;a.titleAlert.stop();setTimeout(function(){if(a.titleAlert._running){return}document.title=".";document.title=b},1000)}};a.titleAlert._blur=function(){a.titleAlert.hasFocus=false};a(window).bind("focus",a.titleAlert._focus);a(window).bind("blur",a.titleAlert._blur)})(jQuery);