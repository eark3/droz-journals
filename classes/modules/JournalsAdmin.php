<?php

class JournalsAdmin extends Admin {
        
    use JournalsModule;
    
    public function journals() {
        $journals = [];
        foreach ((new JournalEntity())->retrieveAll() as $journal) {
            $journals[] = $this->_journal($journal);
        }
        return $journals;
    }
    
    public function settings() {
        $type = $this->params['type'] ?? null;
        $id = $this->params['id'] ?? null;
        $name = $this->params['name'] ?? null;
        if (!isset($type) || !isset($id)) {
            return $this->error(400);
        }
        if (!in_array($type, CACHED_OBJECT_TYPES)) {
            return $this->error(404);
        }
        $class = ucfirst($type).'Entity';
        $object = (new $class())->retrieveOne($id);
        if ($object === false) {
            return $this->error(404);
        }
        return $this->_settings($type, $object, $name);
    }
    
    public function cache() {
        $process = $this->params['process'] ?? null;
        if (!isset($process)) {
            return $this->error(400);
        }
        $cache = Cache::instance();
        switch ($process) {
            case 'clear': {
                foreach (CACHED_OBJECT_TYPES as $type) {
                    $cache->clear($type);
                }
                return true;
            }
            default: {
                return $this->error(400);
            }
        }
    }
    
}

?>