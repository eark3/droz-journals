<div class="alert alert-info" role="alert">
	<?php echo $locale->login->lost; ?>
</div>

<form class="pkp_form lost_password" id="lostPasswordForm" action="<?php echo $baseURL; ?>/login/requestResetPassword" method="post">
<?php $this->render('/portal/widget/alert'); ?>
	<div class="form-group">
		<label for="login-email"><?php echo $locale->login->email; ?></label>
		<input type="email" name="email" class="form-control" id="login-email" placeholder="Courriel de l'utilisateur inscrit" value="" maxlenght="32" required>
	</div>
	<div class="buttons">
		<button type="submit" class="btn btn-primary"><?php echo $locale->login->reset; ?></button>
	</div>
</form>