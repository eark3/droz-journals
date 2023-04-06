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
<?php foreach ($journals as $journal) { ?>
							<li><a href="<?php echo $journal['path']; ?>"><?php echo $journal['name']; ?></a></li>
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
    				<a href="/CFS">Cahiers Ferdinand de Saussure</a>
    			</div>
    		</h1>
    	</div>
    	<nav id="nav-menu" class="navbar-collapse collapse" aria-label="Site de navigation">
    		<ul id="main-navigation" class="nav navbar-nav">
    			<li><a href="/CFS/issue/current">Dernier numéro</a></li>
    			<li><a href="/CFS/issue/archive">Numéros précédents</a></li>
    			<li class="dropdown">
    				<a href="/CFS/about" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"> À propos <span class="caret"></span></a>
    				<ul class="dropdown-menu">
    					<li><a href="/CFS/about">À propos de la revue </a></li>
    					<li><a href="/CFS/comite-editorial">Comité éditorial</a></li>
    					<li><a href="/CFS/about/contact">Contact</a></li>
    					<li><a href="/CFS/acces">Politique d'accès</a></li>
    					<li><a href="/CFS/licence">Mentions légales</a></li>
    				</ul>	
    			<li><a href="/CFS/subscription">Abonnement</a></li>
    		</ul>
    		<ul class="nav navbar-nav navbar-right">
    			<li class="dropdown">
    				<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-user" aria-hidden="true"></span> <span class="caret"></span></a>
    				<ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
    					<li><a href="/CFS/login">Se connecter</a></li>
    				</ul>
    			</li>
    		</ul>
    	</nav>
    </div>
</header>