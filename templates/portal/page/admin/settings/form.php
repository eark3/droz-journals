<form method="post" action="<?php echo $action; ?>">
	<input type="hidden" name="module" value="Admin"/>
	<input type="hidden" name="action" value="settings"/>
	<input type="hidden" name="type"   value="<?php echo $type; ?>"/>
	<input type="hidden" name="id"     value="<?php echo $id; ?>"/>
<?php echo $this->render('#'.$type); ?>
</form>