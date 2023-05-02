<input id="<?php echo $name; ?>" class="setting image" type="file" name="<?php echo $name; ?>"/>
<img src="/img/wait.gif" style="display: none;"/>
<div class="preview <?php echo $name; ?>">
	<a class="fancybox" href="<?php echo Zord::substitute($config['src'], $settings); ?>">
		<img src="<?php echo Zord::substitute($config['src'] ?? '', $settings); ?>" alt="<?php echo Zord::substitute($config['alt'] ?? '', $settings); ?>">
	</a>
</div>
