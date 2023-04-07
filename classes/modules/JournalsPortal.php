<?php

class JournalsPortal extends Portal {
    
    public function home() {
        $journal = (new JournalEntity())->retrieve(['where' => ['context' => $this->context]]);
        if ($journal !== false) {
            $issues = (new IssueEntity())->retrieve(['many'=> true, 'where' => ['journal' => $journal->id], 'order' => ['desc' => 'id']]);
            $issue = $issues->getIterator()->current();
            if ($issue) {
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
                $description = (new SettingEntity('issue'))->retrieve(['where' => [
                    'type'   => 'issue',
                    'name'   => 'description',
                    'locale' => $this->lang,
                    'object' => $issue->id
                ]]);
                if ($description) {
                    return $this->page('home', ['issue' => [
                        'title'       => $title,
                        'cover'       => $cover,
                        'published'   => $issue->published,
                        'description' => $description->value
                    ]]);
                }
            }
        }
        return $this->redirect('/');
    }
    
}

?>