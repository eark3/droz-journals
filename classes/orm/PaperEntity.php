<?php

class PaperEntity extends JournalsEntity {
    
    public $_type = 'paper';
    
    public function retrieveBy() {
        if (!empty($this->paper)) {
            return $this->paper;
        } else if (!empty($this->issue)) {
            return parent::retrieveAll(['issue' => $this->issue->id]);
        } else if (!empty($this->journal)) {
            $entities = (new IssueEntity())->retrieveAll(['journal' => $this->journal->id]);
            $issues = [];
            foreach ($entities as $issue) {
                $issues[] = $issue->id;
            }
            return parent::retrieveAll(['issue' => ['in' => $issues]]);
        }
    }
    
}

?>