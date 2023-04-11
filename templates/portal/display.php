<?php $locale = Zord::getLocale('portal', $lang); ?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" xml:lang="<?php echo $lang; ?>">
	<head>
		<base href="<?php echo $base; ?>">
		<title><?php echo $title; ?></title>
		<meta charset="UTF-8" />
		<meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes"/>
		<meta http-equiv="Cache-Control" content="no-cache" />
		<meta http-equiv="Pragma" content="no-cache" />
		<meta http-equiv="Expires" content="0" />
		<link rel="icon" type="image/x-icon" href="/img/favicon.ico" />
		<link rel="stylesheet" type="text/css" media="screen" href="/journals/css/fonts.css"/>
		<link rel="stylesheet" type="text/css" media="screen" href="/journals/css/common.css"/>
		<link rel="stylesheet" type="text/css" media="screen" href="/journals/css/display.css"/>
		<link rel="stylesheet" type="text/css" media="screen" href="/journals/css/CFS/bootstrap.css"/>
		<link rel="stylesheet" type="text/css" media="screen" href="/journals/css/CFS/layout.css"/>
		<script src="/js/jquery.js" type="text/javascript"></script>
	</head>
	<body class="pkp_page_article pkp_op_view">
		<header class="header_view">
<?php foreach ($ariadne as $label => $link) { ?>
			<a href="<?php echo is_array($link) ? $link[1] : $link; ?>">
				<span>
					<?php echo is_array($link) ? $link[0] : ($locale->ariadne->$label ?? ($locale->ariadne->$link ?? $link)); ?>
<?php   if ($label === 'archive') { ?>
					<i><?php echo $controler->journal->name; ?></i>
<?php   } ?>
				</span>
			</a>
<?php   if ($label !== 'active') { ?>
			<span>/</span>
<?php   } ?>
<?php } ?>
		</header>
		<div id="htmlContainer" class="galley_view"	style="overflow: visible; -webkit-overflow-scrolling: touch">
			<iframe id="iframeArticle" name="htmlFrame" src="<?php echo $baseURL; ?>/article/download/<?php echo $paper['short']; ?>/<?php echo $display; ?>" allowfullscreen webkitallowfullscreen></iframe>
			<script src="/journals/js/iframe.js" type="text/javascript"></script>
		</div>
	</body>
</html>