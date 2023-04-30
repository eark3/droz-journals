<?php

class JournalsAdmin extends Admin {
        
    use JournalsModule;
    
    protected function __settings($type, $criteria) {
        $object   = false;
        $choices  = [];
        $settings = [];
        $next     = null;
        $journal  = false;
        $issue    = false;
        $section  = false;
        $paper    = false;
        $author   = false;
        $_criteria = [];
        switch ($type) {
            case 'journal': {
                if ($criteria !== 'first') {
                    $object = (new JournalEntity())->retrieveOne($criteria);
                }
                $next = 'issue';
                break;
            }
            case 'issue': {
                $journal = (new JournalEntity())->retrieveOne($this->params['journal']);
                if ($criteria !== 'first') {
                    $object = (new IssueEntity())->retrieveOne($criteria);
                }
                $next = 'section';
                break;
            }
            case 'section': {
                $journal = (new JournalEntity())->retrieveOne($this->params['journal']);
                $issue   = (new IssueEntity())->retrieveOne($this->params['issue']);
                if ($criteria !== 'first') {
                    $object = (new SectionEntity())->retrieveOne($criteria);
                }
                $next = 'paper';
                break;
            }
            case 'paper': {
                $journal = (new JournalEntity())->retrieveOne($this->params['journal']);
                $issue   = (new IssueEntity())->retrieveOne($this->params['issue']);
                $section = (new SectionEntity())->retrieveOne($this->params['section']);
                if ($criteria !== 'first') {
                    $object = (new PaperEntity())->retrieveOne($criteria);
                }
                $next = 'author';
                break;
            }
            case 'author': {
                $journal = (new JournalEntity())->retrieveOne($this->params['journal']);
                $issue   = (new IssueEntity())->retrieveOne($this->params['issue']);
                $section = (new SectionEntity())->retrieveOne($this->params['section']);
                $paper   = (new PaperEntity())->retrieveOne($this->params['paper']);
                if ($criteria !== 'first') {
                    $object = (new AuthorEntity())->retrieveOne($criteria);
                }
                break;
            }
        }
        $_criteria['journal'] = [
            'order' => Zord::value('admin', ['settings','order','journal'])
        ];
        foreach ((new JournalEntity())->retrieveAll($_criteria['journal']) as $_journal) {
            $selected = false;
            if ($journal !== false) {
                $selected = ($_journal->id === $journal->id);
            } else if ($type === 'journal' && $object !== false) {
                $selected = ($_journal->id === $object->id);
            }
            $choices['journal'][] = [
                'value'    => $_journal->id,
                'label'    => $_journal->context,
                'selected' => $selected
            ];
        }
        if ($journal !== false) {
            $_criteria['issue'] = [
                'journal' => $journal->id,
                'order'   => Zord::value('admin', ['settings','order','issue'])
            ];
            foreach ((new IssueEntity())->retrieveAll($_criteria['issue']) as $_issue) {
                $selected = false;
                if ($issue !== false) {
                    $selected = ($_issue->id === $issue->id);
                } else if ($type === 'issue' && $object !== false) {
                    $selected = ($_issue->id === $object->id);
                }
                $choices['issue'][] = [
                    'value'    => $_issue->id,
                    'label'    => JournalsUtils::short($journal->context, $_issue->volume, $_issue->number),
                    'selected' => $selected
                ];
            }
        }
        if ($journal !== false && $issue !== false) {
            $_criteria['section'] = [
                'journal' => $journal->id,
                'order'   => Zord::value('admin', ['settings','order','section'])
            ];
            foreach ((new SectionEntity())->retrieveAll($_criteria['section']) as $_section) {
                $papers = (new PaperEntity())->retrieveAll(['issue' => $issue->id, 'section' => $_section->id]);
                if ($papers->getIterator()->valid()) {
                    $selected = false;
                    if ($section !== false) {
                        $selected = ($_section->id === $section->id);
                    } else if ($type === 'section' && $object !== false) {
                        $selected = ($_section->id === $object->id);
                    }
                    $choices['section'][] = [
                        'value'    => $_section->id,
                        'label'    => $_section->name,
                        'selected' => $selected
                    ];
                }
            }
        }
        if ($issue !== false && $section !== false) {
            $_criteria['paper'] = [
                'issue'   => $issue->id,
                'section' => $section->id,
                'order'   => Zord::value('admin', ['settings','order','paper'])
            ];
            foreach ((new PaperEntity())->retrieveAll($_criteria['paper']) as $_paper) {
                $selected = false;
                if ($paper !== false) {
                    $selected = ($_paper->id === $paper->id);
                } else if ($type === 'paper' && $object !== false) {
                    $selected = ($_paper->id === $object->id);
                }
                $choices['paper'][] = [
                    'value'    => $_paper->id,
                    'label'    => JournalsUtils::short($journal->context, $issue->volume, $issue->number, $_paper->pages),
                    'selected' => $selected
                ];
            }
        }
        if ($paper !== false) {
            $_criteria['author'] = [
                'paper'   => $paper->id,
                'order'   => Zord::value('admin', ['settings','order','author'])
            ];
            foreach ((new AuthorEntity())->retrieveAll($_criteria['paper']) as $_author) {
                $selected = false;
                if ($author !== false) {
                    $selected = ($_author->id === $author->id);
                } else if ($type === 'author' && $object !== false) {
                    $selected = ($_author->id === $object->id);
                }
                $choices['author'][] = [
                    'value'    => $_author->id,
                    'label'    => JournalsUtils::name($_author, true),
                    'selected' => $selected
                ];
            }
        }
        $class = ucfirst($type).'Entity';
        if ($criteria === 'first') {
            $object = (new $class())->retrieveFirst($_criteria[$type]);
        }
        $settings['journal'] = $this->_settings('journal', $journal !== false ? $journal : $object);
        if ($journal !== false) {
            $settings['issue'] = $this->_settings('issue', $issue !== false ? $issue : $object);
        }
        if ($journal !== false && $issue !== false) {
            $settings['section'] = $this->_settings('section', $section !== false ? $section : $object);
        }
        if ($issue !== false && $section !== false) {
            $settings['paper'] = $this->_settings('paper', $paper !== false ? $paper : $object);
        }
        if ($paper !== false) {
            $settings['author'] = $this->_settings('author', $author !== false ? $author : $object);
        }
        return [$object, $choices, $next, $settings];
    }
    
