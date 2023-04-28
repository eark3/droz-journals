<label for="name"><?php echo $locale->settings->forms->journal->name; ?></label>
<input id="name" class="setting" type="text" name="name" value="<?php echo $settings['name'] ?? ''; ?>"/>
<label for="rootDescription"><?php echo $locale->settings->forms->journal->rootDescription; ?></label>
<textarea id="rootDescription" class="setting html" name="rootDescription"><?php echo $settings['rootDescription'] ?? ''; ?></textarea>
<label for="description"><?php echo $locale->settings->forms->journal->description; ?></label>
<textarea id="description" class="setting html" name="description"><?php echo $settings['description'] ?? ''; ?></textarea>
<label for="homepageImage"><?php echo $locale->settings->forms->journal->homepageImage; ?></label>
<input id="homepageImage" class="setting image" type="file" name="homepageImage"/>
<div class="preview homepageImage">
	<img src="/public/journals/images/<?php echo $settings['acronym']; ?>/<?php echo $settings['homepageImage']['uploadName']; ?>" alt="<?php echo $settings['homepageImage']['altText'] ?? ''?>">
</div>
<label for="additionalHomeContent"><?php echo $locale->settings->forms->journal->additionalHomeContent; ?></label>
<textarea id="additionalHomeContent" class="setting html" name="additionalHomeContent"><?php echo $settings['additionalHomeContent'] ?? ''; ?></textarea>
<label for="pageFooter"><?php echo $locale->settings->forms->journal->pageFooter; ?></label>
<textarea id="pageFooter" class="setting html" name="pageFooter"><?php echo $settings['pageFooter'] ?? ''; ?></textarea>
<label for="mailingAddress"><?php echo $locale->settings->forms->journal->mailingAddress; ?></label>
<textarea id="mailingAddress" class="setting" name="mailingAddress" rows="6"><?php echo $settings['mailingAddress'] ?? ''; ?></textarea>
<label for="contactName"><?php echo $locale->settings->forms->journal->contactName; ?></label>
<input id="contactName" class="setting" type="text" name="contactName" value="<?php echo $settings['contactName'] ?? ''; ?>"/>
<label for="contactAffiliation"><?php echo $locale->settings->forms->journal->contactAffiliation; ?></label>
<input id="contactAffiliation" class="setting" type="text" name="contactAffiliation" value="<?php echo $settings['contactAffiliation'] ?? ''; ?>"/>
<label for="contactEmail"><?php echo $locale->settings->forms->journal->contactEmail; ?></label>
<input id="contactEmail" class="setting" type="text" name="contactEmail" value="<?php echo $settings['contactEmail'] ?? ''; ?>"/>
<label for="supportName"><?php echo $locale->settings->forms->journal->supportName; ?></label>
<input id="supportName" class="setting" type="text" name="supportName" value="<?php echo $settings['supportName'] ?? ''; ?>"/>
<label for="supportEmail"><?php echo $locale->settings->forms->journal->supportEmail; ?></label>
<input id="supportEmail" class="setting" type="text" name="supportEmail" value="<?php echo $settings['supportEmail'] ?? ''; ?>"/>
<label for="bannerLink"><?php echo $locale->settings->forms->journal->bannerLink; ?></label>
<input id="bannerLink" class="setting" type="text" name="bannerLink" value="<?php echo $settings['bannerLink'] ?? ''; ?>"/>
<label for="bannerImage"><?php echo $locale->settings->forms->journal->bannerImage; ?></label>
<input id="bannerImage" class="setting image" type="file" name="bannerImage"/>
<div class="preview bannerImage">
	<img src="<?php echo $settings['bannerImage'] ?? ''; ?>">
</div>
