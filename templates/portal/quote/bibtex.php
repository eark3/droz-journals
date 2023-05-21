@article {
	<?php echo $paper['short']; ?>,
	title={<?php echo $paper['settings']['title']; ?>},
	volume={<?php echo $issue['volume']; ?>},
<?php if (!empty($issue['number'])) { ?>
	number={<?php echo $issue['number']; ?>},
<?php } ?>
	url={<?php echo $baseURL; ?>/article/view/<?php echo $paper['short']; ?>},
<?php if (!empty($paper['settings']['pub-id::doi'])) { ?>
	DOI={<?php echo $paper['settings']['pub-id::doi']; ?>},
<?php } ?>
	journal={<?php echo str_replace('<br/>', ' ', $journal['settings']['name']); ?>},
	author={<?php echo implode(' and ', explode(', ', $paper['names'])); ?>},
	year={<?php echo date('Y', strtotime($issue['published'])); ?>},
	month={<?php echo date('M', strtotime($issue['published'])); ?>},
	pages={<?php echo $paper['pages']; ?>}
}