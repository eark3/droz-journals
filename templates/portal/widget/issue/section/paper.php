								<div class="col-xs-12 articleSummaryWrapper">
									<div class="media-body">
										<div class="col-xs-3">
<?php foreach ($paper['galleys'] as $type => $path) { ?>
<?php   $this->render('/portal/widget/galley', ['type' => $type, 'path' => $path]); ?>
<?php } ?>
										</div>
										<div class="col-xs-9">
											<p class="media-heading">
												<a href="<?php echo $baseURL; ?>/article/view/<?php echo $paper['id']; ?>">
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
