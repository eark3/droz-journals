		<div id="main-content" class="page_index_journal" role="content">
			<div class="media">
				<div class="media-left media-top">
					<div class="homepage-image">
						<a class="fancybox" href="/public/journals/images/<?php echo $context; ?>/<?php echo $journal['settings']['homepageImage']['uploadName'] ?? 'homepageImage_fr_FR.jpg'; ?>">
							<img class="img-responsive" src="/public/journals/images/<?php echo $context; ?>/<?php echo $journal['settings']['homepageImage']['uploadName'] ?? 'homepageImage_fr_FR.jpg'; ?>" alt="<?php echo $journal['settings']['homepageImage']['altText'] ?? ''; ?>">
						</a>
					</div>
				</div>
				<div class="media-body" style="margin-bottom: 0 !important; padding-bottom: 0 !important;">
					<div class="journal-description" style="margin-bottom: 0 !important; padding-bottom: 0 !important;">
						<?php echo $journal['settings']['description'] ?? ''; ?>
					</div>
				</div>
			</div>
<?php if ($issue ?? false) { ?>
			<section class="current_issue">
				<h1 class="current_issue_heading"><?php echo $locale->issue->last; ?></h1>
<?php $this->render('/portal/widget/issue'); ?>
				<div style="padding-top: 20px; clear: both;"></div>
				<a href="<?php echo $baseURL; ?>/issue/archive" class="btn btn-primary read-more"><?php echo $locale->issue->archive; ?><span class="glyphicon glyphicon-chevron-right"></span></a>
			</section>
			<section class="additional_content">
				<?php echo $journal['settings']['additionalHomeContent'] ?? ''; ?>
			</section>
<?php } ?>
		</div>
