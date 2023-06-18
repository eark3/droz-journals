var bindUI = function() {
	[].forEach.call(['journal','issue','section','paper','author'], function(type) {
		$('#select #' + type).bind({
			change: function() {
				displayUI(this.name, this.value, LANG);
			}
		});
	});
	$('#select button.up').bind({
		click: function() {
			displayUI(this.previousElementSibling.name, this.previousElementSibling.value, LANG);
		}
	});
	$('#select #next').bind({
		click: function() {
			displayUI(this.dataset.type, 'first', LANG);
		}
	});
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
					if (result.errors) {
						var errors = '';
						[].forEach.call(result.errors, function(error) {
							errors = errors + error + "\n";
						});
						alert(errors);
					} else {
						alert(result.message);
					}
				}
			});
		}
	});
	$('#form span.lang').bind({
		click: function() {
			displayUI(this.dataset.type, this.dataset.id, this.dataset.lang);
		}
	});
	activateChosen();
};

var displayUI = function(type, id, lang) {
	var parameters = {
		module  : 'Admin',
		action  : 'settings',
		type    : type,
		id      : id,
		_lang   : lang,
		return  : 'ui',
		success : function(result) {
			$('#select').html(result.select);
			$('#form').html(result.form);
			bindUI();
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
	if ($('#select').children().length === 0 || $('#form').children().length === 0) {
		displayUI('journal', 'first', LANG);
	} else {
		bindUI();
	}
});