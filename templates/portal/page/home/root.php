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
<?php   $this->render('journal', ['journal' => $journal]); ?>
<?php } ?>
        		</ul>
        	</div>
        </div>
    </main>
</div>