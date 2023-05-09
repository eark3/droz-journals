<?php

abstract class JournalsEntity extends Entity {
    
    protected $journal = null;
    protected $issue = null;
    protected $paper = null;
    
    protected abstract function retrieveBy();
    
    protected function reset($id) {
        $class = get_class($this);
        $type = strtolower(substr($class, 0, strpos($class, 'Entity')));
        (new SettingEntity($type))->deleteAll(['object' => $id]);
    }
    
    public function retrieveOne($criteria, $deep = false) {
        $object = parent::retrieveOne($criteria, $deep);
        return isset($object->id) ? $object : false;
    }
    
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
                    if ($this->paper === false) {
                        return false;
                    }
                }
            }
            return $this->retrieveBy();
        } else if (is_string($criteria) && preg_match('/^97[89]\d{10}$/', $criteria)) {
            $this->issue = (new IssueEntity())->retrieveOne(['ean' => $criteria]);
            if ($this->issue !== false) {
                $this->journal = (new JournalEntity())->retrieveOne($this->issue->journal);
            }
            return $this->retrieveBy();
        }
        return parent::retrieve($criteria, $deep);
    }
    
    public function delete($criteria = null, $deep = false) {
        $entity = parent::retrieve($criteria = null, $deep = false);
        parent::delete($criteria, $deep);
        if ($entity) {
            if ($this->is_many($criteria)) {
                foreach ($entity as $entry) {
                    $this->reset($entry->id);
                }
            } else {
                $this->reset($entity->id);
            }
        }
    }
    
}

?>