<?php

class JournalsAdmin extends Admin {
        
    use JournalsModule;
    
    public function settings() {
        $type = $this->params['type'] ?? 'journal';
        $id = $this->params['id'] ?? 'first';
        $return = $this->params['return'] ?? 'data';
        if (!in_array($return, ['data','ui']) || !in_array($type, CACHED_OBJECT_TYPES)) {
            return $this->error(400);
        }
        $order = false;
        switch ($type) {
            case 'journal': {
                $order = ['asc' => 'place'];
                break;
            }
            case 'issue': {
                $order = [['asc' => 'volume'],['asc' => 'number']];
                break;
            }
            case 'section': {
                $order = ['asc' => 'place'];
                break;
            }
            case 'paper': {
                $order = ['asc' => 'place'];
                break;
            }
            case 'author': {
                $order = [['asc' => 'last'],['asc' => 'first']];
                break;
            }
        }
        $class = ucfirst($type).'Entity';
        $id = ($id === 'first') ? ['first' => true, 'order' => $order] : $id;
        $object = (new $class())->retrieveOne($id);
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
                $choices = [];
                $next    = null;
                $journal = false;
                $issue   = false;
                $section = false;
                $paper   = false;
                $author  = false;
                switch ($type) {
                    case 'journal': {
                        $journal = $object;
                        $next = 'issue';
                        break;
                    }
                    case 'issue': {
                        $journal = (new JournalEntity())->retrieveOne($this->params['journal']);
                        $issue = $object;
                        $next = 'section';
                        break;
                    }
                    case 'section': {
                        $journal = (new JournalEntity())->retrieveOne($this->params['journal']);
                        $issue   = (new IssueEntity())->retrieveOne($this->params['issue']);
                        $section = $object;
                        $next = 'paper';
                        break;
                    }
                    case 'paper': {
                        $journal = (new JournalEntity())->retrieveOne($this->params['journal']);
                        $issue   = (new IssueEntity())->retrieveOne($this->params['issue']);
                        $section = (new SectionEntity())->retrieveOne($this->params['section']);
                        $paper   = $object;
                        $next = 'author';
                        break;
                    }
                    case 'author': {
                        $journal = (new JournalEntity())->retrieveOne($this->params['journal']);
                        $issue   = (new IssueEntity())->retrieveOne($this->params['issue']);
                        $section = (new SectionEntity())->retrieveOne($this->params['section']);
                        $paper   = (new PaperEntity())->retrieveOne($this->params['paper']);
                        $author  = $object;
                        break;
                    }
                }
                if ($journal !== false) {
                    foreach ((new JournalEntity())->retrieveAll(['order' => ['asc' => 'place']]) as $_journal) {
                        $choices['journal'][] = [
                            'value'    => $_journal->id,
                            'label'    => $_journal->context,
                            'selected' => ($_journal->id === $journal->id)
                        ];
                    }
                }
                if ($issue !== false) {
                    foreach ((new IssueEntity())->retrieveAll([
                        'journal' => $journal->id,
                        'order'   => [['asc' => 'volume'],['asc' => 'number']]
                    ]) as $_issue) {
                        $choices['issue'][] = [
                            'value'    => $_issue->id,
                            'label'    => JournalsUtils::short($journal->context, $_issue->volume, $_issue->number),
                            'selected' => ($_issue->id === $issue->id)
                        ];
                    }
                }
                if ($section !== false) {
                    foreach ((new SectionEntity())->retrieveAll([
                        'journal' => $journal->id,
                        'order'   => ['asc' => 'place']
                    ]) as $_section) {
                        $papers = (new PaperEntity())->retrieveAll(['issue' => $issue->id, 'section' => $_section->id]);
                        if ($papers->getIterator()->valid()) {
                            $value = $_section->name;
                            $choices['section'][] = [
                                'value'    => $_section->id,
                                'label'    => $_section->name,
                                'selected' => ($_section->id === $section->id)
                            ];
                        }
                    }
                }
                if ($paper !== false) {
                    foreach ((new PaperEntity())->retrieveAll([
                        'issue'   => $issue->id,
                        'section' => $section->id,
                        'order'   => ['asc' => 'place']
                    ]) as $_paper) {
                        $choices['paper'][] = [
                            'value'    => $_paper->id,
                            'label'    => JournalsUtils::short($journal->context, $issue->volume, $issue->number, $_paper->pages),
                            'selected' => ($_paper->id === $paper->id)
                        ];
                    }
                }
                if ($author !== false) {
                    foreach ((new AuthorEntity())->retrieveAll([
                        'paper'   => $paper->id,
                        'order'   => [['asc' => 'last'],['asc' => 'first']]
                    ]) as $_author) {
                        $choices['author'][] = [
                            'value'    => $_author->id,
                            'label'    => JournalsUtils::name($_author, true),
                            'selected' => ($_author->id === $author->id)
                        ];
                    }
                }
                $select = new View(
                    '/portal/page/admin/settings/select',
                    ['choices' => $choices, 'current' => $type, 'next' => $next],
                    $this->controler, 'admin'
                );
                $form = new View(
                    '/portal/page/admin/settings/form',
                    ['type' => $type, 'id' => $object->id, 'action' => $this->baseURL, 'settings' => $this->_settings($type, $object)],
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