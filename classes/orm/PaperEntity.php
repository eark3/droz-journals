<?php

class PaperEntity extends JournalsEntity {
    
    public $_type = 'paper';
    
    public function retrieveBy() {
        if ($this->paper !== false) {
            return $this->paper;
        } else if ($this->issue !== false) {
            return parent::retrieveAll(['issue' => $this->issue->id]);
        } else if ($this->journal !== false) {
            $entities = (new IssueEntity())->retrieveAll(['journal' => $this->journal->id]);
            $issues = [];
            foreach ($entities as $issue) {
                $issues[] = $issue->id;
            }
            return parent::retrieveAll(['issue' => ['in' => $issues]]);
        } else {
            return parent::retrieveAll();
        }
    }
    
}

?>