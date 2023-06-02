<?php $locale = Zord::getLocale('portal', $lang); ?>
<script src="/js/jquery.js" type="text/javascript"></script>
<script src="/journals/js/jquery.toc.js" type="text/javascript"></script>
<script src="/journals/js/display.js" type="text/javascript"></script>
<div class="sidebar <?php echo $context; ?>">
	<ul class="tabs">
		<li class="tab-link" data-tab="info"><?php echo $locale->download->info; ?></li>
		<li class="tab-link" data-tab="toc"><?php echo $locale->download->toc; ?></li>
		<li class="tab-link" data-tab="notes"><?php echo $locale->download->notes; ?></li>
		<li class="tab-link" data-tab="biblio"><?php echo $locale->download->biblio; ?></li>
	</ul>
	<div id="info" class="tab-content current" >
		<div style="font-weight:bold"><?php echo $paper['settings']['title']; ?></div>
<?php if (isset($paper['settings']['subtitle'])) { ?>
		<div style="font-weight:bold;font-style:italic;"><?php echo $paper['settings']['subtitle']; ?></div>
<?php } ?>
		<div style="font-size:15px">
<?php foreach ($paper['authors'] ?? [] as $author) { ?>
			<?php echo $author['name']; ?><?php echo !empty($author['settings']['affiliation']) ? ', '.$author['settings']['affiliation'] : ''; ?>
			<br />
<?php   if (!empty($author['email'])) { ?>
			<a href="mailto:<?php echo $author['email']; ?>"><?php echo $author['email']; ?></a>
			<br />
<?php   } ?>
<?php } ?>
			<br />
			<div style="font-size:88%">
				<?php echo $locale->download->topic; ?>: <?php echo $section; ?>
				<br />
				<?php echo $locale->download->$status; ?>
				<br />
				<a style="color:#606060" href="<?php echo $baseURL; ?>/info/license" target="_top"><?php echo $issue['copyright']; ?></a>
				<br />
			</div>
		</div>
	</div>
	<div id="toc" class="tab-content ">
		<ul id="tocContent"></ul>
	</div>
	<div id="notes" class="tab-content"></div>
	<div id="biblio" class="tab-content"></div>
</div>
<div class="main">
	<div class="mainContainer">
<?php echo $content ?>
	</div>
</div>