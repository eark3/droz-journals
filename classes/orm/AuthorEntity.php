<?php

class AuthorEntity extends JournalsEntity {
    
    public $_type = 'author';
    
    protected function retrieveBy() {
        return $this->paper ? parent::retrieveAll(['paper' => $this->paper->id]) : false;
    }
    
}

?>