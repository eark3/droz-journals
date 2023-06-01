<div class="choices">
<?php foreach ($config['amongst'] as $choice) { ?>
	<input id="<?php echo $name.'_'.$choice; ?>" class="setting choice" type="radio" name="update[<?php echo $name; ?>]" value="<?php echo $choice; ?>"<?php echo $settings[$type][$name] === $choice ? ' checked' : ''; ?>/>
	<label for="<?php echo $name.'_'.$choice; ?>"><?php echo $locale->settings->forms->$type->choices->$name->$choice; ?></label>
	<br/>
<?php } ?>
</div>
