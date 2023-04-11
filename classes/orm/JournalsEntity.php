<?php

abstract class JournalsEntity extends Entity {
    
    protected $journal = null;
    protected $issue = null;
    protected $paper = null;
    
    protected abstract function retrieveBy();
    
    public function retrieve($criteria = null, $deep = false) {
        if (is_string($criteria) && !is_numeric($criteria)) {
            $journal = null;
            $issue = null;
            $pages = null;
            $tokens = explode('_', $criteria);
            if (!in_array(count($tokens), [1,2,3])) {
                return false;
            }
            if (count($tokens) > 2) {
                list($journal, $issue, $pages) = $tokens;
            } else if (count($tokens) > 1) {
                list($journal, $issue) = $tokens;
            } else {
                $journal = $criteria;
            }
            $this->journal = (new JournalEntity())->retrieveOne(['context' => $journal]);
            if ($this->journal && $issue) {
                $volume = null;
                $number = null;
                $tokens = explode('.', $issue);
                if (!in_array(count($tokens), [1,2])) {
                    return false;
                }
                if (count($tokens) === 2) {
                    list($volume, $number) = $tokens;
                } else {
                    $volume = $issue;
                }
                $this->issue = (new IssueEntity())->retrieveOne([
                    'journal' => $this->journal->id,
                    'volume'  => $volume,
                    'number'  => $number
                ]);
                if ($this->issue && $pages) {
                    $this->paper = (new PaperEntity())->retrieveOne([
                        'issue' => $this->issue->id,
                        'pages' => $pages
                    ]);
                }
            }
            return $this->retrieveBy();
        }
        return parent::retrieve($criteria, $deep);
    }
    
}

?>