								<div class="col-xs-12 articleSummaryWrapper">
									<div class="media-body">
										<div class="col-xs-3">
<?php foreach ($paper['galleys'][JournalsUtils::readable($user, $controler->journal, $issue ?? $controler->issue, $paper)] ?? [] as $type) { ?>
<?php   $this->render('#galley', array_merge($models, ['type' => $type])); ?>
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
