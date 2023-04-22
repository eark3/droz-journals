<?php

class JournalsImport extends Import {
    
    protected $journals = [];
    protected $journal  = null;
    protected $issue    = null;
    
    protected function configure($parameters = []) {
        parent::configure($parameters);
        if (!isset($this->refs)) {
            $set = $this->folder.'*';
            $issues = glob($set.'.json');
            $papers = glob($set, GLOB_ONLYDIR);
            $this->refs = [];
            foreach ([$issues, $papers] as $items) {
                foreach ($items as $item) {
                    $issue = pathinfo($item, PATHINFO_FILENAME);
                    $tokens = explode('_', $issue);
                    if (count($tokens) !== 2) {
                        continue;
                    }
                    list($journal,$issue) = $tokens;
                    if (!is_numeric($issue)) {
                        continue;
                    }
                    $journal = $this->journals[$journal] ?? (new JournalEntity())->retrieveOne($journal);
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
    
    protected function metadata($ean) {
        $issue["journal"] = $this->journal->id;
        $_issue = JournalsUtils::create(new IssueEntity(), $issue);
        echo "\tissue : ".JournalsUtils::short($this->journal->context, $_issue->volume, $_issue->number)."\n";
        foreach ($issue['papers'] as $paper) {
            $paper['journal'] = $this->journal->id;
            $paper['issue']   = $_issue->id;
            $_paper = JournalsUtils::create(new PaperEntity(), $paper);
            echo "\t\tpaper : ".JournalsUtils::short($this->journal->context, $_issue->volume, $_issue->number, $_paper->pages)."\n";
            foreach ($paper['authors'] as $author) {
                $author['paper'] = $_paper->id;
                $_author = JournalsUtils::create(new AuthorEntity(), $author);
                echo "\t\t\tauthor : ".JournalsUtils::name($_author)."\n";
            }
            foreach ($paper['galleys'] as $type) {
                JournalsUtils::create(new GalleyEntity(), [
                    'type'  => $type,
                    'paper' => $_paper->id
                ]);
                echo "\t\t\tgalley : ".$type."\n";
            }
        }
        return true;
    }
    
    protected function folders($ean) {
        list($journal,$issue) = explode('_', $ean);
        return [
            'source' => $this->folder.$ean,
            'target' => STORE_FOLDER.'journals'.$journal.DS.$issue
        ];
    }
    
    protected function contents($ean) {
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
    
}

?>