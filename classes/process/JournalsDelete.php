<?php

class JournalsDelete extends ProcessExecutor {
    
    protected $cache;
    
    public function __construct() {
        $this->cache = Cache::instance();
    }
    
    public function parameters($string) {
        $parameters = [];
        if (strpos($string, ':') > 0) {
            $types = explode('+', $string);
            foreach ($types as $block) {
                list($type, $shorts) = explode(':', $block);
                foreach (explode(',', $shorts) as $short) {
                    $parameters[$type][] = $short;
                }
            }
        }
        return $parameters;
    }
    
    public function execute($parameters = []) {
        foreach ($parameters as $type => $shorts) {
            foreach($shorts as $short) {
                $class = ucfirst($type).'Entity';
                if (class_exists($class)) {
                    $entity = (new $class())->retrieveOne($short);
                    if ($entity !== false) {
                        (new $class())->deleteOne($entity->id);
                        $this->report(0, 'OK', $type.' '.$short.' deleted');
                    } else {
                        $this->report(0, 'warn', $type.' '.$short.' '.'unknown');
                    }
                    switch ($type) {
                        case 'paper': {
                            $paperKey = JournalsUtils::expand($short);
                            $this->clean('paper', $paperKey);
                            $tokens = explode('_', $short);
                            $issueKey = JournalsUtils::expand($tokens[0].'_'.$tokens[1]);
                            $this->clean('issue', $issueKey);
                            break;
                        }
                        case 'issue': {
                            $issueKey = JournalsUtils::expand($short);
                            $this->clean('issue', $issueKey);
                            foreach ($this->cache->keys(DEFAULT_LANG, 'paper', $issueKey.'_*') as $paperKey) {
                                $this->clean('paper', $paperKey);
                            }
                            break;
                        }
                        case 'journal':{
                            $journalKey = JournalsUtils::expand($short);
                            $this->clean('journal', $journalKey);
                            foreach ($this->cache->keys(DEFAULT_LANG, 'issue', $journalKey.'_*') as $issueKey) {
                                $this->clean('issue', $issueKey);
                                foreach ($this->cache->keys(DEFAULT_LANG, 'paper', $issueKey.'_*') as $paperKey) {
                                    $this->clean('paper', $paperKey);
                                }
                            }
                            break;
                        }
                    }
                }
            }
        }
    }
    
    protected function clean($type, $key) {
        $this->info(0, 'delete '.$type.' '.$key.' from cache');
        foreach (glob($this->cache->getRoot().'*', GLOB_ONLYDIR) as $path) {
            $locale = basename($path);
            if ($key && $this->cache->hasItem($locale.DS.$type, $key)) {
                $this->info(1, $locale.DS.$type);
                $this->cache->deleteItem($locale.DS.$type, $key);
            }
        }
        if ($type === 'paper') {
            $short = JournalsUtils::shrink($key);
            $this->info(0, 'delete entry '.$short.' from index');
            Store::deindex($short);
            $tokens = explode('_', $short);
            $directory = STORE_FOLDER.'journals'.DS.$tokens[0].DS.$tokens[1];
            $this->info(0, 'delete files from '.$directory);
            foreach (glob($directory.DS.$short.'*') as $path) {
                $this->info(1, basename($path));
                Zord::deleteRecursive($path);
            }
        }
    }
}

?>