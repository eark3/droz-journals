					<li class="media panel panel-default" style="padding: 15px;">
 						<div class="media-left">
							<a href="/<?php echo $journal['context']; ?>">
								<img class="media-object" src="/public/journals/images/<?php echo $journal['context']; ?>/<?php echo $journal['settings']['homepageImage']['uploadName']; ?>">
							</a>
						</div>
						<div class="media-body">
							<h3 class="media-heading">
								<a href="/<?php echo $journal['context']; ?>" rel="bookmark"><?php echo $journal['settings']['name']; ?></a>
							</h3>
							<div class="description">
								<?php echo $journal['settings']['rootDescription']; ?>
							</div>
							<ul class="nav nav-pills">
								<li class="view">
									<a href="/<?php echo $journal['context']; ?>"><?php echo $locale->root->journal; ?></a></li>
								<li class="current">
									<a href="/<?php echo $journal['context']; ?>/issue/current"><?php echo $locale->root->current; ?></a></li>
							</ul>
						</div>
					</li>
