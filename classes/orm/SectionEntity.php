<?php

class SectionEntity extends JournalsEntity {
    
    protected function retrieveBy() {
        return $this->journal ? parent::retrieveAll(['journal' => $this->journal->id]) : false;
    }
    
}

?>