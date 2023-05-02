			<ul class="nav navbar-nav navbar-right">
				<li class="dropdown">
					<a href="<?php echo $baseURL.'/'.($user->isConnected() ? 'dis' : '').'connect'; ?>" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
<?php if ($user->isInstitution()) { ?>
						<?php echo $user->institution; ?>
<?php } else { ?>
<?php   if ($user->isConnected()) { ?>
						<?php echo $user->name; ?>
<?php   } else { ?>
						<span class="glyphicon glyphicon-user" aria-hidden="true"></span>
<?php   } ?>
						&nbsp;<span class="caret"></span>
<?php } ?>
					</a>
<?php if (!$user->isInstitution()) { ?>
					<ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
						<li>
<?php   if ($user->isConnected()) { ?>
							<a href="<?php echo $baseURL; ?>/login/signOut"><?php echo $locale->login->signOut; ?></a>
<?php   } else { ?>
							<a href="<?php echo $baseURL; ?>/page/login"><?php echo $locale->login->signIn; ?></a>
<?php   } ?>
<?php   if ($user->hasRole('admin', $context)) { ?>
							<a href="<?php echo $baseURL; ?>/admin"><?php echo $locale->admin; ?></a>
<?php   }?>
						</li>
					</ul>
<?php } ?>
				</li>
				<li class="dropdown">
					<a href="/" class="dropdown-toggle" data-toggle="dropdown"><?php echo $locale->language; ?>&nbsp;<b class="caret"></b></a>
	 				<ul class="dropdown-menu dropdown-menu-right">
<?php foreach (Zord::value('portal', 'lang') as $_lang) { ?>
						<li><a href="<?php echo $baseURL; ?>/Portal/last?lang=<?php echo $_lang; ?>"><?php echo Zord::getLocale('portal', $lang)->lang->$_lang; ?></a></li>
<?php } ?>
					</ul>
				</li>
			</ul>
