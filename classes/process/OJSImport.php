<?php

class OJSImport extends ProcessExecutor {
    
    private function getSettings($type, $object) {
        $settings = [];
        $field = $type.'_id';
        foreach ((new OJSSettingEntity($type))->retrieveAll([$field => $object->$field]) as $entity) {
            $settings[$entity->setting_name][$entity->locale] = [
                'content' => $entity->setting_type,
                'value'   => str_replace(['site/images/ojsadmin','https://revues-dev.droz.org'], ['journals/images',''], $entity->setting_value)
            ];
        }
        return $settings;
    }
    
    private function create($entity, $data) {
        $object = $entity->create($data);
        if ($object) {
            foreach ($data['settings'] ?? [] as $name => $locales) {
                foreach ($locales as $locale => $item) {
                    (new SettingEntity($entity->_type))->create([
                        "object"  => $object->id,
                        "name"    => $name,
                        "value"   => $item['value'],
                        "content" => $item['content'],
                        "locale"  => !empty($locale) ? str_replace('_', '-', $locale) : 'fr-FR'
                    ]);
                }
            }
        }
        if ($entity->_type === 'journal') {
            $journal = (new OJSJournalEntity())->retrieveOne(['path' => $object->context]);
            $setting = (new OJSSettingEntity($entity->_type))->retrieveOne([
                'setting_name' => 'description',
                'journal_id'   => $journal->journal_id,
                'locale'       => 'fr_CA'
            ]);
            (new SettingEntity('journal'))->create([
                "object" => $object->id,
                "name"   => 'rootDescription',
                "value"  => $setting->setting_value
            ]);
        }
        return $object;
    }
    
