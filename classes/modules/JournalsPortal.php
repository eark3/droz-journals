<?php

class JournalsPortal extends Portal {
    
    protected function _issue($issue) {
        $title = 'Vol. '.$issue->volume;
        if ($issue->number) {
            $title .= ' n° '.$issue->number;
        }
        if ($issue->year) {
            $title .= ' ('.$issue->year.')';
        }
        if ($issue->title) {
            $title .= ' : '.$issue->title;
        }
        $cover = '/public/journals/covers/'.$this->context.'_'.$issue->volume;
        if ($issue->number) {
            $cover .= '_'.$issue->number;
        }
        $cover .= '.jpg';
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
                if (($this->user->isConnected() && $galley->type !== 'shop') || $issue->open < date('Y-m-d') || $paper->status === 'free' || (!$this->user->isConnected() && $galley->type === 'shop')) {
                    $_paper['galleys'][$galley->type] = $galley->path;
                }
            }
            $_sections[$paper->section]['papers'][] = $_paper;
        }
        foreach ($_sections as $id => $section) {
            if (empty($section['papers'])) {
                unset($_sections[$id]);
            }
        }
        return [
            'title'       => $title,
            'cover'       => $cover,
            'published'   => $issue->published,
            'description' => $description->value ?? null,
            'sections'    => $_sections
        ];
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
    
}

?>