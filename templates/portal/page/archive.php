<div id="issues" class="issues media-list">
<?php foreach ($models['issues'] as $issue) { ?>
<?php   $this->render('summary', ['issue' => $issue]); ?>
<?php } ?>
<?php $this->render('/portal/widget/pagination'); ?>
</div>
