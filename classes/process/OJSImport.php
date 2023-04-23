<?php

class OJSImport extends ProcessExecutor {
    
    private function locale($locale) {
        return !empty($locale) ? str_replace('_', '-', $locale) : 'fr-FR';
    }
    
    private function getSettings($type, $object) {
        $settings = [];
        $field = $type.'_id';
        foreach ((new OJSSettingEntity($type))->retrieveAll([$field => $object->$field]) as $entity) {
            $value = str_replace(['site/images/ojsadmin','https://revues-dev.droz.org','https://revues.droz.org/index.php'], ['journals/images','',''], $entity->setting_value);
            $value = preg_replace_callback(
                '!s:(\d+):"(.*?)";!',
                function($match) {
                    return 's:'.strlen($match[2]).':"'.$match[2].'";';
                },
                $value
            );
            if ($entity->setting_type === 'object') {
                $value = base64_encode($value);
            }
            $settings[$entity->setting_name][$this->locale($entity->locale)] = [
                'content' => $entity->setting_type,
                'value'   => $value
            ];
        }
        if ($type === 'journal') {
            $setting = (new OJSSettingEntity($type))->retrieveOne([
                'setting_name' => 'description',
                'journal_id'   => $object->journal_id,
                'locale'       => 'fr_CA'
            ]);
            $settings['rootDescription'][$this->locale($object->primary_locale)] = [
                'content' => 'string',
                'value'   => $setting->setting_value
            ];
        }
        if ($type === 'submission') {
            if (isset($settings['licenseURL'])) {
                foreach (array_keys($settings['licenseURL']) as $locale) {
                    $settings['licenseURL'][$locale]['value'] = str_replace('/licence', '/page/license', $settings['licenseURL'][$locale]['value']);
                }
            }
        }
        return $settings;
    }
    
    protected function importData() {
        $folder = Zord::liveFolder('import');
        $ojs = new Tunnel('ojs');
        $journals = [];
        $names = [];
        foreach ((new OJSJournalEntity())->retrieveAll() as $journal) {
            $sections = [];
            $issues   = [];
            foreach ((new OJSSectionEntity())->retrieveAll(['journal_id' => $journal->journal_id]) as $section) {
                $settings = $this->getSettings('section', $section);
                $name = false;
                foreach ([$journal->primary_locale, 'fr_CA'] as $locale) {
                    $name = $settings['abbrev'][$this->locale($locale)]['value'] ?? false;
                    if ($name !== false) {
                        break;
                    }
                }
                if ($name !== false && !empty($name)) {
                    $names[$section->section_id] = $name;
                    $sections[] = [
                        'name'     => $name,
                        'ojs'      => $section->section_id,
                        'place'    => $section->seq,
                        'settings' => $settings,
                    ];
                } else {
                    echo 'Missing section name for '.$journal->path.'_'.$section->seq.' ('.$section->section_id.')'."\n";
                }
            }
            foreach ((new OJSIssueEntity())->retrieveAll(['journal_id' => $journal->journal_id]) as $issue) {
                $short = JournalsUtils::short($journal->path, $issue->volume, $issue->number ?? null);
                $ean = null;
                $papers = [];
                foreach ((new OJSPublicationEntity())->retrieveAll(['issue_id' => $issue->issue_id]) as $publication) {
                    $paper = (new OJSPaperEntity())->retrieveOne($publication->submission_id);
                    $_short = JournalsUtils::short($journal->path, $issue->volume, $issue->number ?? null, $paper->pages);
                    $status = $publication->access_status ? 'free' : 'subscription';
                    $pages = str_replace('/', '-', JournalsUtils::pages($paper));
                    $section = $names[$paper->section_id];
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
                    $galleys = [];
                    foreach ($entities as $entity) {
                        if (substr($entity->label, -strlen(" à l'achat")) === " à l'achat" && !empty($entity->remote_url) && substr($entity->remote_url, 0, strlen('https://www.droz.org/')) === 'https://www.droz.org/') {
                            $galleys[] = 'shop';
                            if (!isset($ean)) {
                                $url = $entity->remote_url;
                                if (substr($entity->remote_url, 0, strlen('https://www.droz.org/product/')) === 'https://www.droz.org/product/') {
                                    $url = substr($entity->remote_url, strlen('https://www.droz.org/product/'));
                                } else {
                                    $url = substr($entity->remote_url, strlen('https://www.droz.org/'));
                                }
                                $tokens = explode('/', $url);
                                if (count($tokens) === 2) {
                                    $ean = $tokens[0];
                                }
                            }
                        }
                    }
                    $entities = (new OJSFileEntity())->retrieveAll(['submission_id' => $publication->submission_id,'file_stage' => 10,'file_type' => ['in' => ['application/pdf','text/html']]]);
                    foreach ($entities as $entity) {
                        $type = $entity->file_type === 'application/pdf' ? 'pdf' : 'html';
                        $date = date_format(date_create($entity->date_modified), 'Ymd');
                        $path = OJS::path([
                            'journal'  => $journal->journal_id,
                            'article'  => $publication->submission_id,
                            'genre'    => $entity->genre_id ?? '',
                            'file'     => $entity->file_id,
                            'revision' => $entity->revision,
                            'stage'    => $entity->file_stage,
                            'date'     => $date,
                            'type'     => $type
                        ]);
                        $file = $folder.$short.DS.$_short.'.'.$type;
                        $_folder = dirname($file);
                        if (!file_exists($_folder)) {
                            mkdir($_folder, 0755, true);
                        }
                        if (true /*!$ojs->recv($path, $file)*/) {
                            if (!in_array($type, $galleys)) {
                                $galleys[] = $type;
                            }
                        } else {
                            echo "$_short.$type : Unable to get file $path\n";
                        }
                    }
                    $papers[] = [
                        'pages'    => $pages,
                        'status'   => $status,
                        'section'  => $section,
                        'place'    => $place,
                        'authors'  => $authors,
                        'galleys'  => $galleys,
                        'settings' => $settings
                    ];
                }
                $issues[] = [
                    'volume'    => $issue->volume,
                    'number'    => !empty($issue->number) ? $issue->number : null,
                    'year'      => $issue->year,
                    'ean'       => $ean,
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
        (new JournalEntity())->delete();
        Zord::resetFolder($folder);
        foreach ($journals as $journal) {
            $_journal = JournalsUtils::create(new JournalEntity(), $journal);
            foreach ($journal['sections'] ?? [] as $section) {
                $section["journal"] = $_journal->id;
                JournalsUtils::create(new SectionEntity(), $section);
            }
            foreach ($journal['issues'] ?? [] as $issue) {
                $short = JournalsUtils::short($journal['context'], $issue['volume'], $issue['number'] ?? null);
                file_put_contents($folder.$short.'.json', Zord::json_encode($issue));
            }
        }
    }
    
    protected function addSettings() {
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
                (new SettingEntity('journal'))->create([
                    "type"    => 'journal',
                    "object"  => $journal->id,
                    "name"    => $name,
                    "value"   => $settings[$journal->context][$name]
                ]);
            }
        }
    }
    
    protected function importUsers() {
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
    
    public function execute($parameters = []) {
        $this->importData();
        Zord::getInstance('Import')->execute(['lang' => 'fr-FR', 'continue' => true]);
        $this->addSettings();
        //$this->importUsers();
    }
    
}

?>