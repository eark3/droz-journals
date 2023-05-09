var displayUI = function(type, id) {
	var parameters = {
		module  : 'Admin',
		action  : 'settings',
		type    : type,
		id      : id,
		return  : 'ui',
		success : function(result) {
			$('#select').html(result.select);
			[].forEach.call(['journal','issue','section','paper','author'], function(type) {
				$('#select #' + type).bind({
					change: function() {
						displayUI(this.name, this.value);
					}
				});
			});
			$('#select #next').bind({
				click: function() {
					displayUI(this.dataset.type, 'first');
				}
			});
			$('#form').html(result.form);
			$("#form .fancybox").fancybox();
			$('#form textarea.html').trumbowyg({
				autogrow : true,
				lang     : LANG.substr(0, 2)
			});
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
			$('#form form').bind({
				submit: function(event) {
					event.preventDefault();
					invokeZord({
						form: this,
						upload: true,
						uploading: function() {
							$dialog.wait();
						},
						uploaded: function() {
							$dialog.hide();
						},
						success: function(result) {
							alert(result.message);
						}
					});
				}
			});
		},
		failure: function(error) {
			if (error.code === '404') {
				alert(error.message);
			}
		}
	};
	[].forEach.call(['journal','issue','section','paper','author'], function(type) {
		select = document.getElementById(type);
		if (select !== undefined && select !== null) {
			parameters[type] = select.value;
		}
	});
	invokeZord(parameters);
};

$(document).ready(function() {
	displayUI('journal', 'first');
});