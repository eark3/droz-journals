<select class="chosen-select-standard" name="update[<?php echo $name; ?>][]"<?php echo ($config['multiple'] ?? false) ? ' multiple' : ''; ?> data-placeholder="<?php echo $config['holder']; ?>">
<?php foreach ($config['amongst'] as $choice) { ?>
	<option value="<?php echo $choice; ?>"<?php echo in_array($choice, $settings[$type][$name] ?? []) ? ' selected' : ''; ?>><?php echo $locale->settings->forms->$type->choices->$name->$choice; ?></option>
<?php } ?>
</div>
