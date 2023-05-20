<div class="issue-summary media">
	<div class="media-left">
		<a class="fancybox" href="<?php echo $issue['cover']; ?>">
			<img class="img-responsive" src="<?php echo $issue['cover']; ?>" alt="<?php echo $controler->journal->name; ?> <?php echo $issue['serial']; ?>">
		</a>
	</div>
	<div class="media-body">
		<h2 class="media-heading">
			<a class="title" href="<?php echo $issue['link']; ?>"><?php echo !empty($issue['settings']['title']) ? $issue['settings']['title'] : $issue['serial']; ?></a>
<?php if (!empty($issue['settings']['title'])) { ?>
			<div class="series lead"><?php echo $issue['serial']; ?></div>
<?php } ?>
		</h2>
		<div class="description"><?php echo $issue['published']; ?></div>
	</div>
</div>