<?php

trait JournalsModule {
    
    protected $cache = null;
    
    public function configure() {
        $this->cache = Cache::instance();
        $this->addStyle('/journals/css/'.$this->context.'/bootstrapTheme.css');
        $this->addStyle('/journals/css/common.css');
        $this->addStyle('/journals/css/'.$this->context.'/layout.css');
        if ($this->context !== 'root') {
            $this->addScript('/journals/js/journal.js');
        }
    }
    
    protected function _journal($journal) {
        if ($this->cache->hasItem('journal', $journal->context)) {
            $result = $this->cache->getItem('journal', $journal->context);
        } else {
            $settings = $this->_settings('journal', $journal);
            $result = [
                'context'  => $journal->context,
                'settings' => $settings
            ];
            $this->cache->setItem('journal', $journal->context, $result);
        }
        $issues = (new IssueEntity())->retrieveAll(['journal' => $journal->id, 'order' => ['desc' => 'published']]);
        $_issues = [];
        foreach ($issues as $issue) {
            $_issues[] = $this->_issue($issue, $journal);
        }
        $result['issues'] = $_issues;
        return $result;
    }
    
    protected function _issue($issue, $journal = null) {
        $context = $journal->context ?? $this->context;
        $key = JournalsUtils::short($context, $issue->volume, $issue->number, null, true);
        if ($this->cache->hasItem('issue', $key)) {
            $result = $this->cache->getItem('issue', $key);
        } else {
            $sections = (new SectionEntity())->retrieveAll(['journal' => $issue->journal, 'order' => ['asc' => 'place']]);
            $copyright = 'Copyright (c) '.date('Y', strtotime($issue->published)).' Librarie Droz';
            $short = JournalsUtils::short($context, $issue->volume, $issue->number);
            $serial = 'Vol. '.$issue->volume;
            if ($issue->number) {
                $serial .= ' n° '.$issue->number;
            }
            if ($issue->year) {
                $serial .= ' ('.$issue->year.')';
            }
            $settings = $this->_settings('issue', $issue);
            $cover = '/public/journals/images/'.$context.'/'.$settings['coverImage'];
            $link = $this->baseURL.'/issue/view/'.$short;
            $_sections = [];
            foreach ($sections as $section) {
                $_sections[$section->id] = [
                    'name'     => $section->name,
                    'settings' => $this->_settings('section', $section)
                ];
            }
            $result = [
                'volume'    => $issue->volume,
                'number'    => $issue->number,
                'cover'     => $cover,
                'serial'    => $serial,
                'open'      => $issue->open,
                'published' => $issue->published,
                'link'      => $link,
                'short'     => $short,
                'copyright' => $copyright,
                'settings'  => $settings,
                'sections'  => $_sections
            ];
            $this->cache->setItem('issue', $key, $result);
        }
        $papers = (new PaperEntity())->retrieveAll(['issue' => $issue->id, 'order' => [['asc' => 'place'],['asc' => 'id']]]);
        foreach ($papers as $paper) {
            $result['sections'][$paper->section]['papers'][] = $this->_paper($paper, $issue, $journal);
        }
        foreach ($result['sections'] as $id => $section) {
            if (empty($section['papers'])) {
                unset($result['sections'][$id]);
            }
        }
        return $result;
    }
    
    protected function _paper($paper, $issue, $journal = null) {
        $context = $journal->context ?? $this->context;
        $short = JournalsUtils::short($context, $issue->volume, $issue->number, $paper->pages);
        $key = str_replace('-', '_', JournalsUtils::short($context, $issue->volume, $issue->number, $paper->pages, true));
        if ($this->cache->hasItem('paper', $key)) {
            return $this->cache->getItem('paper', $key);
        }
        $result = [
            'id'       => $paper->id,
            'pages'    => $paper->pages,
            'status'   => $paper->status,
            'short'    => $short,
            'settings' => $this->_settings('paper', $paper)
        ];
        $authors = (new AuthorEntity())->retrieveAll(['paper' => $paper->id, 'order' => ['asc' => 'place']]);
        foreach ($authors as $author) {
            $result['authors'][] = [
                'name'     => JournalsUtils::name($author),
                'email'    => $author->email,
                'settings' => $this->_settings('author', $author)
            ];
        }
        if (!empty($result['authors'])) {
            $result['names'] = implode(', ', array_map(function($author) {return $author['name'];}, $result['authors']));
        }
        $galleys = (new GalleyEntity())->retrieveAll(['paper' => $paper->id]);
        //$journal = $this->controler->journal ?? (new JournalEntity())->retrieveOne($issue->journal);
        foreach ($galleys as $galley) {
            $shop = $galley->type === 'shop';
            foreach ([true, false] as $reader) {
                $access = JournalsUtils::readable($reader, $journal ?? $this->controler->journal, $issue, $paper);
                if ($access !== $shop) {
                    $result['galleys'][$reader][] = $galley->type;
                }
            }
        }
        $this->cache->setItem('paper', $key, $result);
        return $result;
    }
    
    protected function message($type, $content) {
        if ($type === 'error') {
            $type = 'danger';
        }
        return parent::message($type, $content);
    }
    
    protected function _settings($type, $object, $name = null) {
        $locales = [];
        foreach ([$this->lang, $this->controler->journal->locale ?? 'none', DEFAULT_LANG] as $_locale) {
            if ($_locale !== 'none' && !in_array($_locale, $locales)) {
                $locales[] = $_locale;
            }
        }
        $settings = [];
        $criteria = ['object' => $object->id, 'order' => ['asc' => 'name']];
        foreach ($locales as $_locale) {
            $criteria['locale'] = $_locale;
            $entities = (new SettingEntity($type))->retrieveAll($criteria);
            foreach ($entities as $entity) {
                if (!isset($settings[$entity->name])) {
                    $value = $entity->value;
                    switch ($entity->content) {
                        case 'object': {
                            $value = unserialize(base64_decode($value));
                            break;
                        }
                        case 'bool': {
                            $value = (boolean) $value;
                            break;
                        }
                        case 'int': {
                            $value = (int) $value;
                            break;
                        }
                    }
                    $settings[$entity->name] = $value;
                }
            }
        }
        return isset($name) ? ($settings[$name] ?? null) : $settings;
    }
    
    public function models($models) {
        $_page = $models['page'] ?? null;
        foreach ((new JournalEntity())->retrieveAll(['order' => ['asc' => 'place']]) as $journal) {
            $models['journals'][] = $this->_journal($journal);
        }
        if (isset($this->controler->journal)) {
            $models['layout'] = Zord::value('layout', $this->context) ?? Zord::value('layout', 'default');
            $models['journal'] = $this->_journal($this->controler->journal);
        }
        if (isset($_page) && isset($this->locale->pages->$_page) && !isset($models['ariadne'])) {
            $models['ariadne'] = [
                'home'   => '/'.$this->context,
                'active' => $this->locale->pages->$_page
            ];
        }
        return $models;
    }
    
}

?>