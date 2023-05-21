document.addEventListener("DOMContentLoaded", function(event) {

	var cslContainer = document.getElementById('citationOutput').firstElementChild.firstElementChild.firstElementChild;

	var updateCitation = function() {
		invokeZord({
			module:'Portal',
			action:'reference',
			paper:cslContainer.dataset.paper,
			success:function(reference) {
				addCSLObject('articles', reference);
				setBiblio('articles', cslContainer, reference);
				style = getCSLParam('style');
				[].forEach.call(document.querySelectorAll('#cslCitationFormats li'), function(element) {
					if (style == element.dataset.style) {
						element.classList.add('selected');
					} else {
						element.classList.remove('selected');
					}
				});
			}
		});
	};
	
	[].forEach.call(document.querySelectorAll('#cslCitationFormats li'), function(element) {
		if (style = element.dataset.style) {
			element.classList.add('selected');
		}
		element.addEventListener("click", function(event) {
			switch (element.dataset.action) {
				case 'display': {
					setCSLParams({
						'lang':  LANG,
						'style': element.dataset.style
					});
					updateCitation();
					break;
				}
				case 'download': {
					invokeZord({
						module: 'Portal',
						action: 'quote',
						output: 'download',
						style : element.dataset.style,
						paper : cslContainer.dataset.paper
					});
					break;
				}
			}
		});
	});
	
	setCSLParams({
		'lang':  LANG,
		'style': 'acm-sig-proceedings'
	});
	updateCitation();

});