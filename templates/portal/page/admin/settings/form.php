<form method="post" action="<?php echo $action; ?>">
	<input type="hidden" name="module" value="Admin"/>
	<input type="hidden" name="action" value="settings"/>
	<input type="hidden" name="type"   value="<?php echo $type; ?>"/>
	<input type="hidden" name="id"     value="<?php echo $id; ?>"/>
<?php foreach (Zord::value('admin', ['settings','fields',$type]) ?? [] as $name => $config) { ?>
<?php   if (isset($locale->settings->forms->$type->$name)) { ?>
<label for="<?php echo $name; ?>"><?php echo $locale->settings->forms->$type->$name; ?></label>
<?php     echo $this->render($config['template'], ['type' => $type, 'name' => $name, 'config' => $config, 'settings' => $settings]); ?>
<?php   } ?>
<?php } ?>
</form>