<label for="journal"><?php echo $locale->settings->select->journal; ?></label>
<select id="journal" name="journal">
<?php foreach ((new JournalEntity())->retrieveAll() as $journal) { ?>
	<option value="<?php echo $journal->context; ?>"><?php echo $journal->context; ?></option>
<?php }?>
</select>
<label for="issue"><?php echo $locale->settings->select->issue; ?></label>
<select id="issue" name="issue"></select>
<label for="section"><?php echo $locale->settings->select->section; ?></label>
<select id="section" name="section"></select>
<label for="paper"><?php echo $locale->settings->select->paper; ?></label>
<select id="paper" name="paper"></select>
<label for="author"><?php echo $locale->settings->select->author; ?></label>
<select id="author" name="author"></select>