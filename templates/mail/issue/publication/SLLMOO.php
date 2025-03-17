<div class="salutation">Chère lectrice, cher lecteur,</div>
<div class="content">
	<p>Une nouvelle archive a été mise en ligne.</p>
	<p>Vous pouvez consulter les actes du colloque "<?php echo $issue['title'] ?? $issue['settings']['title'][$settings['locale']]['value']; ?>" (<?php echo $issue['year']; ?>) en cliquant sur le lien ci-dessous.</p>
	<p><a href="<?php echo $baseURL.'/issue/'.$short; ?>"><?php echo $baseURL.'/issue/view/'.$short; ?></a></p>
</div>
<div class="signature">La Rédaction.</div>
