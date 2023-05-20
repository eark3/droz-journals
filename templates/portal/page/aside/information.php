		<div class="pkp_block block_information">
			<span class="title"><?php echo $locale->aside->infos->title; ?></span>
			<div class="content">
<?php foreach (Zord::value('portal', ['aside','infos','pages']) as $_page) { ?>
<?php   if (isset($journal['settings'][$_page])) { ?>
				<ul>
					<li>
						<a href="<?php echo $baseURL; ?>/info/<?php echo $_page; ?>"><?php echo $locale->aside->infos->pages->$_page; ?></a>
					</li>
				</ul>
<?php   } ?>
<?php } ?>
			</div>
		</div>
