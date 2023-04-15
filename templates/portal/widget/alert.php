<?php if (!empty($message)) { ?>
<?php   foreach (explode('|', $message) as $_message) { ?>	
<?php     $__message = explode('=', $_message); ?>	
	<div class="alert alert-<?php echo $__message[0]; ?>" role="alert">
		<?php echo $__message[1]; ?>
	</div>
<?php   } ?>
<?php } ?>
