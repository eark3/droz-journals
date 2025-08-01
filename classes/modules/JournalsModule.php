<?php

trait JournalsModule {
    
    protected $cache = null;
    
    public function configure() {
        $this->cache = Cache::instance();
        $theme = strtolower($this->context);
        $default = strtolower(DEFAULT_THEME);
        $css = $this->checkCSS('bootstrap3', $theme);
        if (!isset($css)) {
            $css = $this->checkCSS('bootstrap3', $default);
        }
        $this->addStyle($css);
        $this->addStyle('/journals/css/common.css');
        $css = $this->checkCSS($theme, 'index');
        if (!isset($css)) {
            $css = $this->checkCSS($default, 'index');
        }
        $this->addStyle($css);
        $extra = '/build/css/'.$this->context.'/extra.css';
        if (file_exists(Zord::liveFolder(substr(dirname($extra), 1)).basename($extra))) {
            $this->addStyle($extra);
        }
        if ($this->context !== 'root') {
            $this->addScript('/journals/js/journal.js');
        }
    }
    
    public function models($models) {
        $_page = $models['page'] ?? null;
        $models['host'] = $this->controler->getHost();
        $models['langs'] = Zord::value('portal', 'lang');
        foreach ((new JournalEntity())->retrieveAll(['order' => ['asc' => 'place']]) as $journal) {
            if (!empty(Zord::value('context', [$journal->context,'url']))) {
                $models['journals'][] = $this->_journal($journal, false);
            }
        }
        if (isset($this->controler->journal)) {
            $models['aside'] = Zord::value('portal', ['aside', 'layout', $this->context]) ?? Zord::value('portal', ['aside', 'layout', 'default']);
            $models['journal'] = $this->_journal($this->controler->journal, false);
        }
        if (isset($_page) && isset($this->locale->pages->$_page) && !isset($models['ariadne'])) {
            $models['ariadne'] = [
                'home'   => '/'.$this->context,
                'active' => $this->locale->pages->$_page
            ];
        }
        return $models;
    }
    
    protected function checkCSS($name, $root) {
        $sourceFolder = Zord::getComponentPath('web'.DS.'themes'.DS).$name;
        $source = $sourceFolder.DS.'styles'.DS.$root.'.less';
        if (!file_exists($source)) {
            return null;
        }
        $target = Zord::liveFolder('build'.DS.'css'.DS.$this->context).$name.'.css';
        if (Zord::needsUpdate($target, Zord::listRecursive($sourceFolder))) {
            $parser = new Less_Parser();
            $parser->parseFile($source, $this->baseURL);
            file_put_contents($target, $parser->getCss());
        }
        return '/build/css/'.$this->context.'/'.$name.'.css';
    }
    
    protected function message($type, $content) {
        if ($type === 'error') {
            $type = 'danger';
        }
        return parent::message($type, $content);
    }
    
    protected function properties($type, $object) {
        foreach (Zord::value('orm', [ucfirst($type).'Entity','fields']) as $field) {
            $result[$field] = $object->$field;
        }
        $result['settings'] = $this->_settings($type, $object);
        return $result;
    }
    
    protected function _key($type, $objects) {
        $context = $objects['context'] ?? $this->context;
        $paper   = $objects['paper']   ?? null;
        $issue   = $objects['issue']   ?? (isset($paper) ? (new IssueEntity())->retrieveOne($paper->issue) : null);
        switch ($type) {
            case 'journal': {
                return $context;
            }
            case 'issue': {
                return str_replace('-', '_', JournalsUtils::short($context, $issue->volume, $issue->number, null, true));
            }
            case 'paper': {
                return str_replace('-', '_', JournalsUtils::short($context, $issue->volume, $issue->number, $paper->pages, true));
            }
        }
    }
    
    protected function _journal($journal, $full = true) {
        $key = $this->_key('journal', ['context' => $journal->context]);
        $type = $this->lang.DS.'journal';
        if ($this->cache->hasItem($type, $key)) {
            $result = $this->cache->getItem($type, $key);
        } else {
            $result = $this->properties('journal', $journal);
            $this->cache->setItem($type, $key, $result);
        }
        if ($full) {
            $issues = (new IssueEntity())->retrieveAll(['journal' => $journal->id, 'order' => ['desc' => 'published']]);
            $_issues = [];
            foreach ($issues as $issue) {
                $_issues[] = $this->_issue($issue, $journal);
            }
            $result['issues'] = $_issues;
        }
        return $result;
    }
    
