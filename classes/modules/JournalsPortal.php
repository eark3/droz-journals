<?php

class JournalsPortal extends Portal {
    
    use JournalsModule;
    
    protected function published() {
        return [
            'journal'   => $this->controler->journal->id,
            'published' => ['<=' => $this->controler->getHost() !== PRODUCTION_HOST ? '9999-12-31' : date('Y-m-d')],
            'order'     => [['desc' => 'volume'],['desc' => 'number']]
        ];
    }
    
    public function home() {
        if (isset($this->controler->journal)) {
            $issue = (new IssueEntity())->retrieveFirst($this->published());
            if ($issue) {
                $this->controler->issue = $issue;
                return $this->page('home', [
                    'issue' => $this->_issue($issue),
                    'edit'  => [
                        'type'  => 'journal',
                        'id'    => $this->controler->journal->id,
                        'short' => JournalsUtils::short($this->controler->journal->context)
                    ]
                ]);
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
                    $start = $this->params['start'] ?? 0;
                    $rows  = $this->params['rows']  ?? SEARCH_PAGE_DEFAULT_SIZE;
                    $ariadne['active'] = 'archive';
                    $criteria = $this->published();
                    $entities = (new IssueEntity())->retrieveAll($criteria);
                    $found = count($entities);
                    $criteria['offset'] = $start;
                    $criteria['limit']  = $rows;
                    $entities = (new IssueEntity())->retrieveAll($criteria);
                    $issues = [];
                    foreach ($entities as $issue) {
                        $issues[] = $this->_issue($issue);
                    }
                    $models = [
                        'issues' => $issues,
                        'start'  => $start,
                        'rows'   => $rows,
                        'found'  => $found,
                        'count'  => count($issues)
                    ];
                    if ($this->params['xhr'] ?? false) {
                        $results = new View('/portal/page/archive', $models, $this->controler, $this->locale);
                        $this->response = 'DATA';
                        return $results->render();
                    }
                    break;
                }
                case 'current':
                case 'view': {
                    $ariadne['archive'] = '/'.$this->context.'/issue/archive';
                    $issue = false;
                    if ($page === 'current') {
                        $issue = (new IssueEntity())->retrieveFirst($this->published());
                    } else {
                        $issue = $this->params['issue'] ?? null;
                        if ($issue) {
                            $issue = Zord::value('mapping', ['issues',$issue]) ?? $issue;
                            $issue = (new IssueEntity())->retrieveOne($issue);
                        }
                    }
                    if ($issue) {
                        $this->controler->issue = $issue;
                        $page = 'issue';
                        $issue = $this->_issue($issue);
                        $ariadne['active'] = $issue['serial'].(!empty($issue['settings']['title']) ? ' : '.$issue['settings']['title'] : '');
                        $short = JournalsUtils::short(
                            $this->controler->journal->context,
                            $this->controler->issue->volume,
                            $this->controler->issue->number
                        );
                        $models = [
                            'issue' => $issue,
                            'edit'  => [
                                'type'  => 'issue',
                                'id'    => $this->controler->issue->id,
                                'short' => $short
                            ],
                            'export' => [
                                'short' => $short
                            ]
                        ];
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
            $resource = $this->params['resource'] ?? null;
            $models = false;
            if (!isset($paper)) {
                return $this->error(404);
            }
            $paper = Zord::value('mapping', ['papers',$paper]) ?? $paper;
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
            $this->controler->issue = $issue;
            $this->controler->paper = $paper;
            $path = JournalsUtils::path($this->context, $issue->volume, $issue->number, $paper->pages, $display, $resource);
            if (isset($display) && (!file_exists($path) || !is_file($path))) {
                return $this->error(404);
            }
            $_issue = $this->_issue($issue);
            $_section = $this->_settings('section', $section);
            $_paper = $this->_paper($paper, $issue);
            $ariadne = [
                'home' => '/'.$this->context,
                'archive' => '/'.$this->context.'/issue/archive',
                'issue' => [$_issue['serial'].(!empty($_issue['settings']['title']) ? ' : '.$_issue['settings']['title'] : ''), '/'.$this->context.'/issue/view/'.$_issue['short']]
            ];
            if (in_array($display, ['html','pdf']) && !JournalsUtils::readable($this->user, $this->controler->journal, $issue, $paper)) {
                return $this->page('login', [
                    'message' => $this->message('warning', $this->locale->warning->restricted),
                    'success' => $this->pathURL
                ]);
            }
            switch ($page) {
                case 'view': {
                    if (in_array($display, ['html','pdf'])) {
                        $view = 'display';
                        array_splice($ariadne, 1, 1);
                        $ariadne['active'] = [$_paper['settings']['title'], '/'.$this->context.'/article/view/'.$_paper['short']];
                    } else {
                        $page = 'article';
                        if ($_paper['settings']['title'] !== 'Dossier complet') {
                            $ariadne['section'] = [$_section['title'], '/'.$this->context.'/issue/view/'.$_issue['short'].'#'.$_section['name']];
                        }
                        $others = [];
                        foreach ($_paper['authors'] ?? [] as $author) {
                            foreach ((new AuthorEntity())->retrieveAll([
                                'first' => $author['first'],
                                'last'  => $author['last'],
                            ]) as $_author) {
                                if ($_author->paper !== $paper->id && !isset($others[$_author->paper])) {
                                    $__paper = (new PaperEntity())->retrieveOne($_author->paper);
                                    $__issue = (new IssueEntity())->retrieveOne($__paper->issue);
                                    $__journal = (new JournalEntity())->retrieveOne($__issue->journal);
                                    $other = [
                                        'paper' => [
                                            'title' => $this->_settings('paper', $__paper, 'title'),
                                            'url'   => JournalsUtils::url($__journal->context, $__issue, $__paper),
                                            'views' => $__paper->views
                                        ],
                                        'issue' => [
                                            'title' => $this->_settings('journal', $__journal, 'name').': '.JournalsUtils::serial($__issue).': '.$this->_settings('issue', $__issue, 'title'),
                                            'url'   => JournalsUtils::url($__journal->context, $__issue)
                                        ]
                                    ];
                                    foreach ((new AuthorEntity())->retrieveAll(['paper' => $__paper->id]) as $__author) {
                                        $other['authors'][] = JournalsUtils::name($__author);
                                    }
                                    $others[$_author->paper] = $other;
                                }
                            }
                        }
                        usort($others, function($first, $second) {
                            return $second['paper']['views'] <=> $first['paper']['views'];
                        });
                        $others = array_slice($others, 0, MAX_MOST_READ);
                    }
                    $models = [
                        'paper'   => $_paper,
                        'issue'   => $_issue,
                        'display' => $display,
                        'title'   => $_paper['settings']['title'].' | '.$_issue['serial'],
                        'others'  => $others ?? null,
                        'edit'  => [
                            'type'  => 'paper',
                            'id'    => $this->controler->paper->id,
                            'short' => JournalsUtils::short(
                                $this->controler->journal->context,
                                $this->controler->issue->volume,
                                $this->controler->issue->number,
                                $this->controler->paper->pages
                            )
                        ]
                    ];
                    break;
                }
                case 'download': {
                    if (in_array($display, ['html','pdf','images','css'])) {
                        (new PaperEntity())->update($paper->id, ['views' => $paper->views + 1]);
                    }
                    switch ($display) {
                        case 'html': {
                            $view = 'download';
                            $models = [
                                'paper' => $_paper,
                                'issue' => $_issue,
                                'content' => file_get_contents($path),
                                'status' => JournalsUtils::status($issue, $paper),
                                'section' => $_section['title']
                            ];
                            break;
                        }
                        case 'pdf':
                        case 'css':
                        case 'images':{
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
                $short = JournalsUtils::short($this->context, $issue->volume, $issue->number, $paper->pages);
                $_journal = $this->_journal($this->controler->journal);
                $_issue = $this->_issue($issue, $this->controler->journal);
                $_section = $this->_settings('section', $section, 'title');
                $_paper = $this->_paper($paper, $issue, $this->controler->journal);
                $view = new View('/portal/quote/'.$style, [
                    'journal' => $_journal,
                    'issue'   => $_issue,
                    'section' => $_section,
                    'paper'   => $_paper
                ], $this->controler);
                $view->setMark(false);
                $content = $view->render();
                return isset($content) ? $this->download(
                    $short.'.'.Zord::value('quote', ['download',$style,'extension']),
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
        $_paper = $this->_settings('paper', $paper);
        $_journal = $this->_settings('journal', $journal);
        $short = JournalsUtils::short($this->context, $issue->volume, $issue->number, $paper->pages);
        $reference = [
            'type'            => 'article-journal',
            'id'              => $short,
            'title'           => $_paper['title'],
            'volume'          => $issue->volume,
            'container-title' => str_replace('<br/>', ' ', $_journal['name']),
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
    
    public function info() {
        $content = $this->params['content'] ?? null;
        if (empty($content)) {
            return $this->error(404, 'missing content');
        }
        $title = $this->locale->pages->$content ?? null;
        $settings = $this->_settings('journal', $this->controler->journal);
        if (!isset($settings[$content])) {
            return $this->error(404, 'no setting');
        }
        return $this->page('info', [
            'title'   => $title,
            'content' => $settings[$content],
            'ariadne' => [
                'home'   => '/'.$this->context,
                'active' => $title ?? $this->locale->ariadne->$content
            ]
        ]);
    }
    
    public function login() {
        $step = $this->params['step'] ?? null;
        $account = Zord::getInstance('Account', $this->controler);
        switch ($step) {
            case 'signIn': {
                $account->setParam('login', $this->params['username'] ?? null);
                return $account->connect();
            }
            case 'signOut': {
                return $account->disconnect();
            }
            case 'requestResetPassword': {
                return $account->reset();
            }
        }
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        if (!empty($referer) && substr($referer, 0, strlen($this->baseURL)) === $this->baseURL) {
            $path = explode('/', substr($referer, strlen($this->baseURL)));
            if (!empty($path) && count($path) > 1 && empty($path[0]) && in_array($path[1], ['issue','article','info','page'])) {
                return $this->page('login', ['success' => $referer]);
            }
        }
        return $this->page('login');
    }
    
    public function search() {
        $search  = $this->params['search']  ?? uniqid();
        if ($_SESSION['search'][$search] ?? false) {
            foreach ($_SESSION['search'][$search] as $key => $value) {
                $this->params[$key] = $value;
            }
        }
        $query   = $this->params['query']   ?? null;
        $authors = $this->params['authors'] ?? null;
        $from    = $this->params['from']    ?? null;
        $to      = $this->params['to']      ?? null;
        $start   = $this->params['start']   ?? 0;
        $rows    = $this->params['rows']    ?? SEARCH_PAGE_DEFAULT_SIZE;
        $results = [];
        $models  = ['filters' => [
            'query'   => $query,
            'authors' => $authors,
            'from'    => $from,
            'to'      => $to
        ]];
        if (!empty($query)) {
            $query = Zord::collapse($query, false);
        }
        $filters = ['journal' => $this->context];
        if (!empty($from)) {
            $filters['date']['from'] = $from;
        }
        if (!empty($to)) {
            $filters['date']['to'] = $to;
        }
        if (!empty($authors)) {
            $filters['authors'] = '*'.Zord::collapse($authors, false).'*';
        }
        $found = 0;
        if (!empty($query) || !empty($authors)) {
            list($found, $documents) = Store::search([
                'query'   => $query,
                'filters' => $filters,
                'start'   => $start,
                'rows'    => $rows
            ]);
            if ($found > 0) {
                foreach ($documents as $document) {
                    $paper = (new PaperEntity())->retrieveOne($document['short_s']);
                    if ($paper !== false) {
                        $issue = (new IssueEntity())->retrieveOne($paper->issue);
                        if ($issue !== false) {
                            $journal = (new JournalEntity())->retrieveOne($issue->journal);
                            if ($issue !== false) {
                                $results[] = [
                                    'paper' => $this->_paper($paper, $issue),
                                    'issue' => $this->_issue($issue, $journal)
                                ];
                            } else {
                                $found--;
                            }
                        } else {
                            $found--;
                        }
                    } else {
                        $found--;
                    }
                }
            }
        }
        if (count($results) > 0) {
            $models['start']  = $start;
            $models['rows']   = $rows;
            $models['found']  = $found;
            $models['count']  = count($results);
            $models['papers'] = $results;
            $models['search'] = $search;
            if ($_SESSION['search'][$search] ?? false) {
                $results = new View('/portal/page/search/results', $models, $this->controler, $this->locale);
                $this->response = 'DATA';
                return $results->render();
            } else {
                foreach (['query','authors','from','to','rows'] as $key) {
                    $_SESSION['search'][$search][$key] = $this->params[$key] ?? null;
                }
            }
        } else {
            $models['message'] = $this->message('info', $this->locale->search->results->none);
        }
        return $this->page('search', $models);
    }
    
    public function data() {
        $ean   = $this->params['ean']   ?? null;
        $scope = $this->params['scope'] ?? null;
        if (empty($ean) || empty($scope) || !in_array($scope, ['params','parts'])) {
            return $this->error(406);
        }
        $issue = (new IssueEntity())->retrieveOne($ean);
        if ($issue === false) {
            return [];
        }
        if (empty($issue->ean)) {
            return $this->error(406);
        }
        $journal = (new JournalEntity())->retrieveOne($issue->journal);
        if ($journal === false) {
            return $this->error(404);
        }
        $short = JournalsUtils::short($journal->context, $issue->volume, $issue->number);
        switch ($scope) {
            case 'params': {
                return [
                    'ean'   => $ean,
                    'path'  => $journal->context,
                    'short' => $short
                ];
            }
            case 'parts': {
                $papers = [];
                foreach ((new PaperEntity())->retrieveAll(['issue' => $issue->id, 'status' => 'subscription']) as $paper) {
                    $galley = (new GalleyEntity())->retrieveOne(['paper' => $paper->id, 'type' => 'shop']);
                    if ($galley !== false) {
                        $short = JournalsUtils::short($journal->context, $issue->volume, $issue->number, $paper->pages);
                        list($context,$number,$pages) = explode('_', $short);
                        $settings = $this->_settings('paper', $paper);
                        $tokens = explode('-', $pages);
                        $start = $tokens[0];
                        $end = count($tokens) > 1 ? $tokens[1] : $start;
                        $file = $short.'.pdf';
                        $papers[$short] = [
                            'chapter'  => $short,
                            'title'    => $settings['title'],
                            'subtitle' => $settings['subtitle'] ?? null,
                            'start'    => $start,
                            'end'      => $end,
                            'file'     => $short.'.pdf',
                            'date'     => $issue->published,
                            'open'     => $issue->open,
                            'source'   => STORE_FOLDER.'journals'.DS.$context.DS.$number.DS.$file,
                            'type'     => $settings['title'] === 'Dossier complet' ? 'dossier' : 'article'
                        ];
                        foreach ((new AuthorEntity())->retrieveAll(['paper' => $paper->id]) as $author) {
                            $papers[$short]['authors'][] = [
                                'firstName' => $author->first,
                                'lastName'  => $author->last
                            ];
                        }
                        $entities = (new SettingEntity('paper'))->retrieveAll([
                            'object' => $paper->id,
                            'name'   => 'abstract'
                        ]);
                        foreach ($entities as $setting) {
                            $papers[$short]['abstracts'][$setting->locale] = $setting->value;
                        }
                    }
                }
                return $papers;
            }
        }
    }
    
    public function issues() {
        $for = $this->params['for'] ?? 'shop';
        $list = [];
        foreach ((new IssueEntity())->retrieveAll() as $issue) {
            switch ($for) {
                case 'shop': {
                    if (!empty($issue->ean)) {
                        $list[] = $issue->ean;
                    }
                    break;
                }
            }
        }
        return $list;
    }
}

?>