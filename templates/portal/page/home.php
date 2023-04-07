<div class="pkp_structure_content container">
	<main class="pkp_structure_main col-xs-12 col-sm-10 col-md-8" role="main">
		<div id="main-content" class="page_index_journal" role="content">
			<div class="media">
				<div class="media-left media-top">
					<div class="homepage-image">
						<a class="fancybox" href="https://revues.droz.org/public/journals/18/homepageImage_fr_FR.jpg">
							<img class="img-responsive" src="https://revues.droz.org/public/journals/18/homepageImage_fr_FR.jpg" alt="">
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
				<h1 class="current_issue_heading">Dernier numéro</h1>
<?php $this->render('/portal/widget/issue'); ?>
				<div style="padding-top: 20px; clear: both;"></div>
				<a href="/CFS/issue/archive" class="btn btn-primary read-more"> Voir tous les numéros <span class="glyphicon glyphicon-chevron-right"></span></a>
			</section>
			<section class="additional_content">
<?php $this->render('additional'); ?>
			</section>
		</div>
	</main>
	<aside id="sidebar" class="pkp_structure_sidebar left col-xs-12 col-sm-4" role="complementary" aria-label="Barre de navigation">
<?php $this->render('aside/search'); ?>
<?php $this->render('aside/shop'); ?>
<?php $this->render('aside/information'); ?>
<?php $this->render('aside/link'); ?>
	</aside>
</div>