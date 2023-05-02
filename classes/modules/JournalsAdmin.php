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
    
    protected function adjusted($type, $name, $value, $settings) {
        $config = Zord::value('admin', ['settings','fields', $type, $name]) ?? [];
        if ($config['template'] === 'image' && isset($_FILES[$name])) {
            $source = $_FILES[$name]['tmp_name'];
            if (empty($source)) {
                return false;
            }
            $filename = $_FILES[$name]['name'];
            $folder = STORE_FOLDER.'public'.DS.'journals'.DS.'images';
            switch ($name) {
                case 'homepageImage': {
                    $filename = $settings['journal']['acronym'].DS.$filename;
                    list($width, $height) = getimagesize($source);
                    $value = [
                        'name'         => $filename,
                        'uploadName'   => $name.'_'.str_replace('-', '_', $this->lang).'.'.pathinfo($filename, PATHINFO_EXTENSION),
                        'width'        => $width,
                        'heigth'       => $height,
                        'dateUploaded' => date('Y-m-d H:i:s'),
                        'altText'      => ''
                    ];
                    break;
                }
                case 'bannerImage': {
                    $value = $filename;
                }
            }
            $target = $folder.DS.$filename;
            if (!empty($source) && !is_dir($target)) {
                move_uploaded_file($source, $target);
            }
        }
        $content = 'string';
        if (is_array($value)) {
            $value   = base64_encode(serialize($value));
            $content = 'object';
        }
        return [$value, $content];
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
                $update = array_merge($update, $_FILES);
                if (!empty($update)) {
                    foreach ($update as $name => $value) {
                        $adjusted = $this->adjusted($type, $name, $value, $settings);
                        if ($adjusted) {
                            list($value, $content) = $adjusted;
                            $key = [
                                'object' => $object->id,
                                'name'   => $name,
                                'locale' => $this->lang
                            ];
                            $set = [
                                'value'   => $value,
                                'content' => $content
                            ];
                            $setting = (new SettingEntity($type))->retrieveOne($key);
                            if ($setting !== false) {
                                (new SettingEntity($type))->updateOne($key, $set);
                            } else {
                                (new SettingEntity($type))->create(array_merge($key, $set));
                            }
                            //$key = $this->_key($type, $object, $context, $issue);
                            //$this->cache->deleteItem($type, $key);
                        }
                    }
                    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? null) === 'XMLHttpRequest' ? ['message' => $this->locale->settings->updated] : $this->redirect($this->baseURL.'/admin'); 
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
                $hidden = [];
                foreach ($choices as $name => $options) {
                    $value = $options[0]['value'];
                    foreach ($options as $option) {
                        if ($option['selected']) {
                            $value = $option['value'];
                            break;
                        }
                    }
                    $hidden[$name] = $value;
                }
                $form = new View(
                    '/portal/page/admin/settings/form',
                    ['hidden' => $hidden, 'type' => $type, 'id' => $object->id, 'settings' => $settings],
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