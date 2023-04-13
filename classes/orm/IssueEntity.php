<?php

class IssueEntity extends JournalsEntity {
    
    public $_type = 'issue';
    
    protected function retrieveBy() {
        return isset($this->issue) ? $this->issue : ($this->journal ? parent::retrieveAll(['journal' => $this->journal->id]) : false);
    }
    
}

?>