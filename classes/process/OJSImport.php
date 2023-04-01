<?php

class OJSImport extends ProcessExecutor {
    
    public function execute($parameters = []) {
        $data = [];
        foreach ((new OJSJournalEntity())->retrieve() as $journal) {
            echo "$journal->path\n";
            $entities = (new OJSSettingEntity('journal'))->retrieve(['many' => true, 'where' => [
                'journal_id' => $journal->journal_id,
                'setting_name' => ['in' => ['name','description']],
                'locale' => 'fr_CA'
            ]]);
            foreach ($entities as $entity) {
                $data[$journal->path][$entity->setting_name] = $entity->setting_value;
            }
            foreach ((new OJSIssueEntity())->retrieve(['many' => true, 'where' => ['journal_id' => $journal->journal_id]]) as $issue) {
                echo "\tvol.$issue->volume".(!empty($issue->number) ? " n°$issue->number" : '')." ($issue->year)\n";
                $_issue = [
                    'volume' => $issue->volume,
                    'number' => !empty($issue->number) ? $issue->number : null,
                    'year'   => $issue->year
                ];
                foreach ((new OJSPublicationEntity())->retrieve(['many' => true, 'where' => ['issue_id' => $issue->issue_id]]) as $publication) {
                    $paper = (new OJSPaperEntity())->retrieve($publication->submission_id);
                    $status = $publication->access_status ? 'free' : 'subscription';
                    $pages = $paper->pages;
                    echo "\t\tp.$pages ($status)\n";
                    $entities = (new OJSSettingEntity('submission'))->retrieve(['many' => true, 'where' => ['submission_id' => $publication->submission_id]]);
                    $settings = [];
                    foreach ($entities as $entity) {
                        if (in_array($entity->setting_name, ['title','subtitle','pub-id::doi'])) {
                            echo "\t\t\t$entity->setting_value\n";
                            $settings[$entity->setting_name] = $entity->setting_value;
                        }
                    }
                    $entities = (new OJSAuthorEntity())->retrieve(['many' => true, 'where' => ['submission_id' => $publication->submission_id]]);
                    $authors = [];
                    foreach ($entities as $entity) {
                        $last = trim($entity->last_name);
                        $first = trim($entity->first_name);
                        if (!empty($last) || !empty($first)) {
                            echo "\t\t\t".($last ?? '').(!empty($last) && !empty($first) ? ', ' : '').($first ?? '')."\n";
                            $authors[] = [
                                'first' => $first,
                                'last'  => $last,
                            ];
                        }
                    }
                    $entities = (new OJSGalleyEntity())->retrieve(['many' => true, 'where' => ['submission_id' => $publication->submission_id]]);
                    $resources = [];
                    foreach ($entities as $entity) {
                        if (substr($entity->label, -strlen(" à l'achat")) === " à l'achat" && !empty($entity->remote_url)) {
                            echo "\t\t\t$entity->remote_url\n";
                            $resources['shop'] = $entity->remote_url;
                        }
                    }
                    $entities = (new OJSFileEntity())->retrieve(['many' => true, 'where' => ['submission_id' => $publication->submission_id,'file_stage' => 10,'file_type' => ['in' => ['application/pdf','text/html']]]]);
                    foreach ($entities as $entity) {
                        $type = $entity->file_type === 'application/pdf' ? 'pdf' : 'html';
                        $date = date_format(date_create($entity->date_modified), 'Ymd');
                        $resources[$type] = OJS::path([
                            'journal'  => $journal->journal_id,
                            'article'  => $publication->submission_id,
                            'genre'    => $entity->genre_id ?? '',
                            'file'     => $entity->file_id,
                            'revision' => $entity->revision,
                            'stage'    => $entity->file_stage,
                            'date'     => $date,
                            'type'     => $type
                        ]);
                        echo "\t\t\t$resources[$type]\n";
                    }
                    $_issue['papers'][] = [
                        'pages'     => $pages,
                        'status'    => $status,
                        'settings'  => $settings,
                        'authors'   => $authors,
                        'resources' => $resources,
                    ];
                }
                $data[$journal->path]["issues"][] = $_issue;
            }
        }
        (new JournalEntity())->delete();
        (new SettingEntity())->delete();
        (new UserEntity())->delete();
        foreach ($data as $context => $journal) {
            $entity = (new JournalEntity())->create(["context" => $context]);
            foreach (['name','description'] as $name) {
                (new SettingEntity())->create([
                    "type"   => "journal",
                    "object" => $entity->id,
                    "name"   => $name,
                    "value"  => $journal[$name],
                    "locale" => "fr-FR"
                ]);
            }
        }
        file_put_contents('/tmp/ojs.json', Zord::json_encode($data));
        foreach ((new OJSUserEntity())->retrieve() as $user) {
            $first = trim($user->first_name ?? '');
            $middle = trim($user->middle_name ?? '');
            $last = trim($user->last_name ?? '');
            $tokens = [];
            foreach ([$first,$middle,$last] as $token) {
                if (!empty($token)) {
                    $tokens[] = $token;
                }
            }
            $name = implode(' ', $tokens);
            echo "$name\n";
            $entity = (new UserEntity())->create([
                "login" => $user->username,
                "password" => $user->password,
                "email" => $user->email,
                "name" => $name,
            ]);
        }
    }
    
}

?>