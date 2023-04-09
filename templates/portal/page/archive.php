<div class="issues media-list">
<?php foreach ($issues as $issue) { ?>
<?php   $this->render('summary', ['issue' => $issue]); ?>
<?php } ?>
</div>