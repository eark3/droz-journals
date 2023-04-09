<a class="galley-link<?php echo $type === 'shop' ? '-buy-droz' : ''; ?> btn btn-sm btn-default file restricted" role="button" href="<?php echo $path; ?>">
	<?php echo $locale->galleys->$type; ?>
<?php if ($type === 'shop') { ?>
	&nbsp;<img src="/journals/img/html-galley-btn-arrow-8x8.svg" class="html-galley-btn-arrow">
<?php } ?>
</a>
