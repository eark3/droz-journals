<nav class="cmp_breadcrumbs" role="navigation" aria-label="<?php echo $locale->ariadne->aria; ?>">
	<ol class="breadcrumb">
<?php foreach ($ariadne as $label => $link) { ?>
		<li class="<?php echo $label === 'active' ? 'active' : '' ; ?>">
<?php   if ($label !== 'active') { ?>
			<a href="<?php echo is_array($link) ? $link[1] : $link; ?>">
<?php   } ?>
			<?php echo is_array($link) ? $link[0] : ($locale->ariadne->$label ?? ($locale->ariadne->$link ?? $link)); ?>
<?php   if ($label !== 'active') { ?>
			</a>
<?php   }?>
		</li>
<?php } ?>
	</ol>
</nav>