    public function settings() {
        $type = $this->params['type'] ?? 'journal';
        $criteria = $this->params['id'] ?? 'first';
        $return = $this->params['return'] ?? 'data';
        if (!in_array($return, ['data','ui']) || !in_array($type, TUNABLE_OBJECT_TYPES)) {
            return $this->error(400);
        }
        list($object, $choices, $next, $settings) = $this->__settings($type, $criteria);
        if ($object === false) {
            return $this->error(404);
        }
        switch ($return) {
            case 'data': {
                $update = $this->params['update'] ?? null;
                if (!empty($update)) {
                    $update = Zord::objectToArray(json_decode($update));
                    foreach ($update as $name => $value) {
                        if (is_array($value)) {
                            $value = base64_encode(serialize($value));
                        }
                        (new SettingEntity($type))->updateOne([
                            'object' => $object->id,
                            'name'   => $name,
                            'locale' => $this->lang,
                        ], ['value' => $value]);
                    }
                    return true;
                } else {
                    $name = $this->params['name'] ?? null;
                    return $this->_settings($type, $object, $name);
                }
            }
            case 'ui': {
                $select = new View(
                    '/portal/page/admin/settings/select',
                    ['choices' => $choices, 'current' => $type, 'next' => $next],
                    $this->controler, 'admin'
                );
                $form = new View(
                    '/portal/page/admin/settings/form',
                    ['type' => $type, 'id' => $object->id, 'action' => $this->baseURL, 'settings' => $settings],
                    $this->controler, 'admin'
                );
                return ['select' => $select->render(), 'form' => $form->render()];
            }
        }
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