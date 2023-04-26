$(document).ready(function() {
	invokeZord({
		module:  'Admin',
		action:  'journals',
		before:  function() {
			$dialog.wait();
		},
		after:   function() {
			$dialog.hide();
		},
		success: function(journals) {
			var tree = [];
			[].forEach.call(journals, function(journal) {
				var journalNodes = [];
				journal.type = 'journal';
				journal.label = 'Réglage du journal';
				journalNodes.push(journal);
				if (journal.issues.length > 0) {
					var issues = [];
					[].forEach.call(journal.issues, function(issue) {
						var issueNodes = [];
						issue.type = 'issue';
						issue.label = 'Réglage du numéro';
						issueNodes.push(issue);
						if (Object.keys(issue.sections).length > 0) {
							var sections = [];
							for (const id in issue.sections) {
								var section = issue.sections[id];
								var sectionNodes = [];
								section.type = 'section';
								section.label = 'Réglage de la section';
								sectionNodes.push(section);
								if (section.papers.length > 0) {
									var papers = [];
									[].forEach.call(section.papers, function(paper) {
										var paperNodes = [];
										paper.type = 'paper';
										paper.label = "Réglage de l'article";
										paperNodes.push(paper);
										if (paper.authors !== undefined && paper.authors.length > 0) {
											var authorNodes = [];
											[].forEach.call(paper.authors, function(author) {
												author.type = 'author';
												author.label = author.reverse;
												authorNodes.push(author);
											});
											paperNodes.push({
												label : 'Liste des auteurs',
												ul    : authorNodes
											});
										}
										papers.push({
											label : paper.short,
											ul    : paperNodes
										});
									});
									sectionNodes.push({
										label : 'Liste des articles',
										ul    : papers
									});
								}
								sections.push({
									label : section.name,
									ul    : sectionNodes
								});
							};
							issueNodes.push({
								label : 'Liste des sections',
								ul    : sections
							});
						}
						issues.push({
							label : issue.short,
							ul    : issueNodes
						});
					});
					journalNodes.push({
						label: 'Liste des numéros',
						ul:    issues
					});
				}
				tree.push({
					label : journal.context,
					ul    : journalNodes
				});
			});
			$('#journals').aclTreeView({
				initCollapse : true,
				animationSpeed : 400,
				multy : false,
				callback: function(event, element, parameters) {
					console.log(parameters.type + ' ' + parameters.id + ' ');
				}
			}, tree);
		}
	});
});