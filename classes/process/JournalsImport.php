<?php

use \GuzzleHttp\Client;
use \GuzzleHttp\Exception\RequestException;

class JournalsImport extends Import {
    
    protected $journals = [];
    protected $journal  = null;
    protected $settings = null;
    protected $issue    = null;
    protected $short    = null;
    protected $empty    = false;
    protected $new      = false;
    protected $cache    = null;
    
    private function journal($issue) {
        $tokens = explode('_', $issue);
        if (count($tokens) !== 2) {
            return false;
        }
        list($journal,$issue) = $tokens;
        $tokens = explode('-', $issue);
        foreach ($tokens as $issue) {
            if (!is_numeric($issue)) {
                return false;
            }
        }
        return $this->journals[$journal] ?? false;
    }
    
    private function purge($type, $issue, $paper = null) {
        $key = str_replace('-', '_' ,JournalsUtils::short($this->journal->context, $issue->volume, $issue->number, $paper->pages ?? null, true));
        if ($this->cache->hasItem($type, $key)) {
            $this->cache->deleteItem($type, $key);
        }
    }
    
    protected function configure($parameters = []) {
        parent::configure($parameters);
        $this->cache = Cache::instance();
        foreach ((new JournalEntity())->retrieveAll() as $journal) {
            $this->journals[$journal->context] = $journal;
        }
        if (!isset($this->refs)) {
            $set = $this->folder.'*';
            $issues = glob($set.'.json');
            $papers = glob($set, GLOB_ONLYDIR);
            $this->refs = [];
            foreach ([$issues, $papers] as $items) {
                foreach ($items as $item) {
                    if (is_file($item)) {
                        $issue = pathinfo($item, PATHINFO_FILENAME);
                    } else if (is_dir($item)) {
                        $issue = pathinfo($item, PATHINFO_BASENAME);
                    }
                    $journal = $this->journal($issue);
                    if ($journal === false) {
                        continue;
                    }
                    if (!in_array($issue, $this->refs)) {
                        $this->refs[] = $issue;
                    }
                }
            }
        }
    }
    
    protected function resetRef($ean) {
        parent::resetRef($ean);
        $this->journal = null;
        $this->settings = null;
        $this->issue = null;
        $this->short = null;
        $this->empty = false;
        $this->new   = false;
    }
    
    protected function metadata($ean) {
        if (empty($this->issue)) {
            $this->info(2, $this->locale->messages->metadata->info->nodata);
            return true;
        }
        $this->issue["journal"] = $this->journal->id;
        $_issue = JournalsUtils::import('issue', $this->issue);
        $this->purge('issue', $_issue);
        if ($this->issue['reset'] ?? false) {
            (new PaperEntity())->delete(['issue' => $_issue->id]);
        }
        foreach ($this->issue['papers'] as $paper) {
            $paper['journal'] = $this->journal->id;
            $paper['issue']   = $_issue->id;
            $name = $paper['section'];
            $tokens = explode(':', $name);
            if (count($tokens) === 3) {
                list($name, $place, $title) = $tokens;
            }
            $_section = (new SectionEntity())->retrieveOne([
                'journal' => $this->journal->id,
                'name'    => $name
            ]);
            if ($_section === false && isset($place) && isset($title)) {
                $_section = JournalsUtils::create(new SectionEntity(), [
                    'journal'  => $this->journal->id,
                    'name'     => $name,
                    'place'    => $place,
                    'settings' => ['title' => [$this->journal->locale => ['value' => $title]]]
                ]);
            }
            $paper['section'] = $_section->id;
            $_paper = JournalsUtils::import('paper', $paper);
            $this->purge('paper', $_issue, $_paper);
            $this->info(2, "paper : ".JournalsUtils::short($this->journal->context, $_issue->volume, $_issue->number, $_paper->pages));
            if ($paper['reset'] ?? false) {
                foreach (explode(',', $paper['reset']) as $reset) {
                    switch ($reset) {
                        case 'authors': {
                            (new AuthorEntity())->delete(['paper' => $_paper->id]);
                            break;
                        }
                        case 'galleys': {
                            (new GalleyEntity())->delete(['paper' => $_paper->id]);
                            break;
                        }
                    }
                }
            }
            $authors = [];
            foreach ($paper['authors'] as $author) {
                $author['paper'] = $_paper->id;
                $_author = JournalsUtils::import('author', $author);
                $authors[] = JournalsUtils::name($_author);
            }
            if (!empty($authors)) {
                $this->info(3, "authors : ".implode(', ', $authors));
            };
            $galleys = [];
            foreach ($paper['galleys'] as $type) {
                JournalsUtils::import('galley', [
                    'type'  => $type,
                    'paper' => $_paper->id
                ]);
                $galleys[] = $type;
            }
            if (!empty($galleys)) {
                $this->info(3, "galleys : ".implode(', ', $galleys));
            }
        }
        return true;
    }
    
    protected function folders($ean) {
        if ($this->empty) {
            return null;
        }
        list($journal,$issue) = explode('_', $ean);
        return [
            $this->folder.$ean,
            STORE_FOLDER.'journals'.DS.$journal.DS.$issue
        ];
    }
    
