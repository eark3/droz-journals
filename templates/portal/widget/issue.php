				<div class="issue-toc">
					<div class="heading row">
						<div class="col-md-12">
							<p style="margin-top: 5px !important; margin-bottom: 15px !important;" class="page-header">
								<?php echo $issue['title']; ?>
							</p>
						</div>
					</div>
					<div class="heading row">
						<div class="col-md-4">
							<a class="fancybox" href="<?php echo $issue['cover']; ?>">
								<img class="imgCover img-responsive" src="<?php echo $issue['cover']; ?>">
							</a>
						</div>
						<div style="margin-top:;" class="issue-details col-md-8">
							<div class="description">
								<?php echo $issue['description']; ?>
								<?php echo $locale->published; ?>: <?php echo $issue['published']; ?>
							</div>
						</div>
					</div>
					<div class="heading row">
						<div class="col-md-12">
							<div class="description issue_toc pkp_block block_toc1">
								<div class="content">
									<div class="left">
										<h2>
											<?php echo $locale->issue->summary; ?>
										</h2>
									</div>
									<div class="right">
										<ul style="text-align: right; font-size: 16px">
<?php foreach ($issue['sections'] as $id => $section) { ?>
											<li><a href="<?php echo $_SERVER['REQUEST_URI']; ?>#<?php echo $id; ?>"><?php echo $section['title']; ?></a></li>
<?php } ?>
										</ul>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="sections">
<?php foreach ($issue['sections'] as $id => $section) { ?>
<?php   $this->render('section', ['id' => $id, 'section' => $section]); ?>
<?php } ?>
					</div>
				</div>
