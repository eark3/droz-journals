$(window).scroll(function() {
    if ($(this).scrollTop() > 80) {
        $('#overNavbar').hide();
    } else {
        $('#overNavbar').show();
    }
});

jQuery(document).ready(function() {
	jQuery(".fancybox").fancybox();
	jQuery("a[href]").click(function(e) {
		var href = jQuery(this).attr("href");
		var index = href.indexOf('#');
		if (href !== '#' && index >= 0) {
			e.preventDefault();
			href = href.substring(index);
			var position = jQuery(href).offset().top;
			jQuery("body, html").animate({
				scrollTop: position - 90
			});
		}
	});
});

document.addEventListener("DOMContentLoaded", function(event) {
	
	var toggleMenu = function(toggle) {
		var menu = toggle.nextElementSibling;
		if (toggle.dataset.toggle == 'dropdown') {
			toggle.dataset.toggle = 'pullup'
			menu.style.display = 'block';
		} else {
			toggle.dataset.toggle = 'dropdown'
			menu.style.display = 'none';
			document.activeElement.blur();
		}
	};
	
	[].forEach.call(document.querySelectorAll('.dropdown-toggle'), function(toggle) {
		toggle.addEventListener("click", function(event) {
			event.preventDefault();
			toggleMenu(toggle);
			event.stopPropagation();
			return false;
		});
		toggle.nextElementSibling.addEventListener("mouseleave", function(event) {
			toggleMenu(toggle);
		});
	});
	
	[].forEach.call(document.querySelectorAll('form.search-form'), function(form) {
		form.addEventListener("submit", function(event) {
			var query = form.querySelector('input[type="text"].query');
			if (query && query.value === '') {
				query.style.border = '1px red solid';
				event.preventDefault();
				event.stopPropagation();
				return false;
			}
		});
	});

});