						<section id="<?php echo $section['name']; ?>" class="section <?php echo empty($section['parent']) ? 'top' : 'sub'; ?>" style="clear: both;">
							<div class="page-header">
								<p>
									<?php echo $section['settings']['title']; ?>
								</p>
							</div>
<?php foreach (($section['papers'] ?? []) as $paper) { ?>
<?php   $this->render('#paper', ['paper' => $paper]); ?>
<?php } ?>
						</section>
