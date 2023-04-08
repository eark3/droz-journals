								<div class="col-xs-12 articleSummaryWrapper">
									<div class="media-body">
										<div class="col-xs-3">
<?php foreach ($paper['galleys'] as $type => $path) { ?>
											<a class="galley-link<?php echo $type === 'shop' ? '-buy-droz' : ''; ?> btn btn-sm btn-default file restricted" role="button" href="<?php echo $path; ?>">
												<span class="sr-only"><?php echo $locale->access->subscription ?></span>
												<?php echo $locale->galleys->$type; ?>
<?php   if ($type === 'shop') { ?>
												&nbsp;<img src="/journals/img/html-galley-btn-arrow-8x8.svg" class="html-galley-btn-arrow">
<?php   } ?>
											</a>
<?php } ?>
										</div>
										<div class="col-xs-9">
											<p class="media-heading">
												<a href="/CFS/article/view/3289">
													<?php echo $paper['title']; ?><br />
													<?php if (!empty($paper['subtitle'])) { ?>
													<span class="small"><?php echo $paper['subtitle']; ?></span>
													<?php } ?>
												</a>
											</p>
											<div class="meta">
												<div class="authors" style="float: left;"><?php echo !empty($paper['authors']) ? implode(', ', $paper['authors']) : ''; ?></div>
												<div class="pages" style="float: right;"><?php echo $paper['pages']; ?></div>
											</div>
										</div>
									</div>
								</div>