    protected function _issue($issue, $journal = null) {
        $context = $journal->context ?? $this->context;
        $key = $this->_key('issue', ['issue' => $issue, 'context' => $context]);
        $type = $this->lang.DS.'issue';
        if ($this->cache->hasItem($type, $key)) {
            $result = $this->cache->getItem($type, $key);
        } else {
            $result = $this->properties('issue', $issue);
            $copyright = 'Copyright (c) '.$issue->year.' Librairie Droz';
            $short = JournalsUtils::short($context, $issue->volume, $issue->number);
            $serial = JournalsUtils::serial($issue);
            $cover = '/public/journals/images/'.$context.'/'.$result['settings']['coverImage'];
            $link = Zord::getContextURL($context).'/issue/view/'.$short;
            $_sections = [];
            $papers = (new PaperEntity())->retrieveAll(['issue' => $issue->id, 'order' => [['asc' => 'place'],['asc' => 'pages']]]);
            foreach ($papers as $paper) {
                if (!isset($_sections[$paper->section])) {
                    $section = (new SectionEntity())->retrieveOne($paper->section);
                    if (!empty($section->parent) && !isset($_sections[$section->parent])) {
                        $parent = (new SectionEntity())->retrieveOne($section->parent);
                        $_sections[$section->parent] = $this->properties('section', $parent);
                    }
                    $_sections[$paper->section] = $this->properties('section', $section);
                }
            }
            $result = Zord::array_merge($result, [
                'cover'     => $cover,
                'serial'    => $serial,
                'link'      => $link,
                'short'     => $short,
                'copyright' => $copyright,
                'sections'  => $_sections
            ]);
            $this->cache->setItem($type, $key, $result);
        }
        $papers = (new PaperEntity())->retrieveAll(['issue' => $issue->id, 'order' => [['asc' => 'place'],['asc' => 'pages']]]);
        $dossiers = [];
        foreach ($papers as $paper) {
            $_paper = $this->_paper($paper, $issue, $journal);
            if ($_paper['settings']['title'] === 'Dossier complet') {
                $dossiers[$paper->section] = $_paper;
            } else {
                $result['sections'][$paper->section]['papers'][] = $_paper;
            }
        }
        foreach ($dossiers as $_section => $_paper) {
            $result['sections'][$_section]['papers'][] = $_paper;
        }
        return $result;
    }
    
    protected function _paper($paper, $issue, $journal = null) {
        $context = $journal->context ?? $this->context;
        $short = JournalsUtils::short($context, $issue->volume, $issue->number, $paper->pages);
        $key = $this->_key('paper', ['paper' => $paper, 'context' => $context, 'issue' => $issue]);
        $type = $this->lang.DS.'paper';
        if ($this->cache->hasItem($type, $key)) {
            return $this->cache->getItem($type, $key);
        }
        $result = $this->properties('paper', $paper);
        $result['short'] = $short;
        $authors = (new AuthorEntity())->retrieveAll(['paper' => $paper->id, 'order' => ['asc' => 'place']]);
        foreach ($authors as $author) {
            $_author = $this->properties('author', $author);
            $_author = Zord::array_merge($_author, [
                'first'   => $author->first,
                'last'    => $author->last,
                'name'    => JournalsUtils::name($author),
                'reverse' => JournalsUtils::name($author, true)
            ]);
            $result['authors'][] = $_author;
        }
        if (!empty($result['authors'])) {
            $result['names'] = implode(', ', array_map(function($author) {return $author['name'];}, $result['authors']));
        }
        $galleys = (new GalleyEntity())->retrieveAll(['paper' => $paper->id]);
        foreach ($galleys as $galley) {
            $shop = $galley->type === 'shop';
            foreach ([true, false] as $reader) {
                $access = JournalsUtils::readable($reader, $journal ?? $this->controler->journal, $issue, $paper);
                if ($access !== $shop) {
                    $result['galleys'][$reader][] = $galley->type;
                }
            }
        }
        $this->cache->setItem($type, $key, $result);
        return $result;
    }
    
    protected function _settings($type, $object, $name = null, $lang = null) {
        $locales = [];
        foreach ([$lang, $this->lang ?? null, $this->journal->locale ?? null] as $_locale) {
            if (isset($_locale) && !in_array($_locale, $locales)) {
                $locales[] = $_locale;
            }
        }
        $settings = JournalsUtils::settings($type, $object, $locales);
        return isset($name) ? ($settings[$name] ?? null) : $settings;
    }
    
}

?>