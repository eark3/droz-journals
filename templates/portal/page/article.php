<article class="article-details">
	<header>
		<h1 class="page-header">
			<?php echo $paper['settings']['title']; ?>
<?php if (!empty($paper['subtitle'])) { ?>
			<p style="margin-top:4px;" class="small"><?php echo $paper['subtitle']; ?></p>
<?php } ?>
		</h1>
	</header>
	<div class="row">
		<section class="article-sidebar col-md-4">
			<div class="cover-image">
<?php $this->render('/portal/widget/cover'); ?>
			</div>
			<div>
				<div class="list-group-item date-published"><?php echo $locale->published; ?> <?php echo $issue['published']; ?></div>
<?php if (!empty($paper['settings']['pub-id::doi'])) { ?>
				<div class="list-group-item doi">
					<strong>DOI : </strong>
					<a href="https://doi.org/<?php echo $paper['settings']['pub-id::doi']; ?>"><?php echo $paper['settings']['pub-id::doi']; ?></a>
				</div>
<?php } ?>
			</div>
<?php foreach ($paper['galleys'][JournalsUtils::readable($user, $controler->journal, $controler->issue, $controler->paper)] ?? [] as $type) { ?>
<?php   $this->render('/portal/widget/galley', array_merge($models, ['type' => $type])); ?>
<?php } ?>
			<div class="panel panel-default copyright">
				<div class="panel-body">
					<a href="<?php echo $baseURL; ?>/licence" class="copyright"><?php echo $issue['copyright']; ?></a>
				</div>
			</div>
		</section>
		<div class="col-md-8">
			<section class="article-main">
<?php if (!empty($paper['authors'])) { ?>
				<div class="authors">
<?php   foreach ($paper['authors'] as $author) { ?>
					<strong><?php echo $author['name']; ?></strong>
<?php     if (isset($author['settings']['affiliation'])) { ?>
					<div class="article-author-affilitation"><?php echo $author['settings']['affiliation']; ?></div>
<?php     } ?>
<?php   } ?>
				</div>
<?php }?>
<?php if (isset($paper['settings']['abstract'])) { ?>
				<div class="article-summary" id="summary">
					<h2><?php echo $locale->article->abstract; ?></h2>
					<div class="article-abstract">
						<?php echo $paper['settings']['abstract']; ?>
					</div>
				</div>
<?php } ?>
			</section>
			<div class="item citation">
				<div class="panel panel-default citation_formats">
					<div class="panel-heading"><?php echo $locale->article->quote->how; ?></div>
					<div class="panel-body">
						<div class="sub_item citation_display">
							<div class="value">
								<div id="citationOutput" role="region" aria-live="polite">
									<div class="csl-bib-body">
										<div class="csl-entry">
											<div class="csl-right-inline" data-paper="<?php echo $paper['short']; ?>">
											</div>
										</div>
									</div>
								</div>
								<div class="citation_formats">
									<?php echo $locale->article->quote->format; ?>
									<div id="cslCitationFormats" class="citation_formats_list" aria-hidden="true">
										<ul class="citation_formats_styles">
<?php foreach (Zord::value('quote', 'format') as $style => $label) { ?>
											<li data-style="<?php echo $style; ?>" data-action="display">
												<span aria-controls="citationOutput">
													<?php echo $label; ?>
												</span>
											</li>
<?php } ?>
										</ul>
										<h2 class="label"><?php echo $locale->article->quote->download; ?></h2>
										<ul class="citation_formats_styles">
<?php foreach (Zord::value('quote', 'download') as $style => $_style) { ?>
											<li data-style="<?php echo $style; ?>" data-action="download">
												<span class="fa fa-download">
													<?php echo $_style['label']; ?>
												</span>
											</li>
<?php } ?>
										</ul>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<section class="article-more-details">
					<div class="panel panel-default issue">
						<div class="panel-heading"><?php echo $locale->article->issue; ?></div>
						<div class="panel-body">
							<a class="title" href="<?php echo $baseURL; ?>/issue/view/<?php echo $issue['short']; ?>">
								<?php echo $issue['serial']?><?php echo !empty($issue['settings']['title']) ? ' : '.$issue['settings']['title'] : ''; ?></a>
						</div>
					</div>
<?php if (!empty($others)) { ?>
					<div class="panel panel-default articlesBySameAuthorList">
						<div class="panel-heading"><?php echo $locale->article->others; ?></div>
						<div id="articlesBySameAuthorList" class="panel-body">
							<ul>
<?php   foreach ($others as $other) { ?>
								<li>
<?php     if (!empty($other['authors'])) { ?>
									<?php echo implode(', ', $other['authors']); ?>,
<?php     } ?>
									<a href="<?php echo $other['paper']['url']; ?>"><i><?php echo $other['paper']['title']; ?></i></a>,
									<a href="<?php echo $other['issue']['url']; ?>"><?php echo $other['issue']['title']; ?></a>
								</li>
<?php   } ?>
							</ul>
						</div>
					</div>
<?php } ?>
				</section>
			</div>
		</div>
	</div>
</article>