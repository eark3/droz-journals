var scrollToAnchor = function(hash, top) {
	var element = document.getElementById(hash.substring(1));
	var position = jQuery(element).offset().top;
	jQuery("body, html").animate({
		scrollTop: top ? 0 : position - 90
	});
}

$(window).scroll(function() {
    if ($(this).scrollTop() > 80) {
        $('#overNavbar').hide();
    } else {
        $('#overNavbar').show();
    }
});

jQuery(document).ready(function() {
	jQuery(".fancybox").fancybox();
	jQuery("a[href].summary").click(function(e) {
		var href = jQuery(this).attr("href");
		var index = href.indexOf('#');
		if (href !== '#' && index >= 0) {
			var hash = href.substring(index);
			var section = document.getElementById(hash.substring(1));
			if (section !== undefined && section !== null) {
				e.preventDefault();
				e.stopPropagation();
				scrollToAnchor(hash);
			}
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
	
	[].forEach.call(document.querySelectorAll('.dropdown'), function(dropdown) {
		var toggle = dropdown.querySelector('.dropdown-toggle');
		var menu = dropdown.querySelector('.dropdown-menu');
		if (toggle && menu) {
			toggle.addEventListener("click", function(event) {
				event.preventDefault();
				toggleMenu(toggle);
				event.stopPropagation();
				return false;
			});
			menu.addEventListener("mouseleave", function(event) {
				toggleMenu(toggle);
			});
		}
	});
	
	/*
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
	*/
	
	var pagination = function(id, parameters, top) {
		list = document.getElementById(id);
		if (list) {
			parameters['success'] = function(response) {
				list = document.getElementById(id);
				list.outerHTML = response;
				pagination(id, parameters, top);
			};
			[].forEach.call(document.querySelectorAll('#' + id + ' .cmp_pagination span'), function(span) {
				span.addEventListener('click', function() {
					for (var key in span.dataset) {
						parameters[key] = span.dataset[key];
					}
					invokeZord(parameters);
				});
			});
			scrollToAnchor('#' + id, true);
		}
	};
	
	pagination('issues', {
		module: 'Portal',
		action: 'issue',
		page  : 'archive'
	}, true);

	pagination('results', {
		module: 'Portal',
		action: 'search'
	}, false);
	
	if (window.location.hash) {
		scrollToAnchor(window.location.hash);
	}
	
});