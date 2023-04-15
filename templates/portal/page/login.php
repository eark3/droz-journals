<form class="pkp_form login" id="login" method="post" action="<?php echo $baseURL; ?>/login/signIn">
	<input type="hidden" name="csrfToken" value="2076765f605764b0278a154d189e0b06">
	<input type="hidden" name="source" value="" />
	<div class="form-group">
		<label for="login-username">Nom d'utilisateur</label>
		<input type="text" name="username" class="form-control" id="login-username" placeholder="Nom d'utilisateur" value="" maxlenght="32" required>
	</div>
	<div class="form-group">
		<label for="login-password">Mot de passe</label>
		<input type="password" name="password" class="form-control" id="login-password" placeholder="Mot de passe" password="true" maxlength="32" required="$passwordRequired">
	</div>
	<div class="form-group">
		<a href="<?php echo $baseURL; ?>/page/login/lostPassword">Vous avez oublié votre mot de passe ?</a>
	</div>
	<div class="checkbox">
		<label>
			<input type="checkbox" name="remember" id="remember" value="1" checked="$remember">
			Mémoriser mon nom d'usager et mon mot de passe
		</label>
	</div>
	<div class="buttons">
		<button type="submit" class="btn btn-primary">Se connecter</button>
	</div>
</form>