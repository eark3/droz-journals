<?php

class JournalsPortal extends Portal {
    
    protected function _issue($issue, $full = true) {
        $copyright = 'Copyright (c) '.date('Y', strtotime($issue->published)).' Librarie Droz';
        $short = JournalsUtils::short($this->context, $issue);
        $serial = 'Vol. '.$issue->volume;
        if ($issue->number) {
            $serial .= ' n° '.$issue->number;
        }
        if ($issue->year) {
            $serial .= ' ('.$issue->year.')';
        }
        $settings = JournalsUtils::settings($issue, $this->lang, $this->controler->journal);
        $cover = '/public/journals/images/'.$this->context.'/'.$settings['coverImage'];
        $link = $this->baseURL.'/issue/view/'.$short;
        if ($full) {
            $sections = (new SectionEntity())->retrieveAll(['journal' => $this->controler->journal->id, 'order' => ['asc' => 'place']]);
            $papers = (new PaperEntity())->retrieveAll(['issue' => $issue->id, 'order' => [['asc' => 'place'],['asc' => 'id']]]);
            $_sections = [];
            foreach ($sections as $section) {
                $_sections[$section->id] = [
                    'title' => $section->title
                ];
            }
            foreach ($papers as $paper) {
                $_sections[$paper->section]['papers'][] = $this->_paper($paper, $issue);
            }
            foreach ($_sections as $id => $section) {
                if (empty($section['papers'])) {
                    unset($_sections[$id]);
                }
            }
        }
        $result = [
            'title'     => $settings['title'],
            'cover'     => $cover,
            'serial'    => $serial,
            'published' => $issue->published,
            'link'      => $link,
            'short'     => $short,
            'copyright' => $copyright
        ];
        if ($full) {
            $result = array_merge($result, [
                'description' => $settings['description'] ?? null,
                'sections'    => $_sections
            ]);
        }
        return $result;
    }
    
    protected function _paper($paper, $issue) {
        $short = JournalsUtils::short($this->context, $issue, $paper);
        $settings = JournalsUtils::settings($paper, $this->lang, $this->controler->journal);
        $result = [
            'id'       => $paper->id,
            'title'    => $paper->title,
            'subtitle' => $paper->subtitle,
            'pages'    => $paper->pages,
            'status'   => $paper->status,
            'doi'      => $paper->doi,
            'short'    => $short
        ];
        $authors = (new AuthorEntity())->retrieveAll(['paper' => $paper->id, 'order' => ['asc' => 'place']]);
        foreach ($authors as $author) {
            $_author = [
                'name' => JournalsUtils::name($author),
                'email' => $author->email,
                'affiliation' => $author->affiliation
            ];
            $settings = (new SettingEntity('author'))->retrieveAll([
                'object' => $author->id,
                'locale' => $this->lang
            ]);
            foreach ($settings as $setting) {
                $_author[$setting->name] = $setting->value;
            }
            $result['authors'][] = $_author;
        }
        if (!empty($result['authors'])) {
            $result['names'] = implode(', ', array_map(function($author) {return $author['name'];}, $result['authors']));
        }
        $galleys = (new GalleyEntity())->retrieveAll(['paper' => $paper->id]);
        foreach ($galleys as $galley) {
            $access = $this->user->isConnected() || $issue->open < date('Y-m-d') || $paper->status === 'free';
            $shop = $galley->type === 'shop';
            if ($access !== $shop) {
                $path = $galley->path;
                if (empty($galley->path)) {
                    $path = $this->baseURL.'/article/view/'.$short.'/'.$galley->type;
                }
               $result['galleys'][$galley->type] = $path;
            }
        }
        return $result;
    }
    
    public function home() {
        if (isset($this->controler->journal)) {
            $issue = (new IssueEntity())->retrieveFirst(['journal' => $this->controler->journal->id, 'order' => ['desc' => 'id']]);
            if ($issue) {
                return $this->page('home', [
                    'journal' => JournalsUtils::journal($this->controler->journal, $this->lang),
                    'issue' => $this->_issue($issue)]
                );
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
                        $issues[] = $this->_issue($issue, false);
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
                        $ariadne['active'] = $issue['serial'].' : '.$issue['title'];
                        $models = ['issue' => $issue];
                    }
                    break;
                }
            }
            if ($models !== false) {
                return $this->page($page, array_merge($models, [
                    'ariadne' => $ariadne,
                    'journal' => JournalsUtils::journal($this->controler->journal, $this->lang)
                ]));
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
            if ($paper) {
                $paper = (new PaperEntity())->retrieveOne($paper);
                if ($paper) {
                    $issue = (new IssueEntity())->retrieveOne($paper->issue);
                    if ($issue) {
                        $section = (new SectionEntity())->retrieveOne($paper->section);
                        if ($section) {
                            $_paper = $this->_paper($paper, $issue);
                            $_issue = $this->_issue($issue, false);
                            $ariadne = [
                                'home' => '/'.$this->context,
                                'archive' => '/'.$this->context.'/issue/archive',
                                'issue' => [$_issue['serial'].' : '.$_issue['title'], '/'.$this->context.'/issue/view/'.$_issue['short']]
                            ];
                            switch ($page) {
                                case 'view': {
                                    if (in_array($display, ['html','pdf'])) {
                                        $view = 'display';
                                        $ariadne['section'] = [$section->title, '/'.$this->context.'/issue/view/'.$_issue['short'].'#'.$section->id];
                                        $ariadne['active'] = [$_paper['title'], '/'.$this->context.'/article/view/'.$_paper['short']];
                                    } else {
                                        $page = 'article';
                                        $ariadne['active'] = $section->title;
                                    }
                                    $models = [
                                        'paper' => $_paper,
                                        'issue' => $_issue,
                                        'display' => $display,
                                        'title' => $_paper['title'].' | '.$_issue['serial']
                                    ];
                                    break;
                                }
                                case 'download': {
                                    $path = STORE_FOLDER.'journals'.DS.$this->context.DS.$issue->volume.(!empty($issue->number) ? '.'.$issue->number : '').DS.$_paper['short'].'.'.$display;
                                    switch ($display) {
                                        case 'html': {
                                            $view = 'download';
                                            $models = [
                                                'paper' => $_paper,
                                                'issue' => $_issue,
                                                'content' => file_get_contents($path),
                                                'status' => ($issue->open < date('Y-m-d') || $paper->status === 'free') ? 'free' : 'subscription',
                                                'section' => $section->title
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
                    }
                }
            }
        }
        if ($models !== false) {
            $models = array_merge($models, [
                'ariadne' => $ariadne,
                'journal' => JournalsUtils::journal($this->controler->journal, $this->lang)
            ]);
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
            $this->controler->authors[] = $author->last.', '.$author->first;
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
        $reference = [
            'type'            => 'article-journal',
            'id'              => JournalsUtils::short($this->context, $issue, $paper),
            'title'           => $paper->title,
            'volume'          => $issue->volume,
            'container-title' => $journal->name,
            'page'            => JournalsUtils::pages($paper),
            'accessed'        => ["date-parts" => [[date('Y', $now), date('m', $now), date('d', $now)]]],
            'issued'          => ["date-parts" => [[date('Y', $issued), date('m', $issued), date('d', $issued)]]]
        ];
        if ($issue->number) {
            $reference['issue'] = $issue->number;
        }
        if ($paper->doi) {
            $reference['DOI'] = $paper->doi;
        } else {
            $reference['URL'] = $this->baseURL.'/article/view/'.JournalsUtils::short($this->context, $issue, $paper);
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