    protected function contents($ean) {
        if ($this->empty) {
            return null;
        }
        $contents = [];
        $issue    = (new IssueEntity())->retrieveOne($ean);
        $journal  = (new JournalEntity())->retrieveOne($issue->journal);
        $papers   = (new PaperEntity())->retrieveAll(['issue' => $issue->id]);
        $context  = $journal->context;
        $date     = $issue->date;
        foreach ($papers as $paper) {
            $authors = [];
            foreach ((new AuthorEntity())->retrieveAll(['paper' => $paper->id]) as $author) {
                $authors[] = Zord::collapse(JournalsUtils::name($author), false);
            }
            $authors = implode(' ', $authors);
            $short = JournalsUtils::short($journal->context, $issue->volume, $issue->number, $paper->pages);
            foreach (['html','pdf'] as $type) {
                $name = $paper->pages.'_'.$type;
                $file = JournalsUtils::path($journal->context, $issue->volume, $issue->number, $paper->pages, $type);
                if (file_exists($file)) {
                    $this->info(2, basename($file));
                    if ($type === 'pdf') {
                        Zord::execute('exec', PDFTOTEXT_COMMAND, ['file' => $file]);
                        $file = str_replace('.pdf', '.txt', $file);
                    }
                    $content = Store::align(file_get_contents($file), $type, true);
                    $contents[] = [
                        'name'    => $name,
                        'short'   => $short,
                        'type'    => $type,
                        'date'    => $date,
                        'journal' => $context,
                        'authors' => $authors,
                        'content' => $content
                    ];
                }
            }
        }
        return $contents;
    }
    
    protected function check($ean) {
        $this->journal = $this->journal($ean);
        if ($this->journal === false) {
            $this->error(3, Zord::substitute($this->locale->messages->check->error->ref->wrong, ['ref' => $ean]));
            return false;
        }
        $this->issue = Zord::arrayFromJSONFile($this->folder.$ean.'.json');
        if (empty($this->issue) && !file_exists($this->folder.$ean)) {
            $this->error(3, Zord::substitute($this->locale->messages->check->error->ref->wrong, ['ref' => $ean]));
            return false;
        }
        $this->short = JournalsUtils::short($this->journal->context, $this->issue['volume'], $this->issue['number'] ?? null);
        $this->settings = JournalsUtils::settings('journal', $this->journal, $this->journal->locale);
        $result = true;
        list($source,$target) = $this->folders($ean);
        foreach ($this->issue['papers'] ?? [] as $index => $paper) {
            if (empty($paper['pages'])) {
                $this->error(3, $this->locale->messages->check->error->missing->pages.' ('.$index.')');
                $result &= false;
                continue;
            }
            $short = JournalsUtils::short($this->journal->context, $this->issue['volume'], $this->issue['number'], $paper['pages']);
            $section = $paper['section'] ?? null;
            if ($section) {
                $tokens = explode(':', $section);
                if (count($tokens) === 1) {
                    $name = $section;
                } else if (count($tokens) === 3) {
                    list($name, $place, $title) = $tokens;
                }
                $_section = isset($name) ? (new SectionEntity())->retrieveOne([
                    'journal' => $this->journal->id,
                    'name'    => $name
                ]) : false;
                if ($_section === false && (!isset($name) || !isset($place) || !isset($title))) {
                    $this->error(3, Zord::substitute($this->locale->messages->check->error->missing->section, ['section' => $section]));
                    $result &= false;
                }
            } else {
                $this->error(3, Zord::substitute($this->locale->messages->check->error->without->section, ['paper' => $short]));
                $result &= false;
            }
            foreach ($paper['galleys'] ?? [] as $galley) {
                if ($galley === 'shop') {
                    if (!isset($this->issue['ean'])) {
                        $this->error(3, $this->locale->messages->check->error->missing->ean);
                        $result &= false;
                    }
                } else {
                    $found = false;
                    foreach ([$source,$target] as $folder) {
                        $file = $folder.DS.$short.'.'.$galley;
                        if (file_exists($file) && is_file($file) && is_readable($file)) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $this->error(3, Zord::substitute($this->locale->messages->check->error->missing->file, ['file' => $ean.DS.$short.'.'.$galley]));
                        $result &= false;
                    }
                }
            }
        }
        $this->empty = !file_exists($this->folder.$ean);
        $this->new = ((new IssueEntity())->retrieveOne([
            'journal' => $this->journal->id,
            'volume'  => $this->issue['volume'],
            'number'  => $this->issue['number'] ?? null
        ]) === false);
        return $result;
    }
    
