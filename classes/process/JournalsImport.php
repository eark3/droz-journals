<?php

class JournalsImport extends Import {
    
    protected $journals = [];
    protected $journal  = null;
    protected $issue    = null;
    protected $empty    = false;
    protected $cache    = null;
    
    private function journal($issue) {
        $tokens = explode('_', $issue);
        if (count($tokens) !== 2) {
            return false;
        }
        list($journal,$issue) = $tokens;
        $tokens = explode('-', $issue);
        foreach ($tokens as $issue) {
            if (!is_numeric($issue)) {
                return false;
            }
        }
        return $this->journals[$journal] ?? false;
    }
    
    private function purge($type, $issue, $paper = null) {
        $key = str_replace('-', '_' ,JournalsUtils::short($this->journal->context, $issue->volume, $issue->number, $paper->pages ?? null, true));
        if ($this->cache->hasItem($type, $key)) {
            $this->cache->deleteItem($type, $key);
        }
    }
    
    protected function configure($parameters = []) {
        parent::configure($parameters);
        $this->cache = Cache::instance();
        foreach ((new JournalEntity())->retrieveAll() as $journal) {
            $this->journals[$journal->context] = $journal;
        }
        if (!isset($this->refs)) {
            $set = $this->folder.'*';
            $issues = glob($set.'.json');
            $papers = glob($set, GLOB_ONLYDIR);
            $this->refs = [];
            foreach ([$issues, $papers] as $items) {
                foreach ($items as $item) {
                    if (is_file($item)) {
                        $issue = pathinfo($item, PATHINFO_FILENAME);
                    } else if (is_dir($item)) {
                        $issue = pathinfo($item, PATHINFO_BASENAME);
                    }
                    $journal = $this->journal($issue);
                    if ($journal === false) {
                        continue;
                    }
                    if (!in_array($issue, $this->refs)) {
                        $this->refs[] = $issue;
                    }
                }
            }
        }
    }
    
    protected function resetRef($ean) {
        parent::resetRef($ean);
        $this->journal = null;
        $this->issue = null;
        $this->empty = false;
    }
    
    protected function metadata($ean) {
        if (empty($this->issue)) {
            $this->info(2, $this->locale->messages->metadata->info->nodata);
            return true;
        }
        $this->issue["journal"] = $this->journal->id;
        $_issue = JournalsUtils::import('issue', $this->issue);
        $this->purge('issue', $_issue);
        if ($this->issue['reset'] ?? false) {
            (new PaperEntity())->delete(['issue' => $_issue->id]);
        }
        foreach ($this->issue['papers'] as $paper) {
            $paper['journal'] = $this->journal->id;
            $paper['issue']   = $_issue->id;
            $section = (new SectionEntity())->retrieveOne([
                'journal' => $this->journal->id,
                'name'    => $paper['section']
            ]);
            if ($section === false) {
                list($name, $place, $title) = explode(':', $paper['section']);
                JournalsUtils::create(new SectionEntity(),[
                    'journal'  => $this->journal->id,
                    'name'     => $name,
                    'place'    => $place,
                    'settings' => ['title' => [$this->lang => [
                        'value' => $title,
                        'content' => 'string'
                    ]]]
                ]);
            }
            $paper['section'] = $section->id;
            $_paper = JournalsUtils::import('paper', $paper);
            $this->purge('paper', $_issue, $_paper);
            $this->info(2, "paper : ".JournalsUtils::short($this->journal->context, $_issue->volume, $_issue->number, $_paper->pages));
            if ($paper['reset'] ?? false) {
                foreach (explode(',', $paper['reset']) as $reset) {
                    switch ($reset) {
                        case 'authors': {
                            (new AuthorEntity())->delete(['paper' => $_paper->id]);
                            break;
                        }
                        case 'galleys': {
                            (new GalleyEntity())->delete(['paper' => $_paper->id]);
                            break;
                        }
                    }
                }
            }
            $authors = [];
            foreach ($paper['authors'] as $author) {
                $author['paper'] = $_paper->id;
                $_author = JournalsUtils::import('author', $author);
                $authors[] = JournalsUtils::name($_author);
            }
            if (!empty($authors)) {
                $this->info(3, "authors : ".implode(', ', $authors));
            };
            $galleys = [];
            foreach ($paper['galleys'] as $type) {
                JournalsUtils::import('galley', [
                    'type'  => $type,
                    'paper' => $_paper->id
                ]);
                $galleys[] = $type;
            }
            if (!empty($galleys)) {
                $this->info(3, "galleys : ".implode(', ', $galleys));
            }
        }
        return true;
    }
    
