<?php

use CSSValidator\CSSValidator;

class JournalsAdmin extends StoreAdmin {
        
    use JournalsModule;
    
    protected $errors = [];
    
    protected function adjusted($type, $name, $value, $settings) {
        $config = Zord::value('admin', ['settings','fields', $type, $name]) ?? [];
        if ($type === 'journal' && $name === 'extraCSS') {
            $validator = new CSSValidator();
            $result = $validator->validateFragment($value);
            if ($result->isValid()) {
                $extra = '/build/css/'.$settings[$type]['context'].'/extra.css';
                $file = Zord::liveFolder(substr(dirname($extra), 1)).basename($extra);
                file_put_contents($file, $value);
            } else {
                $this->errors[] = $this->locale->settings->errors->css;
                return false;
            }
        }
        if ($config['template'] === 'image' && isset($_FILES[$name])) {
            $source = $_FILES[$name]['tmp_name'];
            if (empty($source)) {
                return false;
            }
            $filename = $_FILES[$name]['name'];
            $folder = STORE_FOLDER.'public'.DS.'journals'.DS.'images';
            $context = $settings['journal']['acronym'];
            switch ($name) {
                case 'homepageImage': {
                    $filename = $context.DS.$filename;
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
                case 'coverImage': {
                    $value = $filename;
                    $filename = $context.DS.$filename;
                    break;
                }
                case 'bannerImage': {
                    $value = '/public/journals/images/'.$filename;
                    break;
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
    
    protected function prepareIndex($current, $models) {
        $models = parent::prepareIndex($current, $models);
        if ($current === 'settings' && ($this->params['return'] ?? false) === 'models') {
            $models = Zord::array_merge($models, $this->settings());
        }
        return $models;
    }
        
    public function settings() {
        $_lang = $this->params['_lang'] ?? $this->lang;
        $type = $this->params['type'] ?? 'journal';
        $criteria = $this->params['id'] ?? 'first';
        $name = $this->params['name'] ?? null;
        if (isset($name) && isset(Zord::getLocale('portal')->lang->$name)) {
            $_lang = $name;
            $name = null;
        }
        $return = $this->params['return'] ?? 'data';
        if (!in_array($return, ['data','ui','models']) || !in_array($type, TUNABLE_OBJECT_TYPES)) {
            return $this->error(400);
        }
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
                $journal = (new JournalEntity())->retrieveOne($this->params['journal'] ?? null);
                if ($criteria !== 'first') {
                    $object = (new IssueEntity())->retrieveOne($criteria);
                    if ($return === 'models') {
                        $journal = (new JournalEntity())->retrieveOne($object->journal);
                    }
                }
                $next = 'section';
                break;
            }
            case 'section': {
                $journal = (new JournalEntity())->retrieveOne($this->params['journal'] ?? null);
                $issue   = (new IssueEntity())->retrieveOne($this->params['issue'] ?? null);
                if ($criteria !== 'first') {
                    $object = (new SectionEntity())->retrieveOne($criteria);
                }
                $next = 'paper';
                break;
            }
            case 'paper': {
                $journal = (new JournalEntity())->retrieveOne($this->params['journal'] ?? null);
                $issue   = (new IssueEntity())->retrieveOne($this->params['issue'] ?? null);
                $section = (new SectionEntity())->retrieveOne($this->params['section'] ?? null);
                if ($criteria !== 'first') {
                    $object = (new PaperEntity())->retrieveOne($criteria);
                    if ($return === 'models') {
                        $section = (new SectionEntity())->retrieveOne($object->section);
                        $issue   = (new IssueEntity())->retrieveOne($object->issue);
                        $journal = (new JournalEntity())->retrieveOne($issue->journal);
                    }
                }
                $next = 'author';
                break;
            }
            case 'author': {
                $journal = (new JournalEntity())->retrieveOne($this->params['journal'] ?? null);
                $issue   = (new IssueEntity())->retrieveOne($this->params['issue'] ?? null);
                $section = (new SectionEntity())->retrieveOne($this->params['section'] ?? null);
                $paper   = (new PaperEntity())->retrieveOne($this->params['paper'] ?? null);
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
            $papers = (new PaperEntity())->retrieveAll(['issue' => $issue->id]);
            $done = [];
            foreach ($papers as $_paper) {
                if (!in_array($_paper->section, $done)) {
                    $_section = (new SectionEntity())->retrieveOne($_paper->section);
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
                    $done[] = $_paper->section;
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
            foreach ((new AuthorEntity())->retrieveAll($_criteria['author']) as $_author) {
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
            if ($type !== 'section') {
                $object = (new $class())->retrieveFirst($_criteria[$type]);
            } else {
                $paper = (new PaperEntity())->retrieveFirst(['issue' => $issue->id, 'order' => ['asc' => 'place']]);
                $object = (new $class())->retrieveOne($paper->section);
            }
        }
        $settings['journal'] = JournalsUtils::settings('journal', $journal !== false ? $journal : $object, $_lang);
        if ($journal !== false) {
            $settings['issue'] = JournalsUtils::settings('issue', $issue !== false ? $issue : $object, $_lang);
        }
        if ($journal !== false && $issue !== false) {
            $settings['section'] = JournalsUtils::settings('section', $section !== false ? $section : $object, $_lang);
        }
        if ($issue !== false && $section !== false) {
            $settings['paper'] = JournalsUtils::settings('paper', $paper !== false ? $paper : $object, $_lang);
        }
        if ($paper !== false) {
            $settings['author'] = JournalsUtils::settings('author', $author !== false ? $author : $object, $_lang);
        }
        if ($object === false) {
            return $this->error(404, $criteria === 'first' ? Zord::resolve($this->locale->settings->empty, ['type' => $type], $this->locale) : $this->locale->settings->unknown->$type);
        }
        switch ($type) {
            case 'journal': {
                $journal = $object;
                break;
            }
            case 'issue': {
                $journal = (new JournalEntity())->retrieveOne($object->journal);
                $issue = $object;
                break;
            }
            case 'section': {
                $journal = (new JournalEntity())->retrieveOne($object->journal);
                $section = $object;
                break;
            }
            case 'paper': {
                $journal = (new JournalEntity())->retrieveOne($object->journal);
                $issue   = (new IssueEntity())->retrieveOne($object->issue);
                $paper   = $object;
                break;
            }
            case 'author': {
                $paper   = (new PaperEntity())->retrieveOne($object->paper);
                $journal = (new JournalEntity())->retrieveOne($paper->journal);
                $issue   = (new IssueEntity())->retrieveOne($paper->issue);
                $author  = $object;
                break;
            }
        }
        $url = JournalsUtils::url($journal->context, $issue, $paper ? $paper : ($section->name ?? null));
        $hidden = [];
        foreach ($choices as $_name => $options) {
            $value = $options[0]['value'];
            foreach ($options as $option) {
                if ($option['selected']) {
                    $value = $option['value'];
                    break;
                }
            }
            $hidden[$_name] = $value;
        }
        $fields = Zord::loadConfig('admin', $journal->context)['settings']['fields'][$type];
        $_locale = Zord::loadLocale('admin', $this->lang, $journal->context)['settings']['forms'];
        $select = [
            'choices'  => $choices,
            'current'  => $type,
            'next'     => $next,
            'url'      => $url
        ];
        $form = [
            'hidden'   => $hidden,
            'type'     => $type,
            'id'       => $object->id,
            '_lang'    => $_lang,
            'settings' => $settings,
            'fields'   => $fields,
            '_locale'  => $_locale
        ];
        switch ($return) {
            case 'data': {
                $update = $this->params['update'] ?? [];
                $update = array_merge($update, $_FILES);
                if (!empty($update)) {
                    $fields = array_keys($object->as_array());
                    $_update = [];
                    foreach ($update as $name => $value) {
                        if (in_array($name, $fields)) {
                            $_update[$name] = $value;
                        }
                        $adjusted = $this->adjusted($type, $name, $value, $settings);
                        if ($adjusted) {
                            list($value, $content) = $adjusted;
                            $key = [
                                'object' => $object->id,
                                'name'   => $name,
                                'locale' => $_lang
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
                            switch ($type) {
                                case 'journal': {
                                    $key = $this->_key($type, ['context' => $object->context]);
                                    $_type = $_lang.DS.$type;
                                    if ($this->cache->hasItem($_type, $key)) {
                                        $this->cache->deleteItem($_type, $key);
                                    }
                                    break;
                                }
                                case 'issue': {
                                    $key = $this->_key($type, ['context' => $journal->context, 'issue' => $object]);
                                    $_type = $_lang.DS.$type;
                                    if ($this->cache->hasItem($_type, $key)) {
                                        $this->cache->deleteItem($_type, $key);
                                    }
                                    break;
                                }
                                case 'section': {
                                    $_type = $_lang.DS.'issue';
                                    foreach ((new PaperEntity())->retrieveAll(['section' => $object->id]) as $paper) {
                                        $issue = (new IssueEntity())->retrieveOne($paper->issue);
                                        $key = $this->_key('issue', ['context' => $journal->context, 'issue' => $issue]);
                                        if ($this->cache->hasItem($_type, $key)) {
                                            $this->cache->deleteItem($_type, $key);
                                        }
                                    }
                                    break;
                                }
                                case 'paper': {
                                    $key = $this->_key($type, ['context' => $journal->context, 'issue' => $issue, 'paper' => $object]);
                                    $_type = $_lang.DS.$type;
                                    if ($this->cache->hasItem($_type, $key)) {
                                        $this->cache->deleteItem($_type, $key);
                                    }
                                    break;
                                }
                                case 'author': {
                                    $key = $this->_key('paper', ['context' => $journal->context, 'issue' => $issue, 'paper' => $paper]);
                                    $_type = $_lang.DS.'paper';
                                    if ($this->cache->hasItem($_type, $key)) {
                                        $this->cache->deleteItem($_type, $key);
                                    }
                                    break;
                                }
                            }
                        }
                    }
                    if (!empty($_update)) {
                        (new $class())->update($object->id, $_update);
                    }
                    if (empty($this->errors)) {
                        $result = ['message' => $this->locale->settings->updated];
                    } else {
                        $result = ['errors' => $this->errors];
                    }
                    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? null) === 'XMLHttpRequest' ? $result : $this->redirect($this->baseURL.'/admin'); 
                } else {
                    return $this->_settings($type, $object, $name, $_lang);
                }
            }
            case 'ui': {
                $select = new View('/portal/page/admin/settings/select', $select, $this->controler, 'admin');
                $form   = new View('/portal/page/admin/settings/form',   $form, $this->controler, 'admin');
                return ['select' => $select->render(), 'form' => $form->render()];
            }
            case 'models': {
                return ['select' => $select, 'form' => $form];
            }
        }
    }
    
    public function export() {
        $issue = $this->params['issue'] ?? null;
        if (empty($issue)) {
            return $this->error(400);
        }
        $issue = (new IssueEntity())->retrieveOne($issue);
        if ($issue === false) {
            return $this->error(404);
        }
        $journal = (new JournalEntity())->retrieveOne($issue->journal);
        if ($journal === false) {
            return $this->error(404);
        }
        $short = JournalsUtils::short($journal->context, $issue->volume, $issue->number);
        return $this->download($short.'.json', 'admin', Zord::json_encode(JournalsUtils::export($issue)));
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