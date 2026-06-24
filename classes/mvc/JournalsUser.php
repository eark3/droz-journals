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
    
    public function hasRole($role, $context, $wild = true) {
        if ($role === 'counter') {
            return ($this->isConnected() && $this->hasRole('reader', $context)) || $this->hasRole('admin', $context);
        }
        return parent::hasRole($role, $context, $wild);
    }
    
}

?>