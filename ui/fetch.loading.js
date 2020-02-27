'use strict';

let fetchCounter = 0;
let _oldFetch = fetch; 

window.fetch = function() {
	let fetchStart = new Event('fetchStart', {'view': document, 'bubbles': true, 'cancelable': false});
	let fetchEnd = new Event('fetchEnd', {'view': document, 'bubbles': true, 'cancelable': false});
	let fetchCall = _oldFetch.apply(this, arguments);
	document.dispatchEvent(fetchStart);
	fetchCall.then(function(){
		document.dispatchEvent(fetchEnd);
	})
	.catch(function(){
		document.dispatchEvent(fetchEnd);
	});
	return fetchCall;
};

document.addEventListener('fetchStart', function(e) {
	fetchCounter++;
	document.body.classList.add('progress');
	document.querySelector('#fetching').classList.add('loading');
});

document.addEventListener('fetchEnd', function(e) {
	fetchCounter--;
	document.body.classList.remove('progress');
	if (!fetchCounter)
		document.querySelector('#fetching').classList.remove('loading');
});