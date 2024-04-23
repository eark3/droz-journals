<?php

class OpenEditionImport extends ProcessExecutor {
    
    private static $XML_PARSE_BIG_LINES = 4194304;
    
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
    
    protected $styles = [];
    
    public function parameters($string) {
        $parameters = explode(' ', $string);
        $journals = explode(',', $parameters[0]);
        $steps = Zord::value('import', 'steps');
        if (count($parameters) > 1) {
            $steps = explode(',', $parameters[1]);
        }
        $parameters = ['journals' => $journals, 'steps' => $steps];
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
                        $beginFirst = explode('-',$first['extent'])[0];
                        $beginSecond = explode('-',$second['extent'])[0];
                        if (!is_numeric($beginFirst) && is_numeric($beginSecond)) {
                            return -1;
                        }
                        if (is_numeric($beginFirst) && !is_numeric($beginSecond)) {
                            return 1;
                        }
                        return $beginFirst <=> $beginSecond;
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
                    $previous = null;
                    foreach ($metadata['paper'] as $paper) {
                        $beginPrevious = isset($previous) ? explode('-', $previous['extent'])[0] : null;
                        $beginPaper = explode('-', $paper['extent'])[0];
                        $changeNumbering = !isset($beginPrevious) || (!is_numeric($beginPrevious) && is_numeric($beginPaper));
                        $_paper = [];
                        $_paper['pages'] = $paper['extent'];
                        $_paper['status'] = self::$STATUS[$paper['accessRights']];
                        $_short = JournalsUtils::short($journal, $volume, $number, $_paper['pages']);
                        foreach ($paper['files'] as $type => $file) {
                            $_paper['galleys'][] = self::$FILES[$type]['galley'];
                            $filename = Zord::liveFolder('import').$short.DS.$_short;
                            if (!file_exists(dirname($filename))) {
                                mkdir(dirname($filename), 0777, true);
                            }
                            if ($type === 'pdf') {
                                if (OPEN_EDITION_UPDATE_PDF) {
                                    Zord::execute('exec', 'pdftk '.$file.' cat 2-end output '.$filename.'.pdf');
                                }
                                if (isset($issue['ean']) && $_paper['status'] === 'subscription') {
                                    $_paper['galleys'][] = 'shop';
                                }
                            } else if ($type === 'tei') {
                                $_paper['html'] = $filename.'.html';
                                $_paper['tei']  = $file;
                            }
                        }
                        $doi = null;
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
                            ]
                        ];
                        if (isset($doi)) {
                            $_paper['settings']['pub-id::doi'] = [
                                $_journal->locale => [
                                    'value' => $doi
                                ]
                            ];
                        }
                        $section = $paper['isPartOf']['section'] ?? false;
                        if ($section) {
                            if (!in_array($section, $sections)) {
                                $sections[] = $section;
                            }
                        } else {
                            $section = 'SECTION_'.$short.'_'.(count($sections) + 1);
                            if (count($sections) === 0 || substr($sections[count($sections) - 1], 0, 8) !== 'SECTION_' || $changeNumbering) {
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
                        $previous = $paper;
                    }
                    $issue['papers'] = $papers;
                    foreach ($papers as $paper) {
                        if (isset($paper['tei']) && OPEN_EDITION_UPDATE_HTML) {
                            $this->buildHTML($journal, $issue, $paper, $_journal->locale);
                        }
                    }
                    file_put_contents(Zord::liveFolder('import').$short.'.json', json_encode($issue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    $refs[] = $short;
                }
            }
            if (count($refs) > 0) {
                Zord::getInstance('Import')->execute([
                    'lang' => 'fr-FR',
                    'continue' => true,
                    'refs' => $refs,
                    'steps' => $parameters['steps']
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
    
    protected function buildHTML($journal, $issue, $paper, $locale) {
        $pages = explode('-', $paper['pages']);
        if (count($pages) < 2) {
            $pages[] = $pages[0];
        }
        $models = [
            'lang'         => substr($locale, 0, 2),
            'journalTitle' => Zord::getLocaleValue('title', Zord::value('context', $journal), $locale),
            'issueTitle'   => $issue['settings']['title'][$locale]['value'],
            'sectionTitle' => $paper ['section']['settings']['title'][$locale]['value'],
            'paperTitle'   => $paper['settings']['title'][$locale]['value'],
            'paperSubtitle'=> $paper['settings']['subtitle'][$locale]['value'] ?? null,
            'journal'      => $journal,
            'volume'       => $issue['volume'],
            'year'         => $issue['year'],
            'ean'          => $issue['ean'],
            'doi'          => $paper['settings']['pub-id::doi'][$locale]['value'] ?? null,
            'creators'     => $paper['authors'] ?? [],
            'start'        => $pages[0],
            'end'          => $pages[1],
            'section'      => $paper['section']['name']
        ];
        $models = array_merge($models, $this->tei2html($paper));
        $view = new View('/paper.html', $models);
        $view->setMark(false);
        $html = $view->render();
        file_put_contents($paper['html'], $html);
    }
    
    private function tei2html($paper) {
        $file = $paper['tei'];
        $this->info(1, $file);
        $styles = [];
        $footnotes = [];
        $renditions = simplexml_load_string(file_get_contents($file))->teiHeader->encodingDesc->tagsDecl->children();
        foreach ($renditions as $rendition) {
            $styles['#'.$rendition->attributes('xml',true)->id] = trim(''.$rendition);
        }
        $document = new DOMDocument();
        $document->load($file, self::$XML_PARSE_BIG_LINES);
        $header = Zord::firstElementChild($document->documentElement);
        $text   = Zord::nextElementSibling($header);
        $front  = Zord::firstElementChild($text);
        $body   = Zord::nextElementSibling($front);
        $back   = Zord::nextElementSibling($body);
        $contents = [];
        foreach (['front' => $front, 'body' => $body, 'back' => $back] as $root => $part) {
            if (!isset($part)) {
                continue;
            }
            $fragment = new DOMDocument();
            $fragment->preserveWhiteSpace = false;
            $fragment->formatOutput = true;
            $fragment->loadXML('<div></div>');
            $fragment->replaceChild($fragment->importNode($part, true), $fragment->documentElement);
            $xpath = new DOMXpath($fragment);
            $elements = $xpath->query('//*');
            $replacements = [];
            foreach ($elements as $element) {
                $tag = $element->nodeName;
                if ($tag === 'p' && in_array($element->parentNode->nodeName,[$root,'div']) && $element->getAttribute('rend') === '') {
                    $element->setAttribute('class', 'indent');
                } else if ($tag !== 'note' || $element->getAttribute('place') !== 'foot') {
                    $attributes = [];
                    foreach ($element->attributes as $name => $attribute) {
                        $attributes[$name] = $attribute->value;
                    }
                    foreach ($attributes as $name => $value) {
                        $element->removeAttribute($name);
                        if ($tag === 'cell' && in_array($name, ['rows','cols'])) {
                            $name = $name.'pan';
                        } else {
                            switch ($name) {
                                case 'rendition': {
                                    $value = $styles[$value];
                                    $name = 'style';
                                    break;
                                }
                                case 'rend': {
                                    if ($tag === 'q' && $value === 'quotation') {
                                        $name = 'class';
                                        $value = 'block';
                                    }
                                    if ($tag === 'p' && $value === 'break') {
                                        $name = 'style';
                                        $value = 'text-align: center;';
                                    }
                                    break;
                                }
                                case 'target': {
                                    if ($tag = 'ref') {
                                        $name = 'href';
                                    }
                                    break;
                                }
                                case 'url': {
                                    if ($tag === 'graphic') {
                                        $name = 'src';
                                        $filename = str_replace('tei', pathinfo($paper['html'], PATHINFO_FILENAME).DS.'images', dirname($file)).DS.basename($value);
                                        if (!file_exists($filename)) {
                                            if (!file_exists(dirname($filename))) {
                                                mkdir(dirname($filename), 0777, true);
                                            }
                                            file_put_contents($filename, file_get_contents($value));
                                        }
                                        $value = 'images/'.basename($value);
                                    }
                                    break;
                                }
                                case 'type': {
                                    if ($tag === 'list' && in_array($value, ['ordered','unordered'])) {
                                        $replacements[] = [
                                            'element' => $element,
                                            'tag'     => $value[0].'l'
                                        ];
                                        $name = null;
                                    }
                                    if ($tag === 'note' && $value === 'author') {
                                        $replacements[] = [
                                            'element' => $element,
                                            'parent'  => Zord::firstElementChild($element),
                                            'tag'     => 'p'
                                        ];
                                        $name = 'class';
                                        $value = 'au2';
                                    }
                                    break;
                                }
                                default: {
                                    $name = null;
                                    break;
                                }
                            }
                        }
                        if (isset($name)) {
                            $element->setAttribute($name, $value);
                        }
                    }
                }
            }
            foreach ($replacements as $replacement) {
                $node = $fragment->createElement($replacement['tag']);
                $children = [];
                $parent = $replacement['parent'] ?? $replacement['element'];
                foreach ($parent->childNodes as $child) {
                    $children[] = $child;
                }
                foreach ($replacement['element']->attributes as $attribute) {
                    $node->setAttribute($attribute->nodeName, $attribute->nodeValue);
                }
                foreach ($children as $child) {
                    $node->appendChild($fragment->importNode($child, true));
                }
                $replacement['element']->parentNode->replaceChild($node, $replacement['element']);
            }
            $content = preg_replace_callback(
                '#</?(\w+)#',
                function ($matches) {
                    $start = substr($matches[0], 1, 1) !== '/';
                    $tag = $matches[1];
                    switch ($tag) {
                        case 'row': {
                            $tag = 'tr';
                            break;
                        }
                        case 'cell': {
                            $tag = 'td';
                            break;
                        }
                        case 'hi': {
                            $tag = 'span';
                            break;
                        }
                        case 'item': {
                            $tag = 'li';
                            break;
                        }
                        case 'q': {
                            $tag = 'p';
                            break;
                        }
                        case 'ref': {
                            $tag = 'a';
                            break;
                        }
                        case 'lb': {
                            $tag = 'br';
                            break;
                        }
                        case 'head': {
                            $tag = 'h1';
                            break;
                        }
                        case 'figure': {
                            $tag = 'p';
                            break;
                        }
                        case 'graphic': {
                            $tag = 'img';
                            break;
                        }
                    }
                    return '<'.($start ? '' : '/').$tag;
                },
                preg_replace('#<(\w+) ([\w|\s|:|=|"|\#|/]*)/>#', '', $fragment->saveXML($fragment->documentElement))
            );
            $content = preg_replace('#(\s+)xml:lang="(\w+)"#', '', $content);
            $fragment->loadXML($content);
            $fragment->formatOutput = false;
            $xpath = new DOMXpath($fragment);
            $elements = $xpath->query('//*');
            foreach ($elements as $element) {
                $tag = $element->nodeName;
                if ($tag === 'note' && $element->getAttribute('place') === 'foot') {
                    $footnote = $fragment->saveXML($element);
                    $footnote = preg_replace('#(\s*)<note place="(\w+)" n="(\w+)">(\s*)<p>(\s*)#', '', $footnote);
                    $footnote = preg_replace('#(\s*)</p>(\s*)</note>#', '', $footnote);
                    $num = $element->getAttribute('n');
                    $footnotes['#fn_'.$num] = $footnote;
                    $footnote = $fragment->createElement('sup');
                    $anchor = $fragment->createElement('a', $num);
                    $anchor->setAttribute('id', 'fn_'.$num);
                    $anchor->setAttribute('href', '#fn'.$num);
                    $footnote->appendChild($anchor);
                    $element->parentNode->replaceChild($footnote, $element);
                }
            }
            $content = str_replace("<".$root." xmlns=\"http://www.tei-c.org/ns/1.0\">", '', $fragment->saveXML($fragment->documentElement));
            $content = substr($content, 0, strlen($content) - strlen('</'.$root.'>'));
            $content = str_replace(' xmlns:default="http://www.tei-c.org/ns/1.0"', '', $content);
            $content = preg_replace('#(\s+)<(\w+)#', '<$2', $content);
            $content = preg_replace('#/(\w+)>(\s+)#', '/$1>', $content);
            $content = preg_replace('#<sup>(\s+)<a#', '<sup><a', $content);
            $contents[$root] = $content;
        }
        $contents['footnotes'] = $footnotes;
        return ['contents' => $contents];
    }
}

?>