    public function execute($parameters = []) {
        $journals = [];
        foreach ((new OJSJournalEntity())->retrieveAll() as $journal) {
            $sections = [];
            $issues   = [];
            foreach ((new OJSSectionEntity())->retrieveAll(['journal_id' => $journal->journal_id]) as $section) {
                $sections[] = [
                    'ojs'      => $section->section_id,
                    'place'    => $section->seq,
                    'settings' => $this->getSettings('section', $section),
                ];
            }
            foreach ((new OJSIssueEntity())->retrieveAll(['journal_id' => $journal->journal_id]) as $issue) {
                $papers = [];
                foreach ((new OJSPublicationEntity())->retrieveAll(['issue_id' => $issue->issue_id]) as $publication) {
                    $paper = (new OJSPaperEntity())->retrieveOne($publication->submission_id);
                    $status = $publication->access_status ? 'free' : 'subscription';
                    $pages = JournalsUtils::pages($paper);
                    $section = $paper->section_id;
                    $place = $publication->seq;
                    $settings = $this->getSettings('submission', $publication);
                    foreach ($settings['title'] ?? [] as $locale => $item) {
                        if ($item['value'] === "PDF du dossier") {
                            $settings['title'][$locale]['value'] = 'Dossier complet';
                            unset($settings['subtitle'][$locale]);
                        }
                    }
                    $authors = [];
                    foreach ((new OJSAuthorEntity())->retrieveAll(['submission_id' => $publication->submission_id]) as $author) {
                        $last   = trim($author->last_name);
                        $middle = trim($author->middle_name);
                        $first  = trim($author->first_name);
                        if (!empty($last) || !empty($first)) {
                            if ($first !== 's.' || $last !== 'n.') {
                                $authors[] = [
                                    'first'    => $first,
                                    'middle'   => $middle,
                                    'last'     => $last,
                                    'email'    => $author->email,
                                    'place'    => $author->seq,
                                    'settings' => $this->getSettings('author', $author)
                                ];
                            }
                        }
                    }
                    $entities = (new OJSGalleyEntity())->retrieveAll(['submission_id' => $publication->submission_id]);
                    $resources = [];
                    foreach ($entities as $entity) {
                        if (substr($entity->label, -strlen(" à l'achat")) === " à l'achat" && !empty($entity->remote_url) && substr($entity->remote_url, 0, strlen('https://www.droz.org/')) === 'https://www.droz.org/') {
                            $resources['shop'] = $entity->remote_url;
                        }
                    }
                    $entities = (new OJSFileEntity())->retrieveAll(['submission_id' => $publication->submission_id,'file_stage' => 10,'file_type' => ['in' => ['application/pdf','text/html']]]);
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
                    }
                    $papers[] = [
                        'pages'     => $pages,
                        'status'    => $status,
                        'settings'  => $settings,
                        'authors'   => $authors,
                        'resources' => $resources,
                        'section'   => $section,
                        'place'     => $place
                    ];
                }
                $issues[] = [
                    'volume'    => $issue->volume,
                    'number'    => !empty($issue->number) ? $issue->number : null,
                    'year'      => $issue->year,
                    'published' => $issue->date_published,
                    'modified'  => $issue->last_modified,
                    'open'      => $issue->open_access_date,
                    'papers'    => $papers,
                    'settings'  => $this->getSettings('issue', $issue)
                ];
            }
            $journals[] = [
                'context'  => $journal->path,
                'locale'   => $journal->primary_locale,
                'place'    => $journal->seq,
                'sections' => $sections,
                'issues'   => $issues,
                'settings' => $this->getSettings('journal', $journal)
            ];
        }
        //file_put_contents('/tmp/ojs.json', Zord::json_encode($journals));
        (new JournalEntity())->delete();
        (new IssueEntity())->delete();
        (new SectionEntity())->delete();
        (new PaperEntity())->delete();
        (new AuthorEntity())->delete();
        (new GalleyEntity())->delete();
        (new SettingEntity())->delete();
        $ojs = new Tunnel('ojs');
        foreach ($journals as $journal) {
            $_journal = $this->create(new JournalEntity(), $journal);
            echo $_journal->context."\n";
            $sections = [];
            foreach ($journal['sections'] ?? [] as $section) {
                $section["journal"] = $_journal->id;
                $_section = $this->create(new SectionEntity(), $section);
                $sections["OJS_".$section['ojs']] = $_section->id;
                echo "\tsection : ".$_section->place."\n";
            }
            foreach ($journal['issues'] ?? [] as $issue) {
                $issue["journal"] = $_journal->id;
                $_issue = $this->create(new IssueEntity(), $issue);
                echo "\tissue : ".JournalsUtils::short($_journal->context, $_issue)."\n";
                foreach ($issue['papers'] as $paper) {
                    $paper['issue'] = $_issue->id;
                    $paper['section'] = $sections["OJS_".$paper['section']];
                    $_paper = $this->create(new PaperEntity(), $paper);
                    echo "\t\tpaper : ".JournalsUtils::short($_journal->context, $_issue, $_paper)."\n";
                    foreach ($paper['authors'] as $author) {
                        $author['paper'] = $_paper->id;
                        $_author = $this->create(new AuthorEntity(), $author);
                        echo "\t\t\tauthor : ".JournalsUtils::name($_author)."\n";
                    }
                    foreach ($paper['resources'] as $type => $resource) {
                        $path = $type === 'shop' ? $resource : null;
                        $create = true;
                        if ($type !== 'shop') {
                            $folder = STORE_FOLDER.'journals'.DS.$journal['context'].DS.$issue['volume'].(isset($issue['number']) ? '_'.$issue['number'] : '').DS;
                            $file = JournalsUtils::short($journal['context'], $_issue, $_paper).'.'.$type;
                            if (!file_exists($folder)) {
                                mkdir($folder, 0755, true);
                            }
                            $create = $ojs->recv($resource, $folder.$file);
                        }
                        if ($create) {
                            $this->create(new GalleyEntity(), [
                                'type'  => $type,
                                'paper' => $_paper->id,
                                'path'  => $path
                            ]);
                            echo "\t\t\tgalley : ".$type."\n";
                        }
                    }
                }
            }
        }
        $settings = [
            'RHP' => [
                'bannerLink'   => 'https://www.shpf.fr/',
                'bannerImages' => '/public/journals/images/annonce-shpf.jpg'
            ],
            'RFHL' => [
                'bannerLink'   => 'http://sbg1866.canalblog.com/',
                'bannerImages' => '/public/journals/images/RFHL_Lien_site.png'
            ],
            'CFS' => [
                'bannerLink'   => 'https://www.cercleferdinanddesaussure.org/',
                'bannerImages' => '/public/journals/images/CFS_Image_accueil.png'
            ],
            'RThPh' => [
                'bannerLink'   => 'https://rthph.ch/',
                'bannerImages' => '/public/journals/images/RTHPH_lien_site.png'
            ],
            'SLLMOO' => [
                'bannerLink'   => 'http://www.conjointures.org/',
                'bannerImages' => '/public/journals/images/BASE_SITE_CONJOINTURE.jpg'
            ]
        ];
        foreach ((new JournalEntity())->retrieveAll(['context' => ['in' => array_keys($settings)]]) as $journal) {
            foreach (array_keys($settings[$journal->context]) as $name) {
                (new SettingEntity($entity->_type))->create([
                    "type"    => 'journal',
                    "object"  => $journal->id,
                    "name"    => $name,
                    "value"   => $settings[$journal->context][$name]
                ]);
            }
        }
        (new UserEntity())->delete();
        (new UserHasRoleEntity())->delete();
        (new UserHasProfileEntity())->delete();
        (new UserHasIPV4Entity())->delete();
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
            (new UserEntity())->create([
                "login" => $user->username,
                "password" => $user->password,
                "password.crypted" => true,
                "email" => $user->email,
                "name" => implode(' ', $tokens),
            ]);
            if ($user->username === 'ojsadmin') {
                (new UserHasRoleEntity())->create([
                    'user'    => $user->username,
                    'context' => '*',
                    'role'    => '*',
                    'start'   => date('Y-m-d'),
                    'end'     => '2038-01-19'
                ]);
            } else {
                $journals = (new OJSJournalEntity())->retrieveAll();
                $_journals = [];
                foreach ($journals as $journal) {
                    $_journals[$journal->journal_id] = $journal->path;
                }
                $data['user'] = $user->username;
                $data['role'] = 'reader';
                foreach ((new OJSSubscriptionEntity())->retrieveAll([
                    'journal_id' => ['in' => array_keys($_journals)],
                    'user_id'    => $user->user_id,
                    'status'     => 1
                ]) as $subscription) {
                    $data['context'] = $_journals[$subscription->journal_id];
                    $data['start']   = $subscription->date_start;
                    $data['end']     = $subscription->date_end;
                    $type = (new OJSSubscriptionTypeEntity())->retrieveOne($subscription->type_id);
                    if ($type->institutional) {
                        $subscription = (new OJSInstitutionalSubscriptionEntity())->retrieveOne(['subscription_id' => $subscription->subscription_id]);
                        $_user = User::get($user->username);
                        $_user->setInstitution($subscription->institution_name);
                        $_user->saveProfile();;
                        $ips = (new OJSInstitutionalSubscriptionIPEntity())->retrieveAll(['subscription_id' => $subscription->subscription_id]);
                        $done = [];
                        $undone = [];
                        foreach ($ips as $ip) {
                            if (!empty($ip->ip_string)) {
                                if (strpos($ip->ip_string, '/') > 0) {
                                    $done[] = $ip->ip_string;
                                } else {
                                    $entry = explode('-', str_replace(' ', '', $ip->ip_string));
                                    if (count($entry) > 1) {
                                        $entry[0] = str_replace('*', '0', $entry[0]);
                                        $entry[1] = str_replace('*', '255', $entry[1]);
                                        $first = explode('.', $entry[0]);
                                        $second = explode('.', $entry[1]);
                                        $undone[] = $first[0].'.'.$first[1].'.'.$first[2].'-'.$second[2].'.'.$first[3].'-'.$second[3];
                                    } else {
                                        $undone[] = str_replace('*', '0-255', $entry[0]);
                                    }
                                }
                            }
                        }
                        (new UserEntity())->update($user->username, ["ipv4" => implode(',', array_merge($done, Zord::IP($undone)))]);
                    }
                    (new UserHasRoleEntity())->create($data);
                }
            }
        }
    }
    
}

?>