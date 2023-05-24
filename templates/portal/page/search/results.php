<?php if (!empty($models['papers'])) { ?>
		<div id="results">
<?php   $this->render('/portal/widget/pagination'); ?>
<?php foreach ($models['papers'] ?? [] as $paper) { ?>
<?php   $this->render('/portal/widget/paper', $paper); ?>
<?php } ?>
<?php   $this->render('/portal/widget/pagination'); ?>
		</div>
<?php }?>
