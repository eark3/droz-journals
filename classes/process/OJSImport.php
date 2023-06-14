<?php

class OJSImport extends ProcessExecutor {
        
    protected $users  = null;
    protected $issues = null;
    protected $reset  = false;
    
    public function parameters($string) {
        if ($string === 'all') {
            $string = 'metadata,journals,issues,files';
        }
        $parameters = ['imports' => explode(',', $string)];
        $this->setParameters($parameters);
        return $parameters;
    }
    
    private function import($data) {
        return in_array($data, $this->parameters['imports'] ?? []);
    }
    
    private function locale($locale) {
        return !empty($locale) ? str_replace('_', '-', $locale) : 'fr-FR';
    }
    
    private function getSettings($type, $object) {
        $settings = [];
        $field = $type.'_id';
        $replacements = [
            'site/images/ojsadmin'              => 'journals/images',
            'https://revues-dev.droz.org'       => '',
            'https://revues.droz.org/index.php' => '',
            '/subscription'                     => '/info/subscription',
            'author@mail.com'                   => ''
        ];
        foreach ((new OJSSettingEntity($type))->retrieveAll([$field => $object->$field]) as $entity) {
            $value = str_replace(array_keys($replacements), array_values($replacements), $entity->setting_value);
            if ($entity->setting_type === 'object') {
                $value = base64_encode(Zord::sanitize($value));
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
        if ($this->import('journals')) {
            (new JournalEntity())->delete();
        }
        $files  = [];
        $folders = [];
        if (empty($this->issues)) {
            $files   = glob($folder.'*.json');
            $folders = glob($folder, GLOB_ONLYDIR);
        } else {
            foreach ($this->issues as $context => $numbers) {
                foreach ($numbers as $number) {
                    $key = $context.'_'.$number;
                    $files[]   = $key.'.json';
                    $folders[] = $key;
                }
            }
        }
        if ($this->import('metadata')) {
            foreach ($files as $metadata) {
                if (file_exists($metadata) && is_file($metadata)) {
                    unlink($metadata);
                }
            }
        }
        if ($this->import('files')) {
            foreach ($folders as $_folder) {
                if (file_exists($_folder) && is_dir($_folder)) {
                    Zord::deleteRecursive($_folder);
                }
            }
        }
        $ojs = new Tunnel('ojs');
        $journals = [];
        $names = [];
        $mapping = Zord::getConfig('mapping');
        foreach ((new OJSJournalEntity())->retrieveAll() as $journal) {
            if (!empty($this->issues) && !in_array($journal->path, array_keys($this->issues))) {
                continue;
            }
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
                foreach ($settings as $_name => $locales) {
                    if (empty($locales['fr-FR'])) {
                        $settings[$_name]['fr-FR'] = $locales['fr-CA'];
                    }
                }
                if ($name !== false && !empty($name)) {
                    $names[$section->section_id] = $name;
                    $sections[] = [
                        'name'     => $name,
                        'ojs'      => $section->section_id,
                        'settings' => $settings,
                    ];
                } else {
                    echo 'Missing section name for '.$journal->path.'_'.$section->seq.' ('.$section->section_id.')'."\n";
                }
            }
            foreach ((new OJSIssueEntity())->retrieveAll(['journal_id' => $journal->journal_id]) as $issue) {
                $short = JournalsUtils::short($journal->path, $issue->volume, $issue->number ?? null);
                if (!in_array(substr($short, strlen($journal->path.'_')), $this->issues[$journal->path])) {
                    continue;
                }
                $mapping['issues'][$issue->issue_id] = $short;
                $ean = null;
                $papers = [];
                foreach ((new OJSPublicationEntity())->retrieveAll(['issue_id' => $issue->issue_id]) as $publication) {
                    $paper = (new OJSPaperEntity())->retrieveOne($publication->submission_id);
                    $_short = JournalsUtils::short($journal->path, $issue->volume, $issue->number ?? null, $paper->pages);
                    $mapping['papers'][$publication->submission_id] = $_short;
                    $status = $publication->access_status ? 'free' : 'subscription';
                    $pages = str_replace(['_','/'], ['-','-'], JournalsUtils::pages($paper));
                    $section = $names[$paper->section_id];
                    $place = JournalsUtils::place($pages);
                    $settings = $this->getSettings('submission', $publication);
                    $doi = $settings['pub-id::doi'][$this->locale($journal->primary_locale)]['value'] ?? '';
                    if (!empty($doi) && substr($doi, 0, strlen(DROZ_DOI_PREFIX)) === DROZ_DOI_PREFIX) {
                        $suffix = substr($doi, strlen(DROZ_DOI_PREFIX));
                        $mapping['papers'][$suffix] = $_short;
                    }
                    foreach ($settings['title'] ?? [] as $locale => $item) {
                        if ($item['value'] === "PDF du dossier") {
                            $settings['title'][$locale]['value'] = 'Dossier complet';
                            unset($settings['subtitle'][$locale]);
                        }
                    }
                    $keywords = [];
                    foreach ((new OJSVocabEntity())->retrieveAll(['symbolic' => 'submissionKeyword', 'assoc_id' => $publication->submission_id]) as $vocab) {
                        foreach ((new OJSVocabEntryEntity())->retrieveAll(['controlled_vocab_id' => $vocab->controlled_vocab_id, 'order' => ['asc' => 'seq']]) as $entry) {
                            foreach ((new OJSVocabEntrySettingEntity())->retrieveAll(['controlled_vocab_entry_id' => $entry->controlled_vocab_entry_id]) as $setting) {
                                $keywords[$this->locale($setting->locale)][] = $setting->setting_value;
                            }
                        }
                    }
                    foreach ($keywords as $locale => $values) {
                        $settings['keywords'][$locale]['value'] = implode(', ', $values);
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
                    $entities = (new OJSFileEntity())->retrieveAll([
                        'submission_id' => $publication->submission_id,
                        'file_stage'    => ['in' => [10,17]],
                        'file_type'     => ['in' => ['application/pdf','text/html','image/png','text/css']],
                        'assoc_type'    => '__NOT_NULL__',
                        'assoc_id'      => '__NOT_NULL__'
                    ]);
                    foreach ($entities as $entity) {
                        $type = explode('/', $entity->file_type)[1];
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
                        $file = $folder.$short.DS.$_short.(in_array($type, ['html','pdf']) ? '.'.$type : DS.$type.DS.$entity->original_file_name);
                        $_folder = dirname($file);
                        if ($this->import('files') && !file_exists($_folder)) {
                            mkdir($_folder, 0755, true);
                        }
                        if (!$this->import('files') || $ojs->recv($path, $file)) {
                            if (!in_array($type, $galleys)) {
                                $galleys[] = $type;
                            }
                        } else {
                            echo "$_short.$type : Unable to get file $path\n";
                        }
                    }
                    $views = Zord::value('stats', $publication->submission_id) ?? 0;
                    if (empty($pages)) {
                        $this->warn(0, "Pages non renseignées pour ".$publication->submission_id.' ('.$journal->path.')');
                    }
                    $papers[] = [
                        'pages'    => $pages,
                        'status'   => $status,
                        'section'  => $section,
                        'place'    => $place,
                        'views'    => $views,
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
        foreach ($journals as $journal) {
            if ($this->import('journals')) {
                $_journal = JournalsUtils::create(new JournalEntity(), $journal);
                foreach ($journal['sections'] ?? [] as $section) {
                    $section["journal"] = $_journal->id;
                    JournalsUtils::create(new SectionEntity(), $section);
                }
            }
            if ($this->import('metadata')) {
                foreach ($journal['issues'] ?? [] as $issue) {
                    $short = JournalsUtils::short($journal['context'], $issue['volume'], $issue['number'] ?? null);
                    file_put_contents($folder.$short.'.json', Zord::json_encode($issue));
                }
            }
        }
        Zord::saveConfig('mapping', $mapping);
    }
    
    protected function addSettings() {
        $settings = [
            'RHP' => [
                'bannerLink'  => 'https://www.shpf.fr/',
                'bannerImage' => '/public/journals/images/annonce-shpf.jpg'
            ],
            'RFHL' => [
                'bannerLink'  => 'http://sbg1866.canalblog.com/',
                'bannerImage' => '/public/journals/images/RFHL_Lien_site.png'
            ],
            'CFS' => [
                'bannerLink'  => 'https://www.cercleferdinanddesaussure.org/',
                'bannerImage' => '/public/journals/images/CFS_Image_accueil.png'
            ],
            'RThPh' => [
                'bannerLink'  => 'https://rthph.ch/',
                'bannerImage' => '/public/journals/images/RTHPH_lien_site.png'
            ],
            'SLLMOO' => [
                'bannerLink'  => 'http://www.conjointures.org/',
                'bannerImage' => '/public/journals/images/BASE_SITE_CONJOINTURE.jpg'
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
        if (empty($this->users)) {
            (new UserEntity())->delete();
            (new UserHasRoleEntity())->delete();
            (new UserHasProfileEntity())->delete();
            (new UserHasIPV4Entity())->delete();
        }
        foreach ((new OJSUserEntity())->retrieve() as $user) {
            if (!in_array($user->username, $this->users)) {
                continue;
            }
            $this->info(0, $user->username);
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
        if (!empty($parameters['issues']) && is_array($parameters['issues']) && Zord::is_associative($parameters['issues'])) {
            $this->issues = $parameters['issues'];
        }
        if (!empty($parameters['users']) && is_array($parameters['users']) && !Zord::is_associative($parameters['users'])) {
            $this->users = $parameters['users'];
        }
        $this->setParameters($parameters);
        if ($this->import('metadata') || $this->import('files')) {
            $this->importData();
        }
        if ($this->import('journals')) {
            $this->addSettings();
        }
        if ($this->import('issues')) {
            $refs = null;
            if (!empty($this->issues)) {
                $refs = [];
                foreach ($this->issues as $context => $numbers) {
                    foreach ($numbers as $number) {
                        $refs[] = $context.'_'.$number;
                    }
                }
            }
            Zord::getInstance('Import')->execute(['lang' => 'fr-FR', 'continue' => true, 'refs' => $refs]);
        }
        if ($this->import('users')) {
            $this->importUsers();
        }
    }
    
}

?>