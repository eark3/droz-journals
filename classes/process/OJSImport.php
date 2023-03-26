<?php

class OJSImport extends ProcessExecutor {
    
    public function execute($parameters = []) {
        $data = [];
        foreach ((new JournalEntity())->retrieve() as $journal) {
            foreach ((new IssueEntity())->retrieve(['many' => true, 'where' => ['journal_id' => $journal->journal_id]]) as $issue) {
                $_issue = [
                    'volume' => $issue->volume,
                    'number' => !empty($issue->number) ? $issue->number : null,
                    'year'   => $issue->year
                ];
                foreach ((new PublicationEntity())->retrieve(['many' => true, 'where' => ['issue_id' => $issue->issue_id]]) as $publication) {
                    $paper = (new PaperEntity())->retrieve($publication->submission_id);
                    $entities = (new SettingEntity())->retrieve(['many' => true, 'where' => ['submission_id' => $publication->submission_id]]);
                    $settings = [];
                    foreach ($entities as $entity) {
                        if (!empty($entity->locale)) {
                            $settings[$entity->setting_name][$entity->locale] = $entity->setting_value;
                        } else {
                            $settings[$entity->setting_name] = $entity->setting_value;
                        }
                    }
                    $entities = (new AuthorEntity())->retrieve(['many' => true, 'where' => ['submission_id' => $publication->submission_id]]);
                    $authors = [];
                    foreach ($entities as $entity) {
                        $authors[] = [
                            'first' => $entity->first_name,
                            'last'  => $entity->last_name,
                        ];
                    }
                    $entities = (new GalleyEntity())->retrieve(['many' => true, 'where' => ['submission_id' => $publication->submission_id]]);
                    $galleys = [];
                    foreach ($entities as $entity) {
                        $galleys[] = [
                            'url'   => $entity->remote_url,
                            'label' => $entity->label,
                        ];
                    }
                    $entities = (new FileEntity())->retrieve(['many' => true, 'where' => ['submission_id' => $publication->submission_id,'file_stage' => 10,'file_type' => ['in' => ['application/pdf','text/html']]]]);
                    $files = [];
                    foreach ($entities as $entity) {
                        $files[] = OJS::path([
                            'journal'  => $journal->journal_id,
                            'article'  => $publication->submission_id,
                            'genre'    => $entity->genre_id ?? '',
                            'file'     => $entity->file_id,
                            'revision' => $entity->revision,
                            'stage'    => $entity->file_stage,
                            'date'     => date_format(date_create($entity->date_modified), 'Ymd'),
                            'type'     => $entity->file_type === 'application/pdf' ? 'pdf' : 'html'
                        ]);
                    }
                    $_issue['papers'][] = [
                        'pages'  => $paper->pages,
                        'status' => $publication->access_status ? 'free' : 'subscription',
                        'settings' => $settings,
                        'authors'  => $authors,
                        'galleys'  => $galleys,
                        'files'    => $files
                    ];
                }
                $data[$journal->path][] = $_issue;
            }
        }
        file_put_contents('/tmp/ojs.json', Zord::json_encode($data));
    }
    
}

?>