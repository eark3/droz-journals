<?php

class OJSSettingEntity extends OJSEntity {
    
    public function __construct($type) {
        parent::__construct([
            'table' => $type,
            'field' => Zord::value('ojs', ["settings",$type]) ?? $type
        ]);
    }
    
}

?>