var displayTab = function(id) {
	jQuery("ul.tabs li").removeClass("current");
	jQuery("ul.tabs li[data-tab='" + id + "']").addClass("current");
	jQuery(".tab-content").removeClass("current");
	jQuery("#" + id).addClass("current");
}

var hideTab = function(id) {
	jQuery(".tab-link[data-tab='" + id + "']").css("display", "none");
}

jQuery(document).ready(function() {
	/* Gestion des onglets */
	jQuery("ul.tabs li").click(function() {
		displayTab(jQuery(this).attr("data-tab"))
	});
	jQuery("a[href^='#fn']").click(function() {
		displayTab('notes');
		jQuery('#notes p[class*="fn"]').removeClass('highlight');
		var hash = this.href.substr(this.href.indexOf('#'));
		var position = jQuery(hash).parent().parent().addClass('highlight').position().top + jQuery(".sidebar").scrollTop() - 88;
		jQuery(".sidebar").animate({
			scrollTop: position
		});
	});
	displayTab('info');
	if (jQuery(".footnotes_block").length) {
		jQuery(".footnotes_block").clone().addClass('sidebar').appendTo( "#notes" );
		jQuery(".footnotes_block:not(.sidebar)").remove();
		jQuery(".footnotes_block").removeClass('sidebar');
	} else {
		hideTab('notes');
	}
	if (jQuery(".bibl_block").length) {
		jQuery(".bibl_block").clone().addClass('sidebar').appendTo( "#biblio" );
		//jQuery(".bibl_block:not(.sidebar)").remove();
		jQuery(".bibl_block").removeClass('sidebar');
	} else {
		hideTab('biblio');
	}
	/* Génération de la table des matières */
	jQuery("#tocContent").toc({content: ".main", headings: "p.h1,p.h2,p.h3"});
	if (jQuery("#tocContent").children().length == 0) {
		hideTab('toc');
	}
});
