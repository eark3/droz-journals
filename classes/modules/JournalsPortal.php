<?php

class JournalsPortal extends Portal {
    
    private static $STYLES_EXT = [
        "ris"    => "ris",
        "bibtex" => "bib"
    ];
    
    protected function _issue($issue, $full = true) {
        $copyright = 'Copyright (c) '.date('Y', strtotime($issue->published)).' Librarie Droz';
        $short = $this->context.'_'.$issue->volume;
        $serial = 'Vol. '.$issue->volume;
        if ($issue->number) {
            $serial .= ' n° '.$issue->number;
            $short .= '_'.$issue->number;
        }
        if ($issue->year) {
            $serial .= ' ('.$issue->year.')';
        }
        $cover = '/public/journals/covers/'.$short. '.jpg';
        $link = $this->baseURL.'/issue/view/'.$short;
        if ($full) {
            $description = (new SettingEntity('issue'))->retrieveOne([
                'name'   => 'description',
                'locale' => $this->lang,
                'object' => $issue->id
            ]);
            $sections = (new SectionEntity())->retrieveAll(['journal' => $this->controler->journal->id, 'order' => ['asc' => 'place']]);
            $papers = (new PaperEntity())->retrieveAll(['issue' => $issue->id, 'order' => [['asc' => 'place'],['asc' => 'id']]]);
            $_sections = [];
            foreach ($sections as $section) {
                $_sections[$section->id] = [
                    'title' => $section->title
                ];
            }
            foreach ($papers as $paper) {
                $_paper = [
                    'id'       => $paper->id,
                    'pages'    => $paper->pages,
                    'status'   => $paper->status,
                    'title'    => $paper->title,
                    'subtitle' => $paper->subtitle
                ];
                $authors = (new AuthorEntity())->retrieveAll(['paper' => $paper->id]);
                foreach ($authors as $author) {
                    $_paper['authors'][] = $author->first.' '.$author->last;
                }
                $galleys = (new GalleyEntity())->retrieveAll(['paper' => $paper->id]);
                foreach ($galleys as $galley) {
                    $access = $this->user->isConnected() || $issue->open < date('Y-m-d') || $paper->status === 'free';
                    $shop = $galley->type === 'shop';
                    if ($access !== $shop) {
                        $path = $galley->path;
                        if (empty($galley->path)) {
                            $path = $this->baseURL.'/article/view/'.$paper->id.'/'.$galley->type;
                        }
                        $_paper['galleys'][$galley->type] = $path;
                    }
                }
                $_sections[$paper->section]['papers'][] = $_paper;
            }
            foreach ($_sections as $id => $section) {
                if (empty($section['papers'])) {
                    unset($_sections[$id]);
                }
            }
        }
        $result = [
            'title'     => $issue->title,
            'cover'     => $cover,
            'serial'    => $serial,
            'published' => $issue->published,
            'link'      => $link,
            'short'     => $short,
            'copyright' => $copyright
        ];
        if ($full) {
            $result = array_merge($result, [
                'description' => $description->value ?? null,
                'sections'    => $_sections
            ]);
        }
        return $result;
    }
    
    protected function _paper($paper, $issue) {
        $result = [
            'title'    => $paper->title,
            'subtitle' => $paper->subtitle,
            'id'       => $paper->id,
            'doi'      => $paper->doi
        ];
        $authors = (new AuthorEntity())->retrieveAll(['paper' => $paper->id]);
        foreach ($authors as $author) {
            $result['authors'][] = $author->first.' '.$author->last;
        }
        $galleys = (new GalleyEntity())->retrieveAll(['paper' => $paper->id]);
        foreach ($galleys as $galley) {
            $access = $this->user->isConnected() || $issue->open < date('Y-m-d') || $paper->status === 'free';
            $shop = $galley->type === 'shop';
            if ($access !== $shop) {
                $path = $galley->path;
                if (empty($galley->path)) {
                    $path = $this->baseURL.'/article/view/'.$paper->id.'/'.$galley->type;
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
                        $short = $this->params['short'] ?? null;
                        if ($short) {
                            $tokens = explode('_', $short);
                            $number = null;
                            if (count($tokens) > 2) {
                                list($journal, $volume, $number) = $tokens;
                            } else {
                                list($journal, $volume) = $tokens;
                            }
                            if ($journal === $this->controler->journal->context) {
                                $issue = (new IssueEntity())->retrieveOne(['journal' => $this->controler->journal->id, 'volume' => $volume, 'number' => $number]);
                            }
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
                return $this->page($page, array_merge($models, ['ariadne' => $ariadne]));
            }
        }
        return $this->home();
    }
    
    public function article() {
        if (isset($this->controler->journal)) {
            $page = $this->params['page'] ?? null;
            $paper = $this->params['paper'] ?? null;
            $type = $this->params['type'] ?? null;
            $models = false;
            if ($paper) {
                $paper = (new PaperEntity())->retrieveOne($paper);
                if ($paper) {
                    $issue = (new IssueEntity())->retrieveOne($paper->issue);
                    if ($issue) {
                        $section = (new SectionEntity())->retrieveOne($paper->section);
                        if ($section) {
                            $paper = $this->_paper($paper, $issue);
                            $issue = $this->_issue($issue, false);
                            $ariadne = [
                                'home' => '/'.$this->context,
                                'archive' => '/'.$this->context.'/issue/archive',
                                'issue' => [$issue['serial'].' : '.$issue['title'], '/'.$this->context.'/issue/view'],
                                'active' => $section->title
                            ];
                            switch ($page) {
                                case 'view': {
                                    $page = 'article';
                                    $models = ['paper' => $paper, 'issue' => $issue];
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($models !== false) {
            return $this->page($page, array_merge($models, ['ariadne' => $ariadne]));
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
                    $paper->id.'.'.self::$STYLES_EXT[$style],
                    null,
                    $content
                ) : $this->error(501);
            }
        }
    }
}

?>