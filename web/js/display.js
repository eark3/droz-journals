jQuery(document). ready(function() {
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
	jQuery(".tab-link[data-tab='tab-2']").css("display" ,"none");
	if (jQuery(".bibl_block").length) {
		jQuery(".bibl_block").clone().appendTo( "#tab-3" );
	} else {
		jQuery(".tab-link[data-tab='tab-3']").css("display", "none");
	}
	if (jQuery("#toc").children().length == 0) {
		jQuery(".tab-link[data-tab='tab-1']").css("display", "none");
	}
});
