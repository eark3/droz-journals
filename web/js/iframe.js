$(document).ready(function() {
	if (window.location.href.indexOf("/HCL/") > -1) {
		$('.header_view').css("background-color","#e4d0ba");
		$('.header_view').css("border-color","#e4d0ba");
		$('.header_view').css("color","#d62d37");
		$('.header_view span').css("color","#d62d37");
		$('.header_view a').css("color","#d62d37");
	}
});

var myIframe = document.getElementById('iframeArticle');

myIframe.addEventListener("load", function() {
	$('#iframeArticle').contents().find('head link').remove();
	$('#iframeArticle').contents().find('head').append('<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto" type="text/css" />');	  
	$('#iframeArticle').contents().find('head').append('<link rel="stylesheet" href="/journals/css/article.css" type="text/css" />');
	$('#iframeArticle').contents().find('head').append('<link rel="stylesheet" href="/journals/css/sidebar.css" type="text/css" />');
	if (window.location.href.indexOf("/HCL/") > -1) {
		var style = document.createElement('style');
		style.textContent = '.center {text-align:center;color: #606060;}.header_view{background:#e4d0ba !important;}';
	} else {
		var style = document.createElement('style');
		style.textContent = '.center {text-align:center;color: #606060;}';
	}
	myIframe.contentDocument.head.appendChild(style);
});
