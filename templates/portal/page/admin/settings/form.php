<form method="POST" action="<?php echo $baseURL; ?>" enctype="multipart/form-data">
	<input type="hidden" name="module" value="Admin"/>
	<input type="hidden" name="action" value="settings"/>
	<input type="hidden" name="type"   value="<?php echo $type; ?>"/>
	<input type="hidden" name="id"     value="<?php echo $id; ?>"/>
<?php foreach ($hidden as $name => $value) { ?>
	<input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>"/>
<?php } ?>
<?php foreach (Zord::value('admin', ['settings','fields',$type]) ?? [] as $name => $config) { ?>
<?php   if (isset($locale->settings->forms->$type->$name)) { ?>
<label for="<?php echo $name; ?>"><?php echo $locale->settings->forms->$type->$name; ?></label>
<?php     echo $this->render($config['template'], ['type' => $type, 'name' => $name, 'config' => $config, 'settings' => $settings]); ?>
<?php   } ?>
<?php } ?>
	<input type="submit" name="submit" value="<?php echo $locale->settings->forms->submit; ?>"/>
</form>