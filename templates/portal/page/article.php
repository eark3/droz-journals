<article class="article-details">
	<header>
		<h1 class="page-header">
			<?php echo $paper['title']; ?>
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
<?php if (!empty($paper['settings']['doi'])) { ?>
				<div class="list-group-item doi">
					<strong>DOI : </strong>
					<a href="https://doi.org/<?php echo $paper['settings']['pub-id::doi']; ?>"><?php echo $paper['settings']['pub-id::doi']; ?></a>
				</div>
<?php } ?>
			</div>
<?php foreach ($paper['galleys'] ?? [] as $type => $path) { ?>
<?php   $this->render('/portal/widget/galley', ['type' => $type, 'path' => $path]); ?>
<?php } ?>
			<div class="panel panel-default copyright">
				<div class="panel-body">
					<a href="<?php echo $baseURL; ?>/licence" class="copyright"><?php echo $issue['copyright']; ?></a>
				</div>
			</div>
		</section>
		<div class="col-md-8">
			<section class="article-main">
				<div class="authors">
					<strong><?php echo !empty($paper['authors']) ? $paper['names'] : ''; ?></strong>
				</div>
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
											<div class="csl-right-inline" data-paper="<?php echo $paper['id']; ?>">
											</div>
										</div>
									</div>
								</div>
								<div class="citation_formats">
									<?php echo $locale->article->quote->format; ?>
									<div id="cslCitationFormats" class="citation_formats_list" aria-hidden="true">
										<ul class="citation_formats_styles">
<?php foreach (Zord::value('quote', 'format') as $style => $label) { ?>
											<li data-style="<?php echo $style; ?>">
												<span aria-controls="citationOutput">
													<?php echo $label; ?>
												</span>
											</li>
<?php } ?>
										</ul>
										<h2 class="label"><?php echo $locale->article->quote->download; ?></h2>
										<ul class="citation_formats_styles">
<?php foreach (Zord::value('quote', 'download') as $style => $_style) { ?>
											<li>
												<a href="<?php echo $baseURL; ?>/quote/download/<?php echo $style; ?>/<?php echo $paper['short']; ?>">
													<span class="fa fa-download"></span>
													<?php echo $_style['label']; ?>
												</a>
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
								<?php echo $issue['serial']?> : <?php echo $issue['title']?>
							</a>
						</div>
					</div>
				</section>
			</div>
		</div>
	</div>
</article>