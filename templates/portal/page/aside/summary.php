		<div class="description pkp_block block_toc2">
			<span class="title"><?php echo $locale->aside->summary->title; ?></span>
			<div class="content">
<?php foreach ($issue['sections'] ?? [] as $section) { ?>
				<ul>
					<li class="<?php echo empty($section['parent']) ? 'top' : 'sub'; ?>"><a class="summary" href="<?php echo $_SERVER['REQUEST_URI']; ?>#<?php echo $section['name']; ?>"><?php echo $section['settings']['title']; ?></a></li>
				</ul>
<?php } ?>
			</div>
		</div>
