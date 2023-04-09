TY - JOUR
<?php if (!empty($controler->authors)) { ?>
<?php   for ($index = 0; $index < min([count($controler->authors), 4]); $index++) { ?>
A<?php echo $index + 1; ?> - <?php echo $controler->authors[$index]."\n"; ?>
<?php   } ?>
<?php } ?>
PY - <?php echo date('Y/m/d', strtotime($controler->issue->published))."\n"; ?>
Y2 - <?php echo date('Y/m/d')."\n"; ?>
TI - <?php echo $controler->paper->title."\n"; ?>
JF - <?php echo $controler->journal->name."\n"; ?>
JA - <?php echo $controler->journal->context."\n"; ?>
VL - <?php echo $controler->issue->volume."\n"; ?>
<?php if (!empty($controler->issue->number)) { ?>
IS - <?php echo $controler->issue->number."\n"; ?>
<?php } ?>
SE - <?php echo $controler->section->title."\n"; ?>
<?php if (!empty($controler->paper->doi)) { ?>
DO - <?php echo $controler->paper->doi."\n"; ?>
<?php } ?>
UR - <?php echo $baseURL; ?>/article/view/<?php echo $controler->paper->id."\n"; ?>
ER -