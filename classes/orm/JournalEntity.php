<?php

class JournalEntity extends JournalsEntity {
    
    public $_type = 'journal';
    
    protected function retrieveBy() {
        return $this->journal;
    }

}

?>