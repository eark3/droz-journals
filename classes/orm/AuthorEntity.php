<?php

class AuthorEntity extends JournalsEntity {
    
    protected function retrieveBy() {
        return $this->paper ? parent::retrieveAll(['paper' => $this->paper->id]) : false;
    }
    
}

?>