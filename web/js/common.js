document.addEventListener("DOMContentLoaded", function(event) {

	context = document.getElementById('context');
	if (context) {
		[].forEach.call(context.querySelectorAll('li'), function(li) {	
			li.addEventListener("click", function() {
				form = document.getElementById('switchContextForm');
				form.action = BASEURL[li.dataset.context];
				form.submit();
			});
		});
	}

});