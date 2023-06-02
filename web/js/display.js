jQuery(document).ready(function() {
	/* Génération de la table des matières */
	jQuery("#toc").toc({content: ".main", headings: "p.h1,p.h2,p.h3"});
	/* Génération des Tabs */
	jQuery("ul.tabs li").click(function() {
		var tab_id = jQuery(this).attr("data-tab");
		jQuery("ul.tabs li").removeClass("current");
		jQuery(".tab-content").removeClass("current");
		jQuery(this).addClass("current");
		jQuery("#" + tab_id).addClass("current");
	});
	jQuery(".tab-link[data-tab='info']").addClass('current');
	if (jQuery("#toc").children().length == 0) {
		jQuery(".tab-link[data-tab='toc']").css("display", "none");
	}
	if (jQuery(".footnotes_block").length) {
		jQuery(".footnotes_block").clone().appendTo( "#notes" );
	} else {
		jQuery(".tab-link[data-tab='notes']").css("display" ,"none");
	}
	if (jQuery(".bibl_block").length) {
		jQuery(".bibl_block").clone().appendTo( "#biblio" );
		jQuery("#biblio *[id]").removeAttr('id');
	} else {
		jQuery(".tab-link[data-tab='biblio']").css("display", "none");
	}
});
