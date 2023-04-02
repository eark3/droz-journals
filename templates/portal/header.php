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
</header>