    protected function folders($ean) {
        if ($this->empty) {
            return null;
        }
        list($journal,$issue) = explode('_', $ean);
        return [
            $this->folder.$ean,
            STORE_FOLDER.'journals'.DS.$journal.DS.$issue
        ];
    }
    
    protected function contents($ean) {
        if ($this->empty) {
            return null;
        }
        $contents = [];
        $issue    = (new IssueEntity())->retrieveOne($ean);
        $journal  = (new JournalEntity())->retrieveOne($issue->journal);
        $papers   = (new PaperEntity())->retrieveAll(['issue' => $issue->id]);
        $context  = $journal->context;
        $date     = $issue->date;
        foreach ($papers as $paper) {
            $authors = [];
            foreach ((new AuthorEntity())->retrieveAll(['paper' => $paper->id]) as $author) {
                $authors[] = Zord::collapse(JournalsUtils::name($author), false);
            }
            $authors = implode(' ', $authors);
            $short = JournalsUtils::short($journal->context, $issue->volume, $issue->number, $paper->pages);
            foreach (['html','pdf'] as $type) {
                $name = $paper->pages.'_'.$type;
                $file = JournalsUtils::path($journal->context, $issue->volume, $issue->number, $paper->pages, $type);
                if (file_exists($file)) {
                    $this->info(2, basename($file));
                    if ($type === 'pdf') {
                        Zord::execute('exec', PDFTOTEXT_COMMAND, ['file' => $file]);
                        $file = str_replace('.pdf', '.txt', $file);
                    }
                    $content = Store::align(file_get_contents($file), $type, true);
                    $contents[] = [
                        'name'    => $name,
                        'short'   => $short,
                        'type'    => $type,
                        'date'    => $date,
                        'journal' => $context,
                        'authors' => $authors,
                        'content' => $content
                    ];
                }
            }
        }
        return $contents;
    }
    
    protected function check($ean) {
        $this->journal = $this->journal($ean);
        if ($this->journal === false) {
            $this->error(3, Zord::substitute($this->locale->messages->check->error->ref->wrong, ['ref' => $ean]));
            return false;
        }
        $this->issue = Zord::arrayFromJSONFile($this->folder.$ean.'.json');
        if (empty($this->issue) && !file_exists($this->folder.$ean)) {
            $this->error(3, Zord::substitute($this->locale->messages->check->error->ref->wrong, ['ref' => $ean]));
            return false;
        }
        $result = true;
        list($source,$target) = $this->folders($ean);
        foreach ($this->issue['papers'] ?? [] as $paper) {
            $section = $paper['section'] ?? null;
            if ($section) {
                $_section = (new SectionEntity())->retrieveOne([
                    'journal' => $this->journal->id,
                    'name'    => $section
                ]);
                if ($_section === false) {
                    $this->error(3, Zord::substitute($this->locale->messages->check->error->missing->section, ['section' => $section]));
                    $result &= false;
                }
            }
            $short = JournalsUtils::short($this->journal->context, $this->issue['volume'], $this->issue['number'], $paper['pages']);
            foreach ($paper['galleys'] ?? [] as $galley) {
                if ($galley === 'shop') {
                    if (!isset($this->issue['ean'])) {
                        $this->error(3, $this->locale->messages->check->error->missing->ean);
                        $result &= false;
                    }
                } else {
                    $found = false;
                    foreach ([$source,$target] as $folder) {
                        $file = $folder.DS.$short.'.'.$galley;
                        if (file_exists($file) && is_file($file) && is_readable($file)) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $this->error(3, Zord::substitute($this->locale->messages->check->error->missing->file, ['file' => $ean.DS.$short.'.'.$galley]));
                        $result &= false;
                    }
                }
            }
        }
        $this->empty = !file_exists($this->folder.$ean);
        return $result;
    }
    
}

?>