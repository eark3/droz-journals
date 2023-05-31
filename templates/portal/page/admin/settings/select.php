<?php foreach ($choices as $type => $options) {?>
<label for="<?php echo $type; ?>"><?php echo Zord::resolve($locale->settings->select->list, ['type' => $type], $locale); ?></label>
<div class="select">
	<select id="<?php echo $type; ?>" name="<?php echo $type; ?>">
<?php   foreach ($options as $option) { ?>
		<option value="<?php echo $option['value']; ?>"<?php echo $option['selected'] ? ' selected' : ''; ?>><?php echo $option['label']; ?></option>
<?php   }?>
	</select>
<?php   if ($current !== $type) { ?>
	<button class="up" aria-label="<?php echo $locale->settings->select->edit; ?>"><i class="fa fa-fw fa-arrow-right"></i></button>
<?php   } else if (isset($next) ) { ?>
	<button id="next" data-type="<?php echo $next; ?>" aria-label="<?php echo Zord::resolve($locale->settings->select->list, ['type' => $next], $locale); ?>"><i class="fa fa-fw fa-arrow-down"></i></button>
<?php   } ?>
</div>
<?php   if (isset($next) && $current === $type) { ?>
<a href="<?php echo $url; ?>" target="_blank"><?php echo $locale->settings->select->view; ?></a>
<?php   } ?>
<?php } ?>
