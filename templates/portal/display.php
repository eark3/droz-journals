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
		</div>
		<script src="/js/jquery.js" type="text/javascript"></script>
		<script type="text/javascript">
		
    		$(document).ready(function() {
    			if (window.location.href.indexOf("/HCL/") > -1) {
    				$('.header_view').css("background-color","#e4d0ba");
    				$('.header_view').css("border-color","#e4d0ba");
    				$('.header_view').css("color","#d62d37");
    				$('.header_view span').css("color","#d62d37");
    				$('.header_view a').css("color","#d62d37");
    			}
    		});
    		
    		var myIframe = document.getElementById('iframeArticle');
    		myIframe.addEventListener("load", function() {
    			$('#iframeArticle').contents().find('head link').remove();
    			$('#iframeArticle').contents().find('head').append('<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto" type="text/css" />');	  
    			$('#iframeArticle').contents().find('head').append('<link rel="stylesheet" href="/journals/css/article.css" type="text/css" />');
    			$('#iframeArticle').contents().find('head').append('<link rel="stylesheet" href="/journals/css/sidebar.css" type="text/css" />');
    			if (window.location.href.indexOf("/HCL/") > -1) {
    				var style = document.createElement('style');
    				style.textContent = '.center {text-align:center;color: #606060;}.header_view{background:#e4d0ba !important;}';
    			} else {
    				var style = document.createElement('style');
    				style.textContent = '.center {text-align:center;color: #606060;}';
    			}
    			myIframe.contentDocument.head.appendChild(style);
    		});
		
		</script>
	</body>
</html>