<?php

class JournalsDelete extends ProcessExecutor {
    
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
                    if ($entity === false) {
                        $this->report(0, 'warn', $type.' '.$short.' '.'unknown');
                    }
                    (new $class())->deleteOne($entity->id);
                    $this->report(0, 'OK', $type.' '.$short.' deleted');
                    $parentType = null;
                    $parentKey = null;
                    switch ($type) {
                        case 'paper': {
                            $issue = (new IssueEntity())->retrieveOne($entity->issue);
                            $journal = (new JournalEntity())->retrieveOne($issue->journal);
                            $parentType = 'issue';
                            $parentKey = str_replace('-', '_', JournalsUtils::short($journal->context, $issue->volume, $issue->number, null, true));
                            break;
                        }
                        case 'issue': {
                            break;
                        }
                        case 'journal':{
                            break;
                        }
                    }
                    if ($parentType && $parentKey) {
                        $cache = Cache::instance();
                        foreach (Zord::value('portal', 'lang') as $locale) {
                            if ($cache->hasItem($locale.DS.$parentType, $parentKey)) {
                                $cache->deleteItem($locale.DS.$parentType, $parentKey);
                                $this->info(0, 'delete '.$parentKey.' from cache '.$locale.DS.$parentType);
                            }
                        }
                    }
                }
            }
        }
    }
    
}

?>