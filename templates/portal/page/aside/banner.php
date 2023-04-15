<?php if (isset($journal['settings']['bannerLink']) && isset($journal['settings']['bannerImage'])) { ?>
		<div class="block_banner <?php echo strtolower($context); ?>">
			<div class="">
				<a href="<?php echo $journal['settings']['bannerLink']; ?>" target="_blank">
					<img class="img-responsive" src="<?php echo $journal['settings']['bannerImage']; ?>">
				</a>
			</div>
		</div>
<?php } ?>
