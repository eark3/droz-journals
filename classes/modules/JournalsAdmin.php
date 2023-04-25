<?php

class JournalsAdmin extends Admin {
    
    use JournalsModule;
    
    public function objects() {
        $objects = [];
        foreach ((new JournalEntity())->retrieveAll() as $journal) {
            $_journal = $this->_journal($journal);
            $object = [
                'label' => $_journal['context']
            ];
            foreach ($_journal['issues'] as $_issue) {
                $_object = [
                    'label' => $_issue['short']
                ];
                foreach ($_issue['sections'] as $_section) {
                    $__object = [
                        'label' => $_section['name']
                    ];
                    foreach ($_section['papers'] as $_paper) {
                        $___object = [
                            'label' => $_paper['short']
                        ];
                        $__object['ul'][] = $___object;
                    }
                    $_object['ul'][] = $__object;
                }
                $object['ul'][] = $_object;
            }
            $objects[] = $object;
        }
        return $objects;
    }
    
    public function settings() {
        $type = $this->params['type'] ?? null;
        $id = $this->params['id'] ?? null;
        $name = $this->params['name'] ?? null;
        if (!isset($type) || !isset($id)) {
            return $this->error(400);
        }
        $entity = null;
        switch ($type) {
            case 'journal': {
                $entity = new JournalEntity();
                break;
            }
            case 'section': {
                $entity = new SectionEntity();
                break;
            }
            case 'issue': {
                $entity = new IssueEntity();
                break;
            }
            case 'paper': {
                $entity = new PaperEntity();
                break;
            }
            case 'author': {
                $entity = new AuthorEntity();
                break;
            }
            case 'galley': {
                $entity = new GalleyEntity();
                break;
            }
        }
        if (!isset($entity)) {
            return $this->error(404);
        }
        $object = (new JournalEntity())->retrieveOne($id);
        if ($object === false) {
            return $this->error(404);
        }
        return $this->_settings($type, $object, $name);
    }
    
}

?>