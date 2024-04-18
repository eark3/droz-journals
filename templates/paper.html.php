<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr">
    
    <head>
        <title><?php echo $journalTitle; ?></title>
        <meta name="DCTERMS.volume" content="<?php echo $volume; ?>"/>
        <meta name="DCTERMS.year" content="<?php echo $year; ?>"/>
        <meta name="DCTERMS.EAN/ISBN" content="<?php echo $ean; ?>"/>
        <meta name="DCTERMS.DOI" content="<?php echo $doi; ?>"/>
        <meta name="DCTERMS.title.num" content="<?php echo $issueTitle; ?>"/>
        <meta name="DCTERMS.title" content="<?php echo $paperTitle; ?>"/>
<?php if (isset($paperSubtitle)) { ?>
        <meta name="DCTERMS.sous-title" content="<?php echo $paperSubtitle; ?>"/>
<?php } ?>
<?php foreach ($creators as $creator) { ?>
        <meta name="DCTERMS.creator" content="<?php echo $creator['first'].' '.$creator['last']; ?>"/>
<?php } ?>
        <meta name="DCTERMS.language" content="<?php echo $lang; ?>"/>
        <meta name="DCTERMS.page" content="Page_Start_<?php echo $start; ?>"/>
        <meta name="DCTERMS.page" content="Page_end_<?php echo $end; ?>"/>
    </head>
    
    <body class="version_<?php echo $journal; ?>_Zord_2023">
        
        <!-- <span class="page">xxx</span> --> <!-- on indique dans le cours du HTML les folios correspondants dans le PDF avec des balises commentaires-->
        
        <div class="section_block">
        
            <p class="section"><?php echo $sectionTitle; ?></p>
            <p class="section-ID"><?php echo $section; ?></p>
        
        </div>
        
        <div class="title_block">
        
            <p class="chap-title"><?php echo $paperTitle; ?></p>
<?php if (isset($paperSubtitle)) { ?>
            <p class="chap-subtitle"><?php echo $paperSubtitle; ?></p>
<?php } ?>
        
        </div>
        
        <div class="author_block">
        
<?php foreach ($creators as $creator) { ?>
            <div class="author">
                <p class="au"><?php echo $creator['first'].' '.$creator['last']; ?></p>
            </div>
<?php } ?>
         
        </div>
       
<?php if ($models['content'] ?? false) { ?>
        <div class="text_block">
        
<?php echo $models['content']; ?>
        
        </div>
<?php } ?>
        
<?php if (!empty($models['biblio'] ?? false)) { ?>
        <p class="h1">Bibliographie</p>
        
        <div class="bibl_block">
        
<?php   foreach ($models['biblio'] as $bibl) { ?>
            <p class="bibl"><?php echo $bibl; ?></p>
<?php   } ?>
        
        </div>
<?php } ?>
        
<?php if (!empty($models['annexe'] ?? false)) { ?>
        <p class="h1">Annexe</p>
    
        <div class="annex_block">
        
<?php   foreach ($models['annexe'] as $annex) { ?>
            <p class="annex"><?php echo $annex; ?></p>
<?php   } ?>
        
        </div>
<?php } ?>
        
<?php if (!empty($models['notes'] ?? false)) { ?>
        <p class="noindent">____________</p>
        
        <div class="footnotes_block">
        
<?php   foreach ($models['notes'] as $ref => $note) { ?>
            <p class="fn">
                <span class="num">
                    <a id="<?php echo str_replace(['#','_'], '', $ref); ?>" href="<?php echo $ref; ?>">
                        <sup><?php echo str_replace('#fn_', '', $ref); ?></sup>
                    </a>
                </span>
                <?php echo $note; ?>
            </p>
<?php   } ?>
        
        </div>
<?php } ?>
        
    </body>
</html>