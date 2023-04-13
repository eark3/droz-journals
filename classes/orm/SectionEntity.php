<?php

class SectionEntity extends JournalsEntity {
    
    public $_type = 'section';
    
    protected function retrieveBy() {
        return $this->journal ? parent::retrieveAll(['journal' => $this->journal->id]) : false;
    }
    
}

?>