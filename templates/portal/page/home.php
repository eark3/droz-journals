<div class="pkp_structure_content container">
	<main class="pkp_structure_main col-xs-12 col-sm-10 col-md-8" role="main">
		<div id="main-content" class="page_index_journal" role="content">
			<div class="media">
				<div class="media-left media-top">
					<div class="homepage-image">
						<a class="fancybox" href="/public/journals/thumbnails/<?php echo $context; ?>.jpg">
							<img class="img-responsive" src="/public/journals/thumbnails/<?php echo $context; ?>.jpg" alt="<?php echo $journal['name']; ?>">
						</a>
					</div>
				</div>
				<div class="media-body" style="margin-bottom: 0 !important; padding-bottom: 0 !important;">
					<div class="journal-description" style="margin-bottom: 0 !important; padding-bottom: 0 !important;">
<?php $this->render('description'); ?>
					</div>
				</div>
			</div>
			<section class="current_issue">
				<h1 class="current_issue_heading"><?php echo $locale->issue->last; ?></h1>
<?php $this->render('/portal/widget/issue'); ?>
				<div style="padding-top: 20px; clear: both;"></div>
				<a href="/<?php echo $context; ?>/issue/archive" class="btn btn-primary read-more"><?php echo $locale->issue->archive; ?><span class="glyphicon glyphicon-chevron-right"></span></a>
			</section>
			<section class="additional_content">
<?php $this->render('additional'); ?>
			</section>
		</div>
	</main>
	<aside id="sidebar" class="pkp_structure_sidebar left col-xs-12 col-sm-4" role="complementary" aria-label="Barre de navigation">
<?php foreach ($layout['aside'] as $component) { ?>
<?php   $this->render('aside/'.$component); ?>
<?php } ?>
	</aside>
</div>