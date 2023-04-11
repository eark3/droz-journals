		<div class="pkp_block block_search">
			<span class="title"><?php echo $locale->aside->search->title; ?></span>
			<div class="content">
				<ul>
					<li>
						<form method="post" id="search-form" class="search-form" action="/CFS/search/search" role="search">
							<input type="hidden" name="csrfToken" value="c786c8295b80c3ab3777b26098fb3163">
							<p>
								<label class="sr-only" for="query"><?php echo $locale->aside->search->contains; ?></label>
								<input type="text" id="query" name="query" value="" class="query input-sm form-control" placeholder="Rechercher">
							</p>
							<p>
								<label class="sr-only" for="query"><?php echo $locale->aside->search->authors; ?></label>
								<input type="text" name="authors" value="" class="query input-sm form-control" placeholder="Auteurs" for="authors">
							</p>
							<p>
								<input type="submit" value="<?php echo $locale->aside->search->submit; ?>" class="btn btn-sm btn-default"/>
							</p>
						</form>
					</li>
				</ul>
			</div>
		</div>
