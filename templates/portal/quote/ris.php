TY - JOUR
<?php if (!empty($paper['authors'])) { ?>
<?php   for ($index = 0; $index < min([count($paper['authors']), 4]); $index++) { ?>
A<?php echo $index + 1; ?> - <?php echo JournalsUtils::name($paper['authors'][$index])."\n"; ?>
<?php   } ?>
<?php } ?>
PY - <?php echo date('Y/m/d', strtotime($issue['published']))."\n"; ?>
Y2 - <?php echo date('Y/m/d')."\n"; ?>
TI - <?php echo $paper['settings']['title']."\n"; ?>
JF - <?php echo str_replace('<br/>', ' ', $journal['settings']['name'])."\n"; ?>
JA - <?php echo $journal['context']."\n"; ?>
VL - <?php echo $issue['volume']."\n"; ?>
<?php if (!empty($issue['number'])) { ?>
IS - <?php echo $issue['number']."\n"; ?>
<?php } ?>
SE - <?php echo $section."\n"; ?>
<?php if (!empty($paper['settings']['pub-id::doi'])) { ?>
DO - <?php echo $paper['settings']['pub-id::doi']."\n"; ?>
<?php } ?>
UR - <?php echo $baseURL; ?>/article/view/<?php echo $paper['short']."\n"; ?>
ER -