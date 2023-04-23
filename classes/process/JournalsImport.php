<?php

class JournalsImport extends Import {
    
    protected $journals = [];
    protected $journal  = null;
    protected $issue    = null;
    protected $empty    = true;
    protected $cache    = null;
    
    public function parameters($string) {
        $this->refs = explode(',', $string);
    }
    
    private function journal($issue) {
        $tokens = explode('_', $issue);
        if (count($tokens) !== 2) {
            return false;
        }
        list($journal,$issue) = $tokens;
        if (!is_numeric($issue)) {
            return false;
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
        $this->empty = true;
    }
    
    protected function metadata($ean) {
        if (empty($this->issue)) {
            $this->info(2, $this->locale->messages->metadata->info->nodata);
            return true;
        }
        $this->issue["journal"] = $this->journal->id;
        $_issue = JournalsUtils::import('issue', $this->issue);
        $this->purge('issue', $_issue);
        $this->info(2, "issue : ".JournalsUtils::short($this->journal->context, $_issue->volume, $_issue->number));
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
            $paper['section'] = $section->id;
            $_paper = JournalsUtils::import('paper', $paper);
            $this->purge('paper', $_issue, $_paper);
            $this->info(3, "paper : ".JournalsUtils::short($this->journal->context, $_issue->volume, $_issue->number, $_paper->pages));
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
            foreach ($paper['authors'] as $author) {
                $author['paper'] = $_paper->id;
                $_author = JournalsUtils::import('author', $author);
                $this->info(4, "author : ".JournalsUtils::name($_author));
            }
            $galleys = [];
            foreach ($paper['galleys'] as $type) {
                JournalsUtils::import('galley', [
                    'type'  => $type,
                    'paper' => $_paper->id
                ]);
                $galleys[] = $type;
            }
            $this->info(4, "galleys : ".implode(', ', $galleys));
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
        $issue = (new IssueEntity())->retrieveOne($ean);
        $journal = (new JournalEntity())->retrieveOne($issue->journal);
        $papers = (new PaperEntity())->retrieveAll(['issue' => $issue->id]);
        $context = $journal->context;
        $date = $issue->date;
        foreach ($papers as $paper) {
            $authors = [];
            foreach ((new AuthorEntity())->retrieveAll(['paper' => $paper->id]) as $author) {
                $authors[] = Zord::collapse(JournalsUtils::name($author), false);
            }
            $authors = implode(' ', $authors);
            $short = JournalsUtils::short($journal->context, $issue->volume, $issue->number, $paper->pages);
            foreach (['html'/*,'pdf'*/] as $type) {
                $name = $paper->pages.'_'.$type;
                $file = JournalsUtils::path($journal->context, $issue->volume, $issue->number, $paper->pages, 'html');
                if (file_exists($file)) {
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
        $this->issue = Zord::arrayFromJSONFile($this->folder.$ean.'.json');
        return true;
    }
    
}

?>