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
							<a href="<?php echo $baseURL; ?>/login"><?php echo $locale->login->signIn; ?></a>
<?php   } ?>
<?php   if ($user->hasRole('admin', $context)) { ?>
							<a href="<?php echo $baseURL; ?>/admin"><?php echo $locale->admin->menu; ?></a>
<?php     if (isset($edit)) { ?>
							<a href="<?php echo $baseURL; ?>/admin<?php echo Zord::substitute(Zord::value('portal', 'edit'), $edit); ?>" target="_blank"><?php echo $locale->edit; ?> <?php echo $edit['short']; ?></a>
<?php     } ?>
<?php     if (isset($export)) { ?>
							<a href="<?php echo $baseURL; ?>/export/<?php echo $export['short']; ?>"><?php echo $locale->export; ?> <?php echo $export['short']; ?></a>
<?php     } ?>
<?php   } ?>
						</li>
					</ul>
<?php } ?>
				</li>
<?php if (count($langs ?? []) > 1) { ?>
				<li class="dropdown">
					<a href="/" class="dropdown-toggle" data-toggle="dropdown"><img src="/journals/img/<?php echo $lang ?>.jpg" />&nbsp;<b class="caret"></b></a>
					<ul class="dropdown-menu dropdown-menu-right">
<?php   foreach ($langs as $_lang) { ?>
						<li><a href="<?php echo $pathURL; ?>?lang=<?php echo $_lang; ?>"><img src="/journals/img/<?php echo $_lang ?>.jpg" /> <span style="margin-left: 1em;"><?php echo Zord::getLocale('portal', $_lang)->lang->$_lang; ?></span></a></li>
<?php   } ?>
					</ul>
				</li>
<?php } ?>
			</ul>
