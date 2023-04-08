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
        return [
            'title'       => $title,
            'cover'       => $cover,
            'published'   => $issue->published,
            'description' => $description->value ?? null
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