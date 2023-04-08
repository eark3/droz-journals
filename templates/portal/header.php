<header	class="navbar navbar-expand-lg navbar-light bg-light navbar-fixed-top" id="headerNavigationContainer" role="banner">
	<div class="container-fluid">
		<div class="row" id="overNavbar">
			<div class="col-xs-6 col-sm-8">
				<a href="/" class=""> 
					<span class="SabonLTStd-Roman">
						<span class="drozLightGrey">LIBRAIRIE</span><span class="drozDarkGrey">DROZ</span>
					</span>
				</a>
			</div>
			<div class="col-xs-6 col-sm-4 pull-right">
				<ul class="nav pull-right">
					<li class="dropdown">
						<a href="/" class="dropdown-toggle" data-toggle="dropdown">revues.droz.org<b class="caret"></b></a>
	 					<ul class="dropdown-menu dropdown-menu-right">
<?php foreach ($journals as $_journal) { ?>
							<li><a href="<?php echo $_journal['path']; ?>"><?php echo $_journal['name']; ?></a></li>
<?php } ?>
						</ul>
					</li>
				</ul>
			</div>
		</div>
	</div>
	<div id="navbarContainer" class="container-fluid">
		<div class="navbar-header">
			<h1 class="site-name">
				<div class="navbar-brand">
					<a href="/<?php echo $context; ?>"><?php echo $controler->journal->name; ?></a>
				</div>
			</h1>
		</div>
		<nav id="nav-menu" class="navbar-collapse collapse" aria-label="Site de navigation">
			<ul id="main-navigation" class="nav navbar-nav">
				<li><a href="<?php echo $baseURL; ?>/issue/current"><?php echo $locale->issue->last; ?></a></li>
				<li><a href="<?php echo $baseURL; ?>/issue/archive"><?php echo $locale->issue->previous; ?></a></li>
				<li class="dropdown">
					<a href="<?php echo $baseURL; ?>/about" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?php echo $locale->about->short; ?><span class="caret"></span></a>
					<ul class="dropdown-menu">
						<li><a href="<?php echo $baseURL; ?>/about/journal"><?php echo $locale->about->long; ?></a></li>
						<li><a href="<?php echo $baseURL; ?>/about/comite-editorial"><?php echo $locale->board; ?></a></li>
						<li><a href="<?php echo $baseURL; ?>/about/contact"><?php echo $locale->contact; ?></a></li>
						<li><a href="<?php echo $baseURL; ?>/about/acces"><?php echo $locale->policy; ?></a></li>
						<li><a href="<?php echo $baseURL; ?>/about/licence"><?php echo $locale->license; ?></a></li>
					</ul>	
				<li><a href="<?php echo $baseURL; ?>/subscription"><?php echo $locale->subscription; ?></a></li>
			</ul>
			<ul class="nav navbar-nav navbar-right">
				<li class="dropdown">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
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
							<a href="<?php echo $baseURL; ?>/disconnect"><?php echo $locale->logout; ?></a>
<?php } else { ?>
							<a href="<?php echo $baseURL; ?>/connect"><?php echo $locale->login; ?></a>
<?php } ?>
						</li>
					</ul>
				</li>
			</ul>
		</nav>
	</div>
</header>