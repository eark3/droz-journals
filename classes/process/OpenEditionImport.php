<?php

class OpenEditionImport extends ProcessExecutor {
    
    public static $FILES = [
        'pdf'  => [
            'dir'    => 'pdf',
            'ext'    => '.pdf',
            'galley' => 'pdf'
        ],
        'tei' => [
            'dir'    => 'tei',
            'ext'    => '-tei.xml',
            'galley' => 'html'
        ]
    ];
    
    public static $STATUS = [
        'info:eu-repo/semantics/embargoedAccess' => 'subscription',
        'info:eu-repo/semantics/openAccess'      => 'free'
    ];
    
    public function parameters($string) {
        $parameters = ['journals' => explode(',', $string)];
        $this->setParameters($parameters);
        return $parameters;
    }
    
    public function execute($parameters = []) {
        foreach ($parameters['journals'] ?? [] as $journal) {
            $_journal = (new JournalEntity())->retrieveOne($journal);
            if ($_journal === false) {
                $this->error(0, 'Unknown journal '.$journal);
                continue;
            }
            $refs = [];
            $folders = glob(STORE_FOLDER.'journals'.DS.$journal.DS.'*');
            usort($folders, function($first, $second) {return basename($first) <=> basename($second);});
            foreach ($folders as $folder) {
                $mets = glob($folder.DS.'*-mets.xml');
                if (count($mets) === 1) {
                    $volume = basename($folder);
                    $number = null;
                    if (strpos('.', $volume) > 0) {
                        list($volume, $number) = explode('.', $volume);
                    }
                    $short = JournalsUtils::short($journal, $volume, $number);
                    $this->info(0, 'Found issue '.$short);
                    $issue = [
                        'volume' => $volume,
                        'number' => $number
                    ];
                    $metadata = ['journal' => $journal];
                    $mets = simplexml_load_string(file_get_contents($mets[0]))->GetRecord->record->metadata->children('mets', true);
                    $mets->rewind();
                    $mets = $mets->current();
                    foreach ($mets->dmdSec as $dmdSec) {
                        $terms = [];
                        foreach ($dmdSec->mdWrap->xmlData->children('dcterms', true) as $term) {
                            $attributes = $term->attributes();
                            $termName = $term->getName();
                            $termValue = ''.$term;
                            if ($attributes->count() > 0) {
                                $attributes->rewind();
                                $attrName = ''.$attributes->current();
                                if (!isset($terms[$termName][$attrName])) {
                                    $terms[$termName][$attrName] = $termValue;
                                } else {
                                    if (!is_array($terms[$termName][$attrName])) {
                                        $terms[$termName][$attrName] = [$terms[$termName][$attrName]];
                                    }
                                    $terms[$termName][$attrName][] = $termValue;
                                }
                            } else {
                                if (!isset($terms[$termName])) {
                                    $terms[$termName] = $termValue;
                                } else {
                                    if (!is_array($terms[$termName])) {
                                        $terms[$termName] = [$terms[$termName]];
                                    }
                                    $terms[$termName][] = $termValue;
                                }
                            }
                        }
                        $type = 'paper';
                        switch ($terms['type']) {
                            case 'issue': {
                                $type = 'issue';
                                break;
                            }
                            case 'part': {
                                $type = 'section';
                                break;
                            }
                        }
                        if ($type === 'issue') {
                            $metadata[$type] = $terms;
                        } else {
                            $id = $this->id($dmdSec->attributes()->ID);
                            $metadata[$type][$id] = $terms;
                        }
                    }
                    $_papers = $metadata['paper'];
                    uasort($_papers, function($first,$second) {
                        return explode('-',$first['extent'])[0] <=> explode('-',$second['extent'])[0];
                    });
                    $metadata['paper'] = $_papers;
                    foreach ($mets->structMap->xpath('//mets:div[@TYPE="part"]') as $div) {
                        $part = $this->id($div->attributes()->DMDID);
                        foreach($div->children('mets', true) as $_div) {
                            $id = $this->id($_div->attributes()->DMDID);
                            $metadata['paper'][$id]['isPartOf']['section'] = $part;
                        }
                    }
                    foreach (array_keys($metadata['paper']) as $id) {
                        foreach(array_keys(self::$FILES) as $type) {
                            $file = $this->file($folder, $type, $id);
                            if (file_exists($file)) {
                                $metadata['paper'][$id]['files'][$type] = $file;
                            }
                        }
                    }
                    file_put_contents('/tmp/metadata.'.$journal.'.'.basename($folder).'.json', json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    $issue['year'] = $metadata['issue']['created'];
                    $urns = $metadata['issue']['identifier']['URN'] ?? [];
                    if (!is_array($urns)) {
                        $urns = [$urns];
                    }
                    foreach ($urns as $urn) {
                        if (substr($urn, 0, 9) === 'urn:isbn:') {
                            $issue['ean'] = str_replace('-', '', substr($urn, 9));
                            break;
                        }
                    }
                    $issue['published'] = substr($metadata['issue']['issued'], 0, 10);
                    $issue['settings'] = [
                        'title' => [
                            $_journal->locale => [
                                'value' => $metadata['issue']['title']
                            ]
                        ],
                        'coverImage' => [
                            $_journal->locale => [
                                'value' => Zord::substitute(Zord::value('import', ['openedition','coverImage',$journal]), $metadata)
                            ]
                        ]
                    ];
                    $papers = [];
                    $sections = [];
                    foreach ($metadata['paper'] as $paper) {
                        $_paper = [];
                        $_paper['pages'] = $paper['extent'];
                        $_paper['status'] = self::$STATUS[$paper['accessRights']];
                        $_short = JournalsUtils::short($journal, $volume, $number, $_paper['pages']);
                        foreach ($paper['files'] as $type => $file) {
                            $_paper['galleys'][] = self::$FILES[$type]['galley'];
                            $filename = Zord::liveFolder('import').DS.$short.DS.$_short;
                            if (!file_exists(dirname($filename))) {
                                mkdir(dirname($filename), 0777, true);
                            }
                            if ($type === 'pdf') {
                                copy($file, $filename.'.pdf');
                                if (isset($issue['ean']) && $_paper['status'] === 'subscription') {
                                    $_paper['galleys'][] = 'shop';
                                }
                            } else if ($type === 'tei') {
                                file_put_contents($filename.'.html', $this->tei2html(file_get_contents($file)));
                            }
                        }
                        $doi = DROZ_DOI_PREFIX.$_short;
                        $urns = $paper['identifier']['URN'] ?? [];
                        if (!is_array($urns)) {
                            $urns = [$urns];
                        }
                        foreach ($urns as $urn) {
                            if (substr($urn, 0, 8) === 'urn:doi:') {
                                $doi = substr($urn, 8);
                                break;
                            }
                        }
                        $_paper['settings'] = [
                            'title'       => [
                                $_journal->locale => [
                                    'value' => $paper['title']
                                ]
                            ],
                            'pub-id::doi' => [
                                $_journal->locale => [
                                    'value' => $doi
                                ]
                            ]
                        ];
                        $section = $paper['isPartOf']['section'] ?? false;
                        if ($section) {
                            if (!in_array($section, $sections)) {
                                $sections[] = $section;
                            }
                        } else {
                            $section = 'SECTION_'.$short.'_'.(count($sections) + 1);
                            if (count($sections) === 0 || substr($sections[count($sections) - 1], 0, 8) !== 'SECTION_') {
                                $sections[] = $section;
                            }
                        }
                        $section = $sections[count($sections) - 1];
                        $_paper['section'] = [
                            'name'     => $section,
                            'settings' => [
                                'title' => [
                                    $_journal->locale => [
                                        'value' => $metadata['section'][$section]['title'] ?? 'Titre de section'
                                    ]
                                ]
                            ]
                        ];
                        $creator = $paper['creator'] ?? false;
                        if ($creator) {
                            if (!is_array($creator)) {
                                $creator = [$creator];
                            }
                            foreach ($creator as $_creator) {
                                $middlename = null;
                                list($lastname, $firstname) = explode(',', $_creator);
                                $firstname = trim($firstname);
                                $lastname  = trim($lastname);
                                if (strpos($firstname, ' ') > 0) {
                                    list($firstname, $middlename) = explode(' ', $firstname);
                                }
                                $_paper['authors'][] = [
                                    'first'  => $firstname,
                                    'middle' => $middlename,
                                    'last'   => $lastname
                                ];
                            }
                        }
                        $papers[] = $_paper;
                    }
                    $issue['papers'] = $papers;
                    file_put_contents(Zord::liveFolder('import').$short.'.json', json_encode($issue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    $refs[] = $short;
                }
            }
            if (count($refs) > 0) {
                Zord::getInstance('Import')->execute([
                    'lang' => 'fr-FR',
                    'continue' => true,
                    'refs' => $refs
                ]);
            }
        }
    }
    
    protected function file($folder, $type, $id) {
        return $folder.DS.self::$FILES[$type]['dir'].DS.$id.self::$FILES[$type]['ext'];
    }
    
    protected function ID($attribute) {
        return str_replace('_', '-', substr(''.$attribute, strlen('MD_')));
    }
    
    protected function tei2html($content) {
        $models = [];
        $html = (new View('/paper.html', $models))->render();
        return $html; 
    }
    
}

?>