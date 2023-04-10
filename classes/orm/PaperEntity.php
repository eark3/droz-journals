<?php

class PaperEntity extends Entity {
    
    public function retrieve($criteria = null, $deep = false) {
        if (is_string($criteria) && !is_numeric($criteria)) {
            $tokens = explode('_', $criteria);
            if (!in_array(count($tokens), [3,4])) {
                return false;
            }
            $number = null;
            if (count($tokens) > 3) {
                list($journal,$volume,$number,$pages) = $tokens;
            } else {
                list($journal,$volume,$pages) = $tokens;
            }
            $journal = (new JournalEntity())->retrieveOne(['context' => $journal]);
            if ($journal === false) {
                return false;
            }
            $issue = (new IssueEntity())->retrieveOne([
                'journal' => $journal->id,
                'volume'  => $volume,
                'number'  => $number
            ]);
            if ($issue === false) {
                return false;
            }
            return parent::retrieveOne([
                'issue' => $issue->id,
                'pages' => $pages
            ]);
        }
        return parent::retrieve($criteria, $deep);
    }
    
}

?>