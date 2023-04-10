@article {
	<?php echo JournalsPortal::short($context, $controler->issue, $controler->paper); ?>,
	title={<?php echo $controler->paper->title; ?>},
	volume={<?php echo $controler->issue->volume; ?>},
<?php if (!empty($controler->issue->number)) { ?>
	number={<?php echo $controler->issue->number; ?>},
<?php } ?>
	url={<?php echo $baseURL; ?>/article/view/<?php echo $controler->paper->id; ?>},
	DOI={<?php echo $controler->paper->doi; ?>},
	journal={<?php echo $controler->journal->name; ?>},
	author={<?php echo implode(' and ', $controler->authors); ?>},
	year={<?php echo date('Y', strtotime($controler->issue->published)); ?>},
	month={<?php echo date('M', strtotime($controler->issue->published)); ?>},
	pages={<?php echo $controler->paper->pages; ?>}
}