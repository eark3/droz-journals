<div id="main-content" class="page page_search">
	<div class="page-header">
		<h1><?php echo $locale->search->title; ?></h1>
	</div>
	<form method="post" id="search-form" class="search-form" action="<?php echo $baseURL; ?>/search" role="search">
		<div class="form-group">
			<div class="input-group">
				<input type="text" id="query" name="query" value="<?php echo $filters['query'] ?? ''; ?>" class="query form-control" placeholder="Rechercher">
				<span class="input-group-btn">
					<input type="submit" value="<?php echo $locale->search->submit; ?>" class="btn btn-default">
				</span>
			</div>
		</div>
		<fieldset class="search-advanced">
			<h2><?php echo $locale->search->filters->enhanced; ?></h2>
			<div class="row">
				<div class="col-md-6">
<?php foreach (['from','to'] as $limit) {?>
					<div class="form-group">
						<label for="<?php echo $limit; ?>"><?php echo $locale->search->filters->$limit; ?></label>
						<input class="form-control" type="date" name="<?php echo $limit; ?>" value="<?php echo $filters[$limit] ?? ''; ?>">
					</div>
<?php } ?>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label for="authors"><?php echo $locale->search->filters->authors; ?></label>
						<input class="form-control" type="text" name="authors" value="<?php echo $filters['authors'] ?? ''; ?>">
					</div>
				</div>
			</div>
		</fieldset>
		<div class="search-results">
			<h1 style="margin-bottom: 20px;"><?php echo $locale->search->results->title; ?></h1>
		</div>
<?php $this->render('/portal/widget/alert'); ?>
<?php if (!empty($models['papers'])) { ?>
<?php   $this->render('results'); ?>
<?php }?>
	</form>
</div>