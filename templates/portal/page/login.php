<form class="pkp_form login" id="login" method="post" action="<?php echo $baseURL; ?>/login/signIn">
<?php if (!empty($success)) { ?>
	<input type="hidden" name="success" value="<?php echo $success; ?>"/>
<?php } ?>
<?php if (!empty($failure)) { ?>
	<input type="hidden" name="failure" value="<?php echo $failure; ?>"/>
<?php } ?>
<?php if (!empty($token)) { ?>
	<input type="hidden" name="token" value="<?php echo $token; ?>"/>
<?php } ?>
<?php $this->render('/portal/widget/alert'); ?>
	<div class="form-group">
		<label for="login-username"><?php echo $locale->login->username; ?></label>
		<input type="text" name="username" class="form-control" id="login-username" placeholder="<?php echo $locale->login->username; ?>" value="" maxlenght="32" required>
	</div>
	<div class="form-group">
		<label for="login-password"><?php echo $locale->login->password; ?></label>
		<input type="password" name="password" class="form-control" id="login-password" placeholder="<?php echo $locale->login->password; ?>" password="true" maxlength="32" required="$passwordRequired">
	</div>
	<div class="form-group">
		<a href="<?php echo $baseURL; ?>/page/login/lostPassword"><?php echo $locale->login->forgot; ?></a>
	</div>
	<div class="checkbox">
		<label>
			<input type="checkbox" name="remember" id="remember" value="1" checked="$remember">
			<?php echo $locale->login->remember; ?>
		</label>
	</div>
	<div class="buttons">
		<button type="submit" class="btn btn-primary"><?php echo $locale->login->signIn; ?></button>
	</div>
</form>