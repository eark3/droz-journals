<footer class="footer" role="contentinfo">
	<div class="container">
		<div class="row">
			<div class="col-md-4">
				<?php echo $journal['settings']['pageFooter'] ?? ''; ?>
<?php if (!empty($journal['settings']['onlineIssn'])) { ?>
				<?php echo $locale->footer->issn->online; ?> : <?php echo $journal['settings']['onlineIssn']; ?>
				<br/>
<?php } ?>
<?php if (!empty($journal['settings']['printIssn'])) { ?>
				<?php echo $locale->footer->issn->print; ?> : <?php echo $journal['settings']['printIssn']; ?>
<?php } ?>
			</div>
			<div class="col-md-4">
<?php foreach (Zord::value('portal', ['menu','footer']) as $_type => $_pages) { ?>
<?php   foreach ($_pages as $_page) { ?>
<?php     if ($_type !== 'info' || !empty($journal['settings'][$_page])) { ?>
				<p>
					<a href="<?php echo $baseURL; ?>/<?php echo $_type; ?>/<?php echo $_page; ?>"><?php echo $locale->pages->$_page; ?></a>
				</p>
<?php     }?>
<?php   } ?>
<?php } ?>
			</div>
			<div class="col-md-4">
				<img src="/journals/img/logo-droz-77x80.png" />
				<p>
					Librairie Droz S.A.<br/>
					31 rue Vautier<br/>
					CH-1227 Carouge (Genève)<br/>
					Téléphone : +41 22 346 66 66<br/ >
					<a target="_blank" href="https://www.droz.org">https://www.droz.org</a>
				</p>
			</div>
		</div>
	</div>
</footer>