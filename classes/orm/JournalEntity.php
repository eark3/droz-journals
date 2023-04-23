<?php

class JournalEntity extends JournalsEntity {
    
    public $_type = 'journal';
    
    protected function reset($id) {
        parent::reset($id);
        (new IssueEntity())->deleteAll(['journal' => $id]);
        (new SectionEntity())->deleteAll(['journal' => $id]);
    }
    
    protected function retrieveBy() {
        return $this->journal;
    }

}

?>