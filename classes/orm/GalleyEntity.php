<?php

class GalleyEntity extends JournalsEntity {
    
    public $_type = 'galley';
    
    protected function retrieveBy() {
        return $this->paper ? parent::retrieveAll(['paper' => $this->paper->id]) : false;
    }
    
}

?>