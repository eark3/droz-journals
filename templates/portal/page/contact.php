<div class="page page_contact">
	<div class="contact_section">
		<div class="address">
			<?php echo str_replace("\n", "<br/>", $journal['settings']['mailingAddress']); ?>
		</div>
		<div class="contact primary">
			<h1 style="margin-top: 20px;"><?php echo $locale->contact->main; ?></h1>
			<div class="name"><?php echo $journal['settings']['contactName']; ?></div>
<?php if (isset($journal['settings']['contactAffiliation'])) { ?>
			<div class="affiliation"><?php echo $journal['settings']['contactAffiliation']; ?></div>
<?php } ?>
			<div class="email">
				<a href="mailto:<?php echo $journal['settings']['contactEmail']; ?>"><?php echo $journal['settings']['contactEmail']; ?></a>
			</div>
		</div>
<?php if (!empty($journal['settings']['supportName']) && !empty($journal['settings']['supportEmail'])) { ?>
		<div class="contact support">
			<h1 style="margin-top: 20px;"><?php echo $locale->contact->support; ?></h1>
			<div class="name"><?php echo $journal['settings']['supportName']; ?></div>
			<div class="email">
				<a href="mailto:<?php echo $journal['settings']['supportEmail']; ?>"><?php echo $journal['settings']['supportEmail']; ?></a>
			</div>
		</div>
<?php } ?>
	</div>
</div>