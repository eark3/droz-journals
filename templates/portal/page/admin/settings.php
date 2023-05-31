<div id=settings>
	<div id="select">
<?php if (isset($select)) { ?>
<?php   $this->render('select', $select); ?>
<?php } ?>
	</div>
	<div id="form">
<?php if (isset($form)) { ?>
<?php   $this->render('form', $form); ?>
<?php } ?>
	</div>
</div>