		<div id="main-content" class="page_index_journal" role="content">
			<div class="media">
				<div class="media-left media-top">
					<div class="homepage-image">
						<a class="fancybox" href="<?php echo $journal['thumbnail']; ?>">
							<img class="img-responsive" src="/public/journals/images/<?php echo $context; ?>/<?php echo $journal['settings']['homepageImage']['uploadName']; ?>" alt="<?php echo $journal['settings']['homepageImage']['altText'] ?? ''; ?>">
						</a>
					</div>
				</div>
				<div class="media-body" style="margin-bottom: 0 !important; padding-bottom: 0 !important;">
					<div class="journal-description" style="margin-bottom: 0 !important; padding-bottom: 0 !important;">
						<?php echo $journal['settings']['description']; ?>
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
				<?php echo $journal['settings']['additionalHomeContent'] ?? ''; ?>
			</section>
		</div>
