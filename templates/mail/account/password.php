		<span><?php echo Zord::substitute(Zord::getLocale('account', $lang)->mail->reset_password->new, $models); ?></span>
<?php $this->render('#noreply'); ?>	