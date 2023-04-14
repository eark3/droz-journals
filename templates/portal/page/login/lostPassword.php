<div class="alert alert-info" role="alert">
	Pour des raisons de sécurité, ce système envoie un nouveau mot de passe
	par courriel aux utilisateurs inscrits au lieu de rappeler le mot de
	passe actuel.<br />
	<br />
	Indiquez votre adresse courriel ci-dessous pour réinitialiser
	votre mot de passe. Une confirmation sera envoyée par courriel.
</div>

<form class="pkp_form lost_password" id="lostPasswordForm"
	action="https://revues.droz.org/index.php/CFS/login/requestResetPassword"
	method="post">
	<input type="hidden" name="csrfToken" value="2076765f605764b0278a154d189e0b06">
	<div class="form-group">
		<label for="login-email"> Courriel de l'utilisateur inscrit </label>
		<input type="email" name="email" class="form-control" id="login-email" placeholder="Courriel de l'utilisateur inscrit" value="" maxlenght="32" required>
	</div>
	<div class="buttons">
		<button type="submit" class="btn btn-primary">Réinitialiser le mot de passe</button>
	</div>
</form>