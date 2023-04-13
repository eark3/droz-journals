					<li class="media panel panel-default" style="padding: 15px;">
 						<div class="media-left">
							<a href="<?php echo $journal['path']; ?>">
								<img class="media-object" src="<?php echo $journal['thumbnail']; ?>">
							</a>
						</div>
						<div class="media-body">
							<h3 class="media-heading">
								<a href="<?php echo $journal['path']; ?>" rel="bookmark"><?php echo $journal['settings']['name']; ?></a>
							</h3>
							<div class="description">
								<?php echo $journal['settings']['description']; ?>
							</div>
							<ul class="nav nav-pills">
								<li class="view">
									<a href="<?php echo $journal['path']; ?>"><?php echo $locale->root->journal; ?></a></li>
								<li class="current">
									<a href="<?php echo $journal['path']; ?>/issue/current"><?php echo $locale->root->current; ?></a></li>
							</ul>
						</div>
					</li>
