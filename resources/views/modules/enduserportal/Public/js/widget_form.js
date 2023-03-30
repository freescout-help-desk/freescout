/**
 * Widget form JavaScript.
 */

$(document).ready(function(){

	$('#eupw-minimize').click(function(e) {
        if (typeof(window.parent) != "undefined") {
            window.parent.postMessage('fsw.minimize', '*');
        }

		e.preventDefault();
	});
});

/**
 * Copied from global main.js
 */

// Generate random unique ID
/*function generateDummyId()
{
	return '_' + Math.random().toString(36).substr(2, 9);
}

function localStorageSetObject(key, obj) {
	localStorageSet(key, JSON.stringify(obj));
}

function localStorageGetObject(key) {
	var json = localStorageGet(key);

	if (json) {
		var obj = {};
		try {
			obj = JSON.parse(json);
		} catch (e) {}
		if (obj && typeof(obj) == 'object') {
			return obj;
		} else {
			return {};
		}
	} else {
		return {};
	}
}

function localStorageSet(key, value)
{
	if (typeof(localStorage) != "undefined") {
		localStorage.setItem(key, value);
	} else {
		return false;
	}
}

function localStorageGet(key)
{
	if (typeof(localStorage) != "undefined") {
		return localStorage.getItem(key);
	} else {
		return false;
	}
}

function localStorageRemove(key)
{
	if (typeof(localStorage) != "undefined") {
		localStorage.removeItem(key);
	} else {
		return false;
	}
}*/