<?php

class OJSImport extends ProcessExecutor {
    
    public function execute($parameters = []) {
        $data = [];
        foreach ((new OJSJournalEntity())->retrieve(['many' => true, 'order' => ['asc' => 'seq']]) as $journal) {
            echo "$journal->path\n";
            foreach ((new OJSSettingEntity('journal'))->retrieve(['many' => true, 'where' => [
                'journal_id' => $journal->journal_id,
                'setting_name' => ['in' => ['name','description']],
                'locale' => 'fr_CA'
            ]]) as $entity) {
                $data[$journal->path][$entity->setting_name] = $entity->setting_value;
            }
            foreach ((new OJSSectionEntity())->retrieve(['many' => true, 'where' => ['journal_id' => $journal->journal_id], 'order' => ['asc' => 'seq']]) as $section) {
                $setting = (new OJSSettingEntity('section'))->retrieve(['where' => [
                    'section_id'   => $section->section_id,
                    'locale'       => $journal->primary_locale,
                    'setting_name' => 'title'
                ]]);
                $data[$journal->path]["sections"][] = [
                    'ojs'   => $section->section_id,
                    'title' => $setting->setting_value,
                    'place' => $section->seq
                ];
            }
            foreach ((new OJSIssueEntity())->retrieve(['many' => true, 'where' => ['journal_id' => $journal->journal_id]]) as $issue) {
                echo "\tvol.$issue->volume".(!empty($issue->number) ? " n°$issue->number" : '')." ($issue->year)\n";
                $_issue = [
                    'volume'      => $issue->volume,
                    'number'      => !empty($issue->number) ? $issue->number : null,
                    'year'        => $issue->year,
                    'published'   => $issue->date_published,
                    'modified'    => $issue->last_modified,
                    'open'        => $issue->open_access_date,
                ];
                foreach ((new OJSSettingEntity('issue'))->retrieve(['many' => true, 'where' => [
                    'issue_id'     => $issue->issue_id,
                    'locale'       => $journal->primary_locale,
                    'setting_name' => ['in' => ['title','description']]
                ]]) as $setting) {
                    $_issue[$setting->setting_name] = $setting->setting_value;
                }
                foreach ((new OJSPublicationEntity())->retrieve(['many' => true, 'where' => ['issue_id' => $issue->issue_id]]) as $publication) {
                    $paper = (new OJSPaperEntity())->retrieve($publication->submission_id);
                    $status = $publication->access_status ? 'free' : 'subscription';
                    $pages = $paper->pages;
                    $section = $paper->section_id;
                    $place = $publication->seq;
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
                        'section'   => $section,
                        'place'     => $place
                    ];
                }
                $data[$journal->path]["issues"][] = $_issue;
            }
        }
        file_put_contents('/tmp/ojs.json', Zord::json_encode($data));
        (new JournalEntity())->delete();
        (new IssueEntity())->delete();
        (new SectionEntity())->delete();
        (new PaperEntity())->delete();
        (new SettingEntity())->delete();
        (new UserEntity())->delete();
        $order = 0;
        foreach ($data as $context => $journal) {
            $order++;
            $_journal = (new JournalEntity())->create([
                "context" => $context,
                "name" => $journal['name'],
                "place" => $order
            ]);
            foreach (['description'] as $name) {
                (new SettingEntity('journal'))->create([
                    "object" => $_journal->id,
                    "name"   => $name,
                    "value"  => $journal[$name],
                    "locale" => "fr-FR"
                ]);
            }
            $sections = [];
            foreach ($journal['sections'] ?? [] as $section) {
                $section["journal"] = $_journal->id;
                $_section = (new SectionEntity())->create($section);
                $sections["OJS_".$section['ojs']] = $_section->id;
            }
            foreach ($journal['issues'] ?? [] as $issue) {
                $issue["journal"] = $_journal->id;
                $_issue = (new IssueEntity())->create($issue);
                foreach (['description'] as $name) {
                    (new SettingEntity('issue'))->create([
                        "object" => $_issue->id,
                        "name"   => $name,
                        "value"  => $issue[$name],
                        "locale" => "fr-FR"
                    ]);
                }
                foreach ($issue['papers'] as $paper) {
                    $paper['issue'] = $_issue->id;
                    $paper['section'] = $sections["OJS_".$paper['section']];
                    foreach ($paper['settings'] as $key => $value) {
                        $key = explode('::', $key);
                        $paper[end($key)] = $value;
                    }
                    $_paper = (new PaperEntity())->create($paper);
                    foreach ($paper['authors'] as $author) {
                        $author['paper'] = $_paper->id;
                        (new AuthorEntity())->create($author);
                    }
                }
            }
        }
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