			<ul class="nav navbar-nav navbar-right">
				<li class="dropdown">
					<a href="<?php echo $baseURL.'/'.($user->isConnected() ? 'dis' : '').'connect'; ?>" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
<?php if ($user->isConnected()) { ?>
						<?php echo $user->name; ?>
<?php } else { ?>
						<span class="glyphicon glyphicon-user" aria-hidden="true"></span>
<?php } ?>
						&nbsp;<span class="caret"></span>
					</a>
					<ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
						<li>
<?php if ($user->isConnected()) { ?>
							<a href="<?php echo $baseURL; ?>/login/signOut"><?php echo $locale->login->signOut; ?></a>
<?php } else { ?>
							<a href="<?php echo $baseURL; ?>/page/login"><?php echo $locale->login->signIn; ?></a>
<?php } ?>
						</li>
					</ul>
				</li>
			</ul>
