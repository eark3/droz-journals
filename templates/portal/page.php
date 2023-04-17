<div class="pkp_structure_content container <?php echo $page ?>">
	<main class="pkp_structure_main col-xs-12 col-sm-10 col-md-8" role="main">
<?php if (isset($ariadne)) { ?>
<?php   $this->render('ariadne'); ?>
<?php } ?>
<?php $this->render($page); ?>
	</main>
<?php if ($context !== 'root' && $page !== 'admin') { ?>
	<aside id="sidebar" class="pkp_structure_sidebar left col-xs-12 col-sm-4" role="complementary" aria-label="Barre de navigation">
<?php foreach ($layout['aside'] as $component) { ?>
<?php   $this->render('aside/'.$component); ?>
<?php } ?>
	</aside>
<?php } ?>
</div>