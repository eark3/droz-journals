<?php

class JournalsPortal extends Portal {
    
    use JournalsModule;
    
    public function home() {
        if (isset($this->controler->journal)) {
            $issue = (new IssueEntity())->retrieveFirst(['journal' => $this->controler->journal->id, 'order' => ['desc' => 'id']]);
            if ($issue) {
                $this->controler->issue = $issue;
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
                        $this->controler->issue = $issue;
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
            $this->controler->issue = $issue;
            $this->controler->paper = $paper;
            $_paper = $this->_paper($paper, $issue);
            $path = STORE_FOLDER.'journals'.DS.$this->context.DS.$issue->volume.(!empty($issue->number) ? '.'.$issue->number : '').DS.$_paper['short'].'.'.$display;
            if (isset($display) && (!file_exists($path) || !is_file($path))) {
                return $this->error(404);
            }
            $_issue = $this->_issue($issue);
            $_section = $this->_settings('section', $section);
            $ariadne = [
                'home' => '/'.$this->context,
                'archive' => '/'.$this->context.'/issue/archive',
                'issue' => [$_issue['serial'].' : '.$_issue['settings']['title'], '/'.$this->context.'/issue/view/'.$_issue['short']]
            ];
            if (in_array($display, ['html','pdf']) && !JournalsUtils::readable($this->user, $issue, $paper)) {
                return $this->page('login', ['message' => 'warning='.$this->locale->warning->restricted]);
            }
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
                                'status' => JournalsUtils::status($issue, $paper),
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
        $_paper = $this->_settings('paper', $paper);
        $_journal = $this->_settings('journal', $journal);
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
    
    public function settings() {
        $type = $this->params['type'] ?? null;
        $id = $this->params['id'] ?? null;
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
        return $this->_settings($type, $object);
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
        return $this->page('login');
    }
    
}

?>