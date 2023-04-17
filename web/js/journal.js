jQuery(document).ready(function() {
	jQuery(".fancybox").fancybox();
	var block_toc1 = jQuery('.block_toc1');
	var block_toc2 = jQuery('.block_toc2');
	if (block_toc1.length > 0 && block_toc2.length > 0) {
		var fixmeTop = jQuery('.block_toc1').offset().top;
		jQuery(window).scroll(function() {
		    var currentScroll = jQuery(window).scrollTop();
		    if (currentScroll >= fixmeTop - 70) {
		    	var parentw = jQuery('#sidebar').width();
		        jQuery('.block_toc2').css({
		            position: 'fixed',
		            top: '89px',
		            right: 'auto',
		            width: parentw + 'px',
		            zIndex: '20',
		            display:'block'
		        });
		    } else {
		        jQuery('.block_toc2').css({
		        	display:'none',
		            position: 'static',
		            width:'auto'
		        });
		        jQuery('.block_toc1').css({
		        	display:'block',
		        });
		    }
		});
	}
});