				<div class="issue-toc">
					<div class="heading row">
						<div class="col-md-12">
							<p style="margin-top: 5px !important; margin-bottom: 15px !important;" class="page-header">
								<?php echo $issue['serial']; ?><?php echo !empty($issue['settings']['title']) ? ' : '.$issue['settings']['title'] : ''; ?>
							</p>
						</div>
					</div>
					<div class="heading row">
						<div class="col-md-4">
<?php $this->render('#cover'); ?>
						</div>
						<div style="margin-top:;" class="issue-details col-md-8">
							<div class="description">
								<?php echo str_replace('https://www.droz.org', SHOP_BASE_URL, $issue['settings']['description']); ?>
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
<?php foreach ($issue['sections'] as $section) { ?>
											<li class="<?php echo empty($section['parent']) ? 'top' : 'sub'; ?>"><a href="<?php echo $_SERVER['REQUEST_URI']; ?>#<?php echo $section['name']; ?>"><?php echo $section['settings']['title']; ?></a></li>
<?php } ?>
										</ul>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="sections">
<?php foreach ($issue['sections'] as $section) { ?>
<?php   $this->render('#section', ['section' => $section]); ?>
<?php } ?>
					</div>
				</div>
