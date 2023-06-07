		<div id="main-site" class="page_index_site">
			<div class="about_site">Portail de revues de la Librairie Droz</div>
			<div class="journals">
				<div class="page-header">
					<h2><?php echo $host; ?></h2>
				</div>
				<ul class="media-list">
<?php foreach ($journals as $journal) { ?>
<?php   $this->render('journal', ['journal' => $journal]); ?>
<?php } ?>
				</ul>
			</div>
		</div>
