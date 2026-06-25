<?php

class JournalsCounterReport extends ProcessExecutor {
    
    use JournalsCommon, JournalsSushiService, ProcessSushiService;
    
    public function __construct() {
        $this->cache = Cache::instance();
    }
    
    public function parameters($string) {
        return json_decode(file_get_contents($string));
    }
    
    public function execute($parameters = []) {
        echo Zord::json_encode($this->counter($parameters))."\n";
    }
    
}

