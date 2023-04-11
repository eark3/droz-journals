		<div class="description pkp_block block_toc2">
			<span class="title"><?php echo $locale->aside->summary->title; ?></span>
			<div class="content">
<?php foreach ($issue['sections'] ?? [] as $id => $section) { ?>
				<ul>
					<li><a href="<?php echo $_SERVER['REQUEST_URI']; ?>#<?php echo $id; ?>"><?php echo $section['title']; ?></a></li>
				</ul>
<?php } ?>
			</div>
		</div>
