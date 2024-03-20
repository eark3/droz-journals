<?php $this->render('/portal/widget/alert'); ?>
<?php if ($needform ?? true) { ?>
<form class="pkp_form change_password" id="changePasswordForm" action="<?php echo $baseURL; ?>" method="post">
	<input type="hidden" name="module" value="Account" />
	<input type="hidden" name="action" value="password" />
	<input type="hidden" name="token" value="<?php echo $token; ?>" />
	<input type="hidden" name="mode" value="choose" />
	<div class="form-group">
		<label for="password"><?php echo $locale->login->password; ?></label>
		<input type="password" name="password" class="form-control" id="password" placeholder="<?php echo $locale->login->password; ?>" value="" maxlenght="32" required>
	</div>
	<div class="form-group">
		<label for="confirm"><?php echo $locale->login->confirm; ?></label>
		<input type="password" name="confirm" class="form-control" id="confirm" placeholder="<?php echo $locale->login->confirm; ?>" value="" maxlenght="32" required>
	</div>
	<div class="buttons">
		<button type="submit" class="btn btn-primary"><?php echo $locale->login->change; ?></button>
	</div>
</form>
<?php } ?>
