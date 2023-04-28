var displayForm = function(parameters) {
	invokeZord({
		module  : 'Admin',
		action  : 'settings',
		type    : parameters.type,
		id      : parameters.id,
		return  : 'form',
		success : function(form) {
			$('#form').html(form);
			$('#form textarea.html').trumbowyg({autogrow:true});
			$('#form input.image').change(function() {
				const file = this.files[0];
				const preview = this.id;
				if (file) {
					let reader = new FileReader();
					reader.onload = function (event) {
						$('#form .preview.' + preview + ' img').attr("src", event.target.result);
					};
					reader.readAsDataURL(file);
				}
			});
		}
	});
};

$(document).ready(function() {
	[].forEach.call(['journal','issue','section','paper','author'], function(type) {
		$('#' + type).bind({
			change: function(event) {
				displayForm({
					type : this.name,
					id   : this.value
				});
			}
		});
	});
	displayForm({
		type : 'journal',
		id   : document.getElementById('journal').value
	});
});