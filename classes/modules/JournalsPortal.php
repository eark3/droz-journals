<?php

class JournalsPortal extends Portal {
    
    protected $cache = null;
    
    protected function _journal($context, $locale) {
        if ($this->cache->hasItem('journal', $context)) {
            return $this->cache->getItem('journal', $context);
        }
        $journal = (new JournalEntity())->retrieveOne($context);
        $settings = $this->_settings('journal', $journal, $locale);
        $result = [
            'path'      => '/'.$context,
            'thumbnail' => '/public/journals/images/'.$context.'/'.$settings['homepageImage']['uploadName'],
            'settings'  => $settings
        ];
        $this->cache->setItem('journal', $context, $result);
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
            $settings = $this->_settings('issue', $issue, $this->lang);
            $cover = '/public/journals/images/'.$this->context.'/'.$settings['coverImage'];
            $link = $this->baseURL.'/issue/view/'.$short;
            $_sections = [];
            foreach ($sections as $section) {
                $_sections[$section->id] = [
                    'settings' => $this->_settings('section', $section, $this->lang)
                ];
            }
            $result = [
                'cover'     => $cover,
                'serial'    => $serial,
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
        $short = JournalsUtils::short($this->context, $issue, $paper, true);
        $key = str_replace('-', '_', $short);
        if ($this->cache->hasItem('paper', $key)) {
            return $this->cache->getItem('paper', $key);
        }
        $result = [
            'id'       => $paper->id,
            'pages'    => $paper->pages,
            'status'   => $paper->status,
            'short'    => $short,
            'settings' => $this->_settings('paper', $paper, $this->lang)
        ];
        $authors = (new AuthorEntity())->retrieveAll(['paper' => $paper->id, 'order' => ['asc' => 'place']]);
        foreach ($authors as $author) {
            $result['authors'][] = [
                'name'     => JournalsUtils::name($author),
                'email'    => $author->email,
                'settings' => $this->_settings('author', $author, $this->lang)
            ];
        }
        if (!empty($result['authors'])) {
            $result['names'] = implode(', ', array_map(function($author) {return $author['name'];}, $result['authors']));
        }
        $galleys = (new GalleyEntity())->retrieveAll(['paper' => $paper->id]);
        foreach ($galleys as $galley) {
            $access = $this->user->isConnected() || $issue->open < date('Y-m-d') || $paper->status === 'free';
            $shop = $galley->type === 'shop';
            if ($access !== $shop) {
                $result['galleys'][$galley->type] = !empty($galley->path) ? $galley->path : $this->baseURL.'/article/view/'.$short.'/'.$galley->type;
            }
        }
        $this->cache->setItem('paper', $key, $result);
        return $result;
    }
    
    protected function _settings($type, $object, $locale) {
        $locales = [];
        foreach ([$locale, $this->controler->journal->locale ?? null, DEFAULT_LANG] as $_locale) {
            if (!empty($_locale) && !in_array($_locale, $locales)) {
                $locales[] = $_locale;
            }
        }
        $settings = [];
        $criteria = ['object' => $object->id];
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
    
    public function configure() {
        $this->cache = Zord::getInstance('Cache', Zord::liveFolder('cache'));
    }
    
    public function models($models) {
        foreach ((new JournalEntity())->retrieveAll(['order' => ['asc' => 'place']]) as $journal) {
            $models['journals'][] = $this->_journal($journal->context, $this->lang);
        }
        if (isset($this->controler->journal)) {
            $models['layout'] = Zord::value('layout', $this->context) ?? Zord::value('layout', 'default');
            $models['journal'] = $this->_journal($this->context, $this->lang);
        }
        return $models;
    }
    
    public function home() {
        if (isset($this->controler->journal)) {
            $issue = (new IssueEntity())->retrieveFirst(['journal' => $this->controler->journal->id, 'order' => ['desc' => 'id']]);
            if ($issue) {
                return $this->page('home', ['issue' => $this->_issue($issue)]);
            }
        }
        return parent::home();
    }
    
    public function issue() {
        if (isset($this->controler->journal)) {
            $page = $this->params['page'] ?? null;
            $models = false;
            $ariadne = ['home' => '/'.$this->context];
            switch ($page) {
                case 'archive': {
                    $ariadne['active'] = 'archive';
                    $entities = (new IssueEntity())->retrieveAll(['journal' => $this->controler->journal->id, 'order' => ['desc' => 'published']]);
                    $issues = [];
                    foreach ($entities as $issue) {
                        $issues[] = $this->_issue($issue);
                    }
                    if (!empty($issues)) {
                        $models = ['issues' => $issues];
                    }
                    break;
                }
                case 'current':
                case 'view': {
                    $ariadne['archive'] = '/'.$this->context.'/issue/archive';
                    $issue = false;
                    if ($page === 'current') {
                        $issue = (new IssueEntity())->retrieveFirst(['journal' => $this->controler->journal->id, 'order' => ['desc' => 'id']]);
                    } else {
                        $issue = $this->params['issue'] ?? null;
                        if ($issue) {
                            $issue = (new IssueEntity())->retrieveOne($issue);
                        }
                    }
                    if ($issue) {
                        $page = 'issue';
                        $issue = $this->_issue($issue);
                        $ariadne['active'] = $issue['serial'].' : '.$issue['settings']['title'];
                        $models = ['issue' => $issue];
                    } else {
                        return $this->error(404);
                    }
                    break;
                }
            }
            if ($models !== false) {
                return $this->page($page, array_merge($models, ['ariadne' => $ariadne]));
            }
        }
        return $this->home();
    }
    
    public function article() {
        if (isset($this->controler->journal)) {
            $page = $this->params['page'] ?? null;
            $paper = $this->params['paper'] ?? null;
            $display = $this->params['display'] ?? null;
            $models = false;
            if (!isset($paper)) {
                return $this->error(404);
            }
            $paper = (new PaperEntity())->retrieveOne($paper);
            if ($paper === false) {
                return $this->error(404);
            }
            $issue = (new IssueEntity())->retrieveOne($paper->issue);
            if ($issue === false) {
                return $this->error(404);
            }
            $section = (new SectionEntity())->retrieveOne($paper->section);
            if ($section === false) {
                return $this->error(404);
            }
            $_paper = $this->_paper($paper, $issue);
            $path = STORE_FOLDER.'journals'.DS.$this->context.DS.$issue->volume.(!empty($issue->number) ? '.'.$issue->number : '').DS.$_paper['short'].'.'.$display;
            if (isset($display) && (!file_exists($path) || !is_file($path))) {
                return $this->error(404);
            }
            $_issue = $this->_issue($issue);
            $_section = $this->_settings('section', $section, $this->lang);
            $ariadne = [
                'home' => '/'.$this->context,
                'archive' => '/'.$this->context.'/issue/archive',
                'issue' => [$_issue['serial'].' : '.$_issue['settings']['title'], '/'.$this->context.'/issue/view/'.$_issue['short']]
            ];
            switch ($page) {
                case 'view': {
                    if (in_array($display, ['html','pdf'])) {
                        $view = 'display';
                        $ariadne['section'] = [$_section['title'], '/'.$this->context.'/issue/view/'.$_issue['short'].'#'.$section->id];
                        $ariadne['active'] = [$_paper['settings']['title'], '/'.$this->context.'/article/view/'.$_paper['short']];
                    } else {
                        $page = 'article';
                        $ariadne['active'] = $_section['title'];
                    }
                    $models = [
                        'paper' => $_paper,
                        'issue' => $_issue,
                        'display' => $display,
                        'title' => $_paper['settings']['title'].' | '.$_issue['serial']
                    ];
                    break;
                }
                case 'download': {
                    switch ($display) {
                        case 'html': {
                            $view = 'download';
                            $models = [
                                'paper' => $_paper,
                                'issue' => $_issue,
                                'content' => file_get_contents($path),
                                'status' => ($issue->open < date('Y-m-d') || $paper->status === 'free') ? 'free' : 'subscription',
                                'section' => $_section['title']
                            ];
                            break;
                        }
                        case 'pdf': {
                            return $this->send($path);
                        }
                        default: {
                            return $this->error(404);
                        }
                    }
                }
            }
        }
        if ($models !== false) {
            $models = array_merge($models, ['ariadne' => $ariadne]);
            if (isset($view)) {
                return $this->view('/portal/'.$view, $models);
            } else if (isset($page)) {
                return $this->page($page, $models);
            }
        }
        return $this->home();
    }
    
    public function quote() {
        if (!isset($this->controler->journal)) {
            return $this->error(400, "Not a journal");
        }
        $output = $this->params['output'] ?? null;
        $styles = array_keys(Zord::getConfig('quote'));
        if (!in_array($output, $styles)) {
            return $this->error(400, "Unknown output");
        }
        $styles = Zord::value('quote', $output);
        if (!is_array($styles) || !Zord::is_associative($styles)) {
            return $this->error(400, "Not a style");
        }
        $style = $this->params['style'] ?? null;
        if (!isset($style) || !isset($styles[$style])) {
            return $this->error(400, "Unknown style");
        }
        $paper = $this->params['paper'] ?? null;
        if (!isset($paper)) {
            return $this->error(400, "Missing paper");
        }
        $paper = (new PaperEntity())->retrieveOne($paper);
        if ($paper === false) {
            return $this->error(404, "Unknown paper");
        }
        $issue = (new IssueEntity())->retrieveOne($paper->issue);
        if ($issue === false) {
            return $this->error(404, "Unknown issue");
        }
        $section = (new SectionEntity())->retrieveOne($paper->section);
        if ($section === false) {
            return $this->error(404, "Unknown section");
        }
        $this->controler->issue = $issue;
        $this->controler->section = $section;
        $this->controler->paper = $paper;
        foreach ((new AuthorEntity())->retrieveAll(['paper' => $paper->id]) as $author) {
            $this->controler->authors[] = JournalsUtils::name($author);
        }
        switch ($output) {
            case 'format': {
                $this->response = 'DATA';
                break;
            }
            case 'download': {
                $view = new View('/portal/quote/'.$style, [], $this->controler);
                $view->setMark(false);
                $content = $view->render();
                return isset($content) ? $this->download(
                    JournalsUtils::short($this->context, $issue, $paper).'.'.Zord::value('quote', ['download',$style,'extension']),
                    null,
                    $content
                ) : $this->error(501);
            }
        }
    }
    
    public function reference() {
        $paper = $this->params['paper'];
        if (!isset($paper)) {
            return $this->error(400);
        }
        $paper = (new PaperEntity())->retrieveOne($paper);
        if ($paper === false) {
            return $this->error(404);
        }
        $issue = (new IssueEntity())->retrieveOne($paper->issue);
        if ($issue === false) {
            return $this->error(404);
        }
        $journal = (new JournalEntity())->retrieveOne($issue->journal);
        if ($journal === false) {
            return $this->error(404);
        }
        $now = time();
        $issued = strtotime($issue->published);
        $_paper = $this->_settings('paper', $paper, $this->lang);
        $_journal = $this->_settings('journal', $journal, $this->lang);
        $short = JournalsUtils::short($this->context, $issue, $paper);
        $reference = [
            'type'            => 'article-journal',
            'id'              => $short,
            'title'           => $_paper['title'],
            'volume'          => $issue->volume,
            'container-title' => $_journal['name'],
            'page'            => JournalsUtils::pages($paper),
            'accessed'        => ["date-parts" => [[date('Y', $now), date('m', $now), date('d', $now)]]],
            'issued'          => ["date-parts" => [[date('Y', $issued), date('m', $issued), date('d', $issued)]]]
        ];
        if ($issue->number) {
            $reference['issue'] = $issue->number;
        }
        if (isset($_paper['pub-id::doi'])) {
            $reference['DOI'] = $_paper['pub-id::doi'];
        } else {
            $reference['URL'] = $this->baseURL.'/article/view/'.$short;
        }
        $authors = (new AuthorEntity())->retrieveAll(['paper' => $paper->id]);
        foreach ($authors as $author) {
            $reference['author'][] = [
                'given'  => $author->first,
                'family' => $author->last
            ];
        }
        return $reference;
    }
}

?>