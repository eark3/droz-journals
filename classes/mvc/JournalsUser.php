<?php

class JournalsUser extends User {
    
    use ApiKeyUser;
    
    public $institution = null;
    
    public function setInstitution($institution) {
        $this->institution = $institution;
    }
    
    public function isInstitution() {
        return isset($this->institution);
    }
    
    public function getInstitution() {
        return $this->institution;
    }
    
}

?>