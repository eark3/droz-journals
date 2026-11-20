<div id="main-content" class="page page_search">
	<div class="page-header">
		<h1><?php echo $locale->search->title; ?></h1>
	</div>
	<form method="post" id="search-form" class="search-form" action="<?php echo $baseURL; ?>/search" role="search">
		<div class="form-group">
			<div class="input-group">
				<input type="text" id="query" name="query" value="<?php echo str_replace('"', '&quot;', $filters['query'] ?? ''); ?>" class="query form-control" placeholder="Rechercher">
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
			<div class="form-group">
    			<label><?php echo $locale->search->filters->order->results; ?></label>
    			<div style="display: flex; justify-content: space-around;">
<?php foreach (['DESC','ASC'] as $order) { ?>
					<div><input id="<?php echo $order; ?>" type="radio" name="order" value="<?php echo $order; ?>"<?php if ($filters['order'] === $order) { ?> checked<?php } ?>/><span style="margin-left:1em;"><?php echo $locale->search->filters->order->$order; ?></span></div>
<?php } ?>
				</div>
			</div>
		</fieldset>
		<div class="search-results">
			<h1 style="margin-bottom: 20px;"><?php echo $locale->search->results->title; ?></h1>
		</div>
<?php $this->render('/portal/widget/alert'); ?>
<?php $this->render('results'); ?>
	</form>
</div>