<?php $locale = Zord::getLocale('portal', $lang); ?>
<script src="/js/jquery.js" type="text/javascript"></script>
<script src="/journals/js/jquery.toc.js" type="text/javascript"></script>
<script src="/journals/js/display.js" type="text/javascript"></script>
<div class="sidebar CFS">
	<ul class="tabs">
		<li class="tab-link current" data-tab="tab-0" style="margin-right:-4px"><?php echo $locale->download->info; ?></li>
		<li class="tab-link" data-tab="tab-1" style="display:inline-block"><?php echo $locale->download->toc; ?></li>
		<li class="tab-link" data-tab="tab-2" style="display:inline-block"><?php echo $locale->download->notes; ?></li>
		<li class="tab-link" data-tab="tab-3" style="display:inline-block"><?php echo $locale->download->biblio; ?></li>
	</ul>
	<div id="tab-0" class="tab-content current" >
		<div style="font-weight:bold"><?php echo $paper['title']; ?></div>
		<div style="font-weight:bold;font-style:italic;"></div>
		<div style="font-size:15px">
<?php foreach ($paper['authors'] ?? [] as $author) { ?>
			<?php echo $author; ?>
			<br />
<?php } ?>
<!-- 
			<a href="mailto:jacopo.dalonzo@gmail.com">jacopo.dalonzo@gmail.com</a>
			<br />
-->
			<br />
			<br />
			<div style="font-size:88%">
				<?php echo $locale->download->topic; ?>: <?php echo $section; ?>
				<br />
				<?php echo $locale->download->$status; ?>
				<br />
				<a style="color:#606060" href="<?php echo $baseURL; ?>/licence"><?php echo $issue['copyright']; ?></a>
				<br />
			</div>
		</div>
	</div>
	<div id="tab-1" class="tab-content ">
		<ul id="toc"></ul>
	</div>
	<div id="tab-2" class="tab-content"></div>
	<div id="tab-3" class="tab-content"></div>
</div>
<div class="main">
	<div class="mainContainer">
<?php echo $content ?>
	</div>
</div>