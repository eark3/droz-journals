<?php if (!empty($models['papers'])) { ?>
		<div id="results">
<?php foreach ($models['papers'] ?? [] as $paper) { ?>
<?php   $this->render('/portal/widget/paper', $paper); ?>
<?php } ?>
<?php if ($models['found'] > $models['count']) { ?>
			<div class="cmp_pagination">
				<?php echo Zord::substitute($locale->search->results->pagination, ['from' => $models['start'] + 1, 'to' => min([$models['start'] + $models['rows'], $models['found']]), 'total' => $models['found']]); ?>&nbsp;
				<span data-search="<?php echo $models['search']; ?>" data-start="0">&lt;&lt;</span>&nbsp;
				<span data-search="<?php echo $models['search']; ?>" data-start="<?php echo max([0, $start - $rows]); ?>">&lt;</span>&nbsp;
<?php   for ($index = 0; $index < ceil($models['found'] / $models['rows']); $index++) { ?>
				<span data-search="<?php echo $models['search']; ?>" data-start="<?php echo $index * $models['rows']; ?>"><?php echo $index + 1; ?></span>&nbsp;
<?php   } ?>
				<span data-search="<?php echo $models['search']; ?>" data-start="<?php echo min([$models['rows'] * floor($models['found'] / $models['rows']), $start + $rows]); ?>">&gt;</span>&nbsp;
				<span data-search="<?php echo $models['search']; ?>" data-start="<?php echo $models['rows'] * floor($models['found'] / $models['rows']); ?>">&gt;&gt;</span>&nbsp;
			</div>
<?php } ?>
		</div>
<?php }?>
