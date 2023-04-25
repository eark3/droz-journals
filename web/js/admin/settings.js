$(document).ready(function() {
	invokeZord({
		module: 'Admin',
		action: 'objects',
		success: function(objects) {
			$('#objects').aclTreeView({
				initCollapse : true,
				animationSpeed : 400,
				multy : true,
				callback: function(event, element, parameters) {
					console.log(event);
					console.log(element);
					console.log(parameters);
				}
			}, objects);
		}
	});
});