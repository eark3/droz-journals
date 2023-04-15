								<div class="col-xs-12 articleSummaryWrapper">
									<div class="media-body">
										<div class="col-xs-3">
<?php foreach ($paper['galleys'][$user->isConnected()] ?? [] as $type => $path) { ?>
<?php   $this->render('/portal/widget/galley', ['type' => $type, 'path' => $path]); ?>
<?php } ?>
										</div>
										<div class="col-xs-9">
											<p class="media-heading">
												<a href="<?php echo $baseURL; ?>/article/view/<?php echo $paper['short']; ?>">
													<?php echo $paper['settings']['title']; ?><br />
													<?php if (!empty($paper['settings']['subtitle'])) { ?>
													<span class="small"><?php echo $paper['settings']['subtitle']; ?></span>
													<?php } ?>
												</a>
											</p>
											<div class="meta">
												<div class="authors" style="float: left;"><?php echo !empty($paper['authors']) ? $paper['names'] : ''; ?></div>
												<div class="pages" style="float: right;"><?php echo $paper['pages']; ?></div>
											</div>
										</div>
									</div>
								</div>