    protected function crossref($ean) {
        $connection = Zord::value('connection', 'crossref');
        $filename = Zord::liveFolder('build', true).'crossref_'.$ean.'.xml';
        $models = [];
        $issue = (new IssueEntity())->retrieveOne($ean);
        if ($issue === false) {
            $this->error(2, $this->locale->messages->crossref->error->issue->unknown);
            return false;
        }
        $journal = (new JournalEntity())->retrieveOne($issue->journal);
        if ($journal === false) {
            $this->error(2, $this->locale->messages->crossref->error->journal->unknown);
            return false;
        }
        $models['baseURL'] = Zord::getContextURL($journal->context);
        $settings = JournalsUtils::settings('journal', $journal, $journal->locale);
        $models['journal'] = [
            'title'  => $settings['name'],
            'abbrev' => $journal->context,
            'issn'   => [
                'print'  => $settings['printIssn'],
                'online' => $settings['onlineIssn'],
            ]
        ];
        $settings = JournalsUtils::settings('issue', $issue, $journal->locale);
        $published = strtotime($issue->published);
        $models['issue'] = [
            'short'  => JournalsUtils::short($journal->context, $issue->volume, $issue->number),
            'date'   => [
                'year'  => date('Y', $published),
                'month' => date('m', $published),
                'day'   => date('d', $published)
            ],
            'volume' => $issue->volume,
            'number' => $issue->number,
            'ean'    => $issue->ean
        ];
        $papers = (new PaperEntity())->retrieveAll(['issue' => $issue->id]);
        foreach ($papers as $paper) {
            $settings = JournalsUtils::settings('paper', $paper, $journal->locale);
            if ($settings['pub-id::doi'] ?? false) {
                $this->info(2, $settings['pub-id::doi']);
                list($start, $end) = JournalsUtils::pages($paper, true);
                $article = [
                    'title'    => $settings['title'],
                    'abstract' => $settings['abstract'] ?? null,
                    'start'    => $start,
                    'end'      => $end,
                    'doi'      => $settings['pub-id::doi'],
                    'short'    => JournalsUtils::short($journal->context, $issue->volume, $issue->number, $paper->pages)
                ];
                $authors = (new AuthorEntity())->retrieveAll(['paper' => $paper->id]);
                foreach ($authors as $author) {
                    $article['authors'][] = [
                        'first' => $author->first,
                        'last'  => $author->last
                    ];
                }
                $models['articles'][] = $article;
            }
        }
        if (empty($models['articles'])) {
            $this->info(2, $this->locale->messages->crossref->info->file->empty);
            return true;
        }
        $this->info(2, $filename);
        file_put_contents($filename, (new View('/xml/crossref', Zord::array_map_recursive(function($item) {return htmlentities(str_replace('&nbsp;', ' ', strip_tags($item)), ENT_XML1, 'UTF-8');}, $models)))->render());
        $httpClient = new Client($connection['config']);
        $multipart = [];
        foreach ($connection['parameters'] as $name => $contents) {
            $multipart[] = ['name' => $name, 'contents' => $contents];
        }
        $multipart[] = ['name' => 'mdFile', 'contents' => fopen($filename, 'r')];
        try {
            $this->info(2, $this->locale->messages->crossref->info->file->sending, false, true);
            $httpClient->request('POST', $connection['url'], ['multipart' => $multipart]);
            $this->report(0, 'OK', 'OK');
        } catch(RequestException $error) {
            $this->report(0, 'KO', 'KO');
            $returnMessage = $error->getMessage();
            if ($error->hasResponse()) {
                $responseBody = $error->getResponse()->getBody(true);
                $statusCode = $error->getResponse()->getStatusCode();
                if ($statusCode == 403) {
                    $xmlDoc = new DOMDocument();
                    $xmlDoc->loadXML($responseBody);
                    $msg = $xmlDoc->getElementsByTagName('msg')->item(0)->nodeValue;
                    $returnMessage = $msg.' ('.$statusCode.' '.$error->getResponse()->getReasonPhrase().')';
                } else {
                    $returnMessage = $responseBody.' ('.$statusCode.' '.$error->getResponse()->getReasonPhrase().')';
                }
            }
            $this->error(2, $returnMessage);
            return false;
        }
        return true;
    }
    
    protected function notify($ean) {
        if (true) {
            $batch = [];
            $recipients = [];
            $index = 1;
            foreach ((new UserHasRoleEntity())->retrieveAll([
                'role'    => 'reader',
                'context' => $this->journal->context
            ]) as $role) {
                if ($index > MAX_MAIL_RECIPIENTS) {
                    $batch[] = $recipients;
                    $recipients = [];
                    $index = 1;
                }
                $user = (new UserEntity())->retrieveOne($role->user);
                if ($user !== false) {
                    $recipients['bcc'][$user->email] = $user->name;
                    $index++;
                }
            }
            $batch[] = $recipients;
            $models = [
                'context' => $this->journal->context
            ];
            $mail = [
                'category' => 'issue'.DS.$this->short,
                'template' => '/mail/issue/publication',
                'subject'  => Zord::getLocaleValue('title', Zord::value('context', 'root'), $this->journal->locale).' - '.$this->settings['name'],
                'models'   => $models
            ];
            foreach ($batch as $recipients) {
                $mail['recipients'] = $recipients;
                $this->sendMail($mail);
            }
            $this->info(2, $this->locale->messages->notify->info->mail->sent);
        } else {
            $this->info(2, $this->locale->messages->notify->info->mail->useless);
        }
        return true;
    }
}

?>