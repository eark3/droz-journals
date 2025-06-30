<header	class="navbar navbar-expand-lg navbar-light bg-light navbar-fixed-top" id="headerNavigationContainer" role="banner">
	<div class="container-fluid">
		<div class="row" id="overNavbar">
			<div class="col-xs-6 col-sm-8">
				<a href="/" class=""> 
					<span class="drozLogoName">
						<span class="drozLightGrey">LIBRAIRIE</span><span class="drozDarkGrey">DROZ</span>
					</span>
				</a>
			</div>
			<div class="col-xs-6 col-sm-4 pull-right">
				<ul class="nav pull-right">
					<li class="dropdown">
						<a href="/" class="dropdown-toggle" data-toggle="dropdown"><?php echo $host; ?><b class="caret"></b></a>
	 					<ul class="dropdown-menu dropdown-menu-right">
<?php foreach ($journals as $_journal) { ?>
							<li><a href="<?php echo Zord::getContextURL($_journal['context']) ?>"><?php echo JournalsUtils::flat($_journal['settings']['name'] ?? Zord::value('context', [$_journal['context'],'title',$_journal['locale']])); ?></a></li>
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
					<a href="<?php echo Zord::getContextURL($journal['context']); ?>"><?php echo $journal['settings']['name'] ?? Zord::value('context', [$journal['context'],'title',$journal['locale']]); ?></a>
				</div>
			</h1>
		</div>
		<nav id="nav-menu" class="navbar-collapse collapse" aria-label="Site de navigation">
			<ul id="main-navigation" class="nav navbar-nav">
				<li class="current"><a href="<?php echo $baseURL; ?>/issue/current"><?php echo $locale->issue->last; ?></a></li>
				<li class="archive"><a href="<?php echo $baseURL; ?>/issue/archive"><?php echo $locale->issue->previous; ?></a></li>
				<li class="about dropdown">
					<a href="<?php echo $baseURL; ?>/info/about" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?php echo $locale->about; ?><span class="caret"></span></a>
					<ul class="dropdown-menu">
<?php foreach (Zord::value('portal', ['menu','header']) as $_type => $_pages) { ?>
<?php   foreach ($_pages as $_page) { ?>
<?php     if ($_type !== 'info' || !empty($journal['settings'][$_page])) { ?>
						<li class="<?php echo $_page; ?>"><a href="<?php echo $baseURL; ?>/<?php echo $_type; ?>/<?php echo $_page; ?>"><?php echo $locale->pages->$_page; ?></a></li>
<?php     }?>
<?php   } ?>
<?php } ?>
					</ul>	
				<li class="subscription"><a href="<?php echo $baseURL; ?>/info/subscription"><?php echo $locale->pages->subscription; ?></a></li>
<?php if (!empty($journal['settings']['notice'])) { ?>
				<li class="notice"><a href="<?php echo $baseURL; ?>/info/notice"><?php echo $locale->pages->notice; ?></a></li>
<?php } ?>
			</ul>
<?php $this->render('/portal/widget/user'); ?>
		</nav>
	</div>
</header>