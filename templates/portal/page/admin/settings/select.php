<?php foreach ($choices as $type => $options) {?>
<label for="<?php echo $type; ?>"><?php echo Zord::resolve($locale->settings->select->list, ['type' => $type], $locale); ?></label>
<select id="<?php echo $type; ?>" name="<?php echo $type; ?>">
<?php   foreach ($options as $option) { ?>
	<option value="<?php echo $option['value']; ?>"<?php echo $option['selected'] ? ' selected' : ''; ?>><?php echo $option['label']; ?></option>
<?php   }?>
</select>
<?php   if ($current === $type && isset($next)) { ?>
<button id="next" data-type="<?php echo $next; ?>"><?php echo Zord::resolve($locale->settings->select->list, ['type' => $next], $locale); ?></button>
<?php   }?>
<?php } ?>
