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
			setCSLParams({
				'lang':  LANG,
				'style': element.dataset.style
			});
			updateCitation();
		});
	});
	
	updateCitation();

});