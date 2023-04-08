<?php

class SettingEntity extends Entity {
    
    protected $_type = null;
    
    public function __construct($type = null) {
        $this->_type = $type;
        parent::__construct();
    }
    
    public function create($data) {
        if (!empty($this->_type)) {
            $data['type'] = $this->_type;
        }
        return parent::create($data);
    }
    
    protected function query($criteria) {
        parent::query($criteria);
        if (!empty($this->_type)) {
            $this->engine()->where('type', $this->_type);
        }
    }
}

?>