'use strict';

class Bang {
	doc;

	constructor(document) {
		this.doc = document;
		
	}
}


document.addEventListener('DOMContentLoaded', e => {
	return new Bang(e);
});