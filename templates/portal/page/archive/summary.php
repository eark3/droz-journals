<div class="issue-summary media">
	<div class="media-left">
		<a class="fancybox" href="<?php echo $issue['cover']; ?>">
			<img class="img-responsive" src="<?php echo $issue['cover']; ?>" alt="<?php echo $controler->journal->name; ?> <?php echo $issue['serial']; ?>">
		</a>
	</div>
	<div class="media-body">
		<h2 class="media-heading">
			<a class="title" href="<?php echo $issue['link']; ?>"><?php echo $issue['title']; ?></a>
			<div class="series lead"><?php echo $issue['serial']; ?></div>
		</h2>
		<div class="description"><?php echo $issue['published']; ?></div>
	</div>
</div>