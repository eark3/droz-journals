		<div class="pkp_block block_search">
			<span class="title"><?php echo $locale->aside->search->title; ?></span>
			<div class="content">
				<ul>
					<li>
						<form method="post" id="search-form" class="search-form" action="<?php echo $baseURL; ?>/search" role="search">
							<p>
								<input type="text" id="query" name="query" value="<?php echo str_replace('"', '&quot;', $filters['query'] ?? ''); ?>" class="query input-sm form-control" placeholder="<?php echo $locale->aside->search->contains; ?>">
							</p>
							<p>
								<input type="text" name="authors" value="<?php echo $filters['authors'] ?? ''; ?>" class="query input-sm form-control" placeholder="<?php echo $locale->aside->search->authors; ?>">
							</p>
							<p>
								<input type="submit" value="<?php echo $locale->aside->search->submit; ?>" class="btn btn-sm btn-default"/>
							</p>
						</form>
					</li>
				</ul>
			</div>
		</div>
