<?php

class IssueEntity extends Entity {
    
    public function retrieve($criteria = null, $deep = false) {
        if (is_string($criteria) && !is_numeric($criteria)) {
            $tokens = explode('_', $criteria);
            if (!in_array(count($tokens), [2,3])) {
                return false;
            }
            $number = null;
            if (count($tokens) > 2) {
                list($journal,$volume,$number) = $tokens;
            } else {
                list($journal,$volume) = $tokens;
            }
            $journal = (new JournalEntity())->retrieveOne(['context' => $journal]);
            if ($journal === false) {
                return false;
            }
            return parent::retrieveOne([
                'journal' => $journal->id,
                'volume'  => $volume,
                'number'  => $number
            ]);
        }
        return parent::retrieve($criteria, $deep);
    }
    
}

?>