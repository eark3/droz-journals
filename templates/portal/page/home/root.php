<div class="pkp_structure_content container">
	<main class="pkp_structure_main col-xs-12 col-sm-10 col-md-8" role="main">
        <div id="main-site" class="page_index_site">
        	<div class="about_site">Portail de revues de la Librairie Droz</div>
        	<div class="journals">
        		<div class="page-header">
        			<h2>revues.droz.org</h2>
        		</div>
        		<ul class="media-list">
<?php foreach ($journals as $journal) { ?>
        			<li class="media panel panel-default" style="padding: 15px;">
 						<div class="media-left">
							<a href="<?php echo $journal['path']; ?>">
								<img class="media-object" src="<?php echo $journal['image']; ?>">
							</a>
						</div>
						<div class="media-body">
        					<h3 class="media-heading">
        						<a href="<?php echo $journal['path']; ?>" rel="bookmark"><?php echo $journal['name']; ?></a>
        					</h3>
        					<div class="description">
        						<?php echo $journal['description']; ?>
        					</div>
        					<ul class="nav nav-pills">
        						<li class="view">
        							<a href="<?php echo $journal['path']; ?>">Voir la revue</a></li>
        						<li class="current">
        							<a href="<?php echo $journal['path']; ?>/issue/current">Numéro	courant </a></li>
        					</ul>
        				</div>
        			</li>
<?php } ?>
        		</ul>
        	</div>
        </div>
    </main>
</div>