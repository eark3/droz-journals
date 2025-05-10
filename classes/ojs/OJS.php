
<?php

class OJS {
    
    public static function journals() {
        $journals = [];
        $entities = (new OJSJournalEntity())->retrieve();
        foreach ($entities as $entity) {
            $journals[$entity->journal_id] = $entity->path;
        }
        return $journals;
    }
    
    public static function issues() {
        $issues = [];
        $journals = self::journals();
        foreach (self::entries() as $entry) {
            if (!in_array($entry['reference'], array_keys($issues))) {
                $issue = self::issue($entry['article']);
                if ($issue) {
                    $issues[$entry['reference']] = [
                        'path' => $journals[$issue->journal_id],
                        'id'   => $issue->issue_id
                    ];
                }
            }
        }
        return $issues;
    }
    
    public static function entries($reference = '%', $chapter = '%') {
        $criteria = Zord::value('ojs', ['criteria','galleys']);
        $criteria['remote_url']['like'] = Zord::substitute($criteria['remote_url']['like'], [
            'reference' => $reference,
            'chapter'   => $chapter
        ]);
        $galleys = (new OJSGalleyEntity())->retrieve([
            'many'  => true,
            'where' => $criteria
        ]);
        $entries = [];
        foreach ($galleys as $galley) {
            $tokens = explode('/', $galley->remote_url);
            if (count($tokens) < 2) {
                continue;
            }
            list($reference, $chapter) = array_slice($tokens, -2);
            if (strlen($reference) !== 13 || !is_numeric($reference)) {
                continue;
            }
            $entries[] = [
                'article'   => $galley->submission_id,
                'label'     => $galley->label,
                'type'      => $galley->label === "Dossier à l'achat" ? 'dossier' : 'article',
                'reference' => $reference,
                'chapter'   => $chapter
            ];
        }
        return $entries;
    }
    
    public static function paper($article) {
        return (new OJSPaperEntity())->retrieve($article);
    }
    
    public static function file($article) {
        $file = null;
        $revision = 0;
        $fileID = 0;
        $criteria = Zord::value('ojs', ['criteria','files']);
        $criteria['submission_id'] = $article;
        $files = (new OJSFileEntity())->retrieve([
            'many'  => true,
            'where' => $criteria
        ]);
        foreach ($files as $candidat) {
            $elected = false;
            if ($candidat->file_id > $fileID) {
                $elected = true;
            } else if ($candidat->file_id == $fileID) {
                if ($candidat->revision > $revision) {
                    $elected = true;
                }
            }
            if ($elected) {
                $file     = $candidat;
                $fileID   = $candidat->file_id;
                $revision = $candidat->revision;
            }
        }
        return $file;
    }
    
    public static function issue($article) {
        $publication = (new OJSPublicationEntity())->retrieve(['where' => [
            'submission_id' => $article
        ]]);
        return ($publication !== false) ? (new OJSIssueEntity())->retrieve($publication->issue_id) : false;
    }
    
    public static function product($entry) {
        $product = null;
        $paper = self::paper($entry['article']);
        if ($paper === false) {
            return "nopaper";
        }
        $file  = self::file($entry['article']);
        if (!isset($file)) {
            return "nofile";
        }
        $issue = self::issue($entry['article']);
        if ($issue === false) {
            return "noissue";
        }
        $pages = !empty($paper->pages) ? $paper->pages : substr($entry['chapter'], strrpos($entry['chapter'], '_') + 1);
        if (empty($pages)) {
            return "nopages";
        }
        $pages = explode('-', $pages);
        if (count($pages) !== 2) {
            return "nopages";
        }
        list($start, $end) = $pages;
        $product = array_merge($entry, [
            'journal'  => $issue->journal_id,
            'issue'    => $issue->issue_id,
            'locale'   => $paper->locale,
            'file'     => $file->file_id,
            'revision' => $file->revision,
            'genre'    => $file->genre_id ?? '',
            'stage'    => $file->file_stage,
            //'date'     => date_format(date_create($file->date_uploaded), 'Ymd'),
            //'date'     => date_format(date_create($issue->date_published), 'Ymd'),
            //'date'     => date_format(date_create($issue->last_modified), 'Ymd'),
            'date'     => date_format(date_create($file->date_modified), 'Ymd'),
            'type'     => $file->file_type === 'application/pdf' ? 'pdf' : 'html',
            'start'    => $start,
            'end'      => $end,
            'open'     => $issue->open_access_date
        ]);
        $product['source'] = self::path($product);
        $product['target'] = STORE_FOLDER.'pdf'.DS.$product['reference'].DS.$product['chapter'].'.pdf';
        $product['file']   = basename($product['target']);
        $criteria = Zord::value('ojs', ['criteria','settings']);
        $criteria['submission_id'] = $entry['article'];
        $settings = (new OJSSettingEntity('submission'))->retrieve([
            'many'  => true,
            'where' => $criteria
        ]);
        foreach ($settings as $setting) {
            $locale = $setting->locale;
            $name = $setting->setting_name;
            $value = $setting->setting_value;
            if (!empty(trim($value))) {
                switch ($name) {
                    case 'title':
                    case 'subtitle': {
                        if ($product['locale'] == $locale) {
                            $product[$name] = $value;
                        }
                        break;
                    }
                    case 'abstract': {
                        $product['abstracts'][$locale] = $value;
                        break;
                    }
                }
            }
        }
        $authors = (new OJSAuthorEntity())->retrieve([
            'many'  => true,
            'where' => ['submission_id' => $entry['article']]
        ]);
        foreach ($authors as $author) {
            if (!empty(trim($author->last_name)) || !empty(trim($author->first_name))) {
                if ($author->first_name !== 's.' && $author->first_name !== 'N.') {
                    $product['authors'][] = [
                        'firstName' => $author->first_name,
                        'lastName'  => $author->last_name
                    ];
                }
            }
        }
        return $product;
    }
    
    public static function path($file) {
        $file['base'] = Zord::value('ojs', ['remote','base']);
        return Zord::substitute(Zord::value('ojs', ['remote','path']), $file);
    }
}

?>