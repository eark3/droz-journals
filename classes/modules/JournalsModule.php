<?php

trait JournalsModule {
    
    protected $cache = null;
    
    public function configure() {
        $this->cache = Zord::getInstance('Cache', Zord::liveFolder('cache'));
    }
    
    protected function _journal($journal) {
        if ($this->cache->hasItem('journal', $journal->context)) {
            return $this->cache->getItem('journal', $journal->context);
        }
        $settings = $this->_settings('journal', $journal);
        $result = [
            'path'      => '/'.$journal->context,
            'thumbnail' => '/public/journals/images/'.$journal->context.'/'.$settings['homepageImage']['uploadName'],
            'settings'  => $settings
        ];
        $this->cache->setItem('journal', $journal->context, $result);
        return $result;
    }
    
    protected function _issue($issue) {
        $key = JournalsUtils::short($this->context, $issue, null, true);
        $sections = (new SectionEntity())->retrieveAll(['journal' => $this->controler->journal->id, 'order' => ['asc' => 'place']]);
        $papers = (new PaperEntity())->retrieveAll(['issue' => $issue->id, 'order' => [['asc' => 'place'],['asc' => 'id']]]);
        if ($this->cache->hasItem('issue', $key)) {
            $result = $this->cache->getItem('issue', $key);
        } else {
            $copyright = 'Copyright (c) '.date('Y', strtotime($issue->published)).' Librarie Droz';
            $short = JournalsUtils::short($this->context, $issue);
            $serial = 'Vol. '.$issue->volume;
            if ($issue->number) {
                $serial .= ' n° '.$issue->number;
            }
            if ($issue->year) {
                $serial .= ' ('.$issue->year.')';
            }
            $settings = $this->_settings('issue', $issue);
            $cover = '/public/journals/images/'.$this->context.'/'.$settings['coverImage'];
            $link = $this->baseURL.'/issue/view/'.$short;
            $_sections = [];
            foreach ($sections as $section) {
                $_sections[$section->id] = [
                    'settings' => $this->_settings('section', $section)
                ];
            }
            $result = [
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
        foreach ($papers as $paper) {
            $result['sections'][$paper->section]['papers'][] = $this->_paper($paper, $issue);
        }
        foreach ($result['sections'] as $id => $section) {
            if (empty($section['papers'])) {
                unset($result['sections'][$id]);
            }
        }
        return $result;
    }
    
    protected function _paper($paper, $issue) {
        $short = JournalsUtils::short($this->context, $issue, $paper);
        $key = str_replace('-', '_', JournalsUtils::short($this->context, $issue, $paper, true));
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
        foreach ($galleys as $galley) {
            $shop = $galley->type === 'shop';
            foreach ([true, false] as $reader) {
                $access = JournalsUtils::readable($reader, $this->controler->journal, $issue, $paper);
                if ($access !== $shop) {
                    $result['galleys'][$reader][$galley->type] = !empty($galley->path) ? $galley->path : $this->baseURL.'/article/view/'.$short.'/'.$galley->type;
                }
            }
        }
        $this->cache->setItem('paper', $key, $result);
        return $result;
    }
    
    protected function _settings($type, $object) {
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
                            $value = unserialize($value);
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
        return $settings;
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