<div id="locale">
<?php foreach (Zord::getLocale('portal')->lang as $key => $label) { ?>
	<span class="lang<?php echo $key === $_lang ? ' selected' : ''; ?>" data-type="<?php echo $type; ?>" data-id="<?php echo $id; ?>" data-lang="<?php echo $key; ?>"><?php echo $label; ?></span>
<?php } ?>
</div>
<form method="POST" action="<?php echo $baseURL; ?>" enctype="multipart/form-data">
	<input type="hidden" name="module" value="Admin"/>
	<input type="hidden" name="action" value="settings"/>
	<input type="hidden" name="type"   value="<?php echo $type; ?>"/>
	<input type="hidden" name="id"     value="<?php echo $id; ?>"/>
	<input type="hidden" name="_lang"  value="<?php echo $_lang; ?>"/>
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