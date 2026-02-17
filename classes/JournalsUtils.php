<?php

class JournalsUtils {
    
    public static function short($context, $volume = null, $number = null, $pages = null, $align = false) {
        if (!isset($volume)) {
            return $context;
        }
        if ($align) {
            $volume = Zord::str_pad($volume, 3, '0', STR_PAD_LEFT);
        }
        $short = $context.'_'.$volume;
        if ($number) {
            if ($align) {
                $tokens = explode('-', $number);
                foreach ($tokens as $index => $token) {
                    $tokens[$index] = Zord::str_pad($token, 2, '0', STR_PAD_LEFT);
                }
                $number = implode('-', $tokens);
            }
            $short .= '.'.$number;
        }
        if ($pages) {
            if ($align) {
                $tokens = explode('-', $pages);
                foreach ($tokens as $index => $token) {
                    $tokens[$index] = Zord::str_pad($token, 3, '0', STR_PAD_LEFT);
                }
                $pages = implode('-', $tokens);
            }
            $short .= '_'.$pages;
        }
        return $short;
    }
    
    public static function chunks($short) {
        $tokens = explode('_', $short);
        $chunks = [];
        if (count($tokens) > 0) {
            $chunks['journal'] = $tokens[0];
        }
        if (count($tokens) > 1) {
            $_tokens = explode('.', $tokens[1]);
            if (count($_tokens) > 0) {
                $chunks['volume'] = $_tokens[0];
            }
            $chunks['number'] = count($_tokens) > 1 ?  $_tokens[1] : null;
        }
        if (count($tokens) > 2) {
            $chunks['pages'] = $tokens[2];
        }
        return [
            $chunks['journal'] ?? null,
            $chunks['volume']  ?? null,
            $chunks['number']  ?? null,
            $chunks['pages']   ?? null
        ];
    }
    
    public static function pages($paper, $explode = false) {
        $pages = $paper->pages;
        $tokens = explode('-', $pages);
        if (count($tokens) === 2 && $tokens[0] === $tokens[1]) {
            $pages = $tokens[0];
        }
        return !$explode ? $pages : [$tokens[0], count($tokens) > 1 ? $tokens[1] : $tokens[0]];
    }
    
    public static function name($author, $reverse = false) {
        $first = !empty($author->first ?? $author['first']) ? ($author->first ?? $author['first']) : '';
        $middle = !empty($author->middle ?? $author['middle']) ? ($author->middle ?? $author['middle']) : '';
        $last = !empty($author->last ?? $author['last']) ? ($author->last ?? $author['last']) : '';
        return $reverse 
            ? $middle.(!empty($middle) ? ' ' : '').$last.(!empty($first) ? ', ' : '').$first
            : $first.(!empty($first) ? ' ' : '').$middle.(!empty($middle) ? ' ' : '').$last;
    }
    
    public static function status($issue, $paper) {
        return (($issue->open ?? $issue['open']) < date('Y-m-d') || ($paper->status ?? $paper['status']) === 'free') ? 'free' : 'subscription';
    }
    
    public static function reader($user, $journal) {
        return is_bool($user) ? $user : $user->hasRole('reader', $journal->context);
    }
    
    public static function readable($user, $journal, $issue, $paper) {
        return self::reader($user, $journal) || self::status($issue, $paper) === 'free';
    }
    
    public static function path($journal, $volume, $number, $pages, $type, $resource = null) {
        $short = JournalsUtils::short($journal, $volume, $number, $pages);
        return STORE_FOLDER.'journals'.DS.$journal.DS.$volume.(isset($number) ? '.'.$number : '').DS.$short.(isset($resource) ? DS.$type.DS.$resource : '.'.$type);
    }
    
    public static function create($entity, $data) {
        foreach ($data as $key => $value) {
            if ($value === '__IGNORE__') {
                unset($data[$key]);
            }
        }
        $object = $entity->create($data);
        if ($object) {
            foreach ($data['settings'] ?? [] as $name => $locales) {
                foreach ($locales as $locale => $item) {
                    (new SettingEntity($entity->_type))->create([
                        "object"  => $object->id,
                        "name"    => $name,
                        "value"   => $item['value'],
                        "content" => $item['content'] ?? 'string',
                        "locale"  => $locale
                    ]);
                }
            }
        }
        return $object;
    }
    
    public static function update($entity, $object, $data) {
        foreach ($data as $key => $value) {
            if ($value === '__IGNORE__') {
                unset($data[$key]);
            }
        }
        $object = $entity->update($object->id, $data);
        if ($object) {
            foreach ($data['settings'] ?? [] as $name => $locales) {
                foreach ($locales as $locale => $item) {
                    $key = [
                        "object"  => $object->id,
                        "name"    => $name,
                        "locale"  => $locale
                    ];
                    $_data = [
                        'value'   => $item['value'],
                        'content' => $item['content'] ?? 'string'
                    ];
                    $setting = (new SettingEntity($entity->_type))->retrieveOne($key);
                    if ($setting === false) {
                        (new SettingEntity($entity->_type))->create(array_merge($key, $_data));
                    } else {
                        (new SettingEntity($entity->_type))->update($setting->id, $_data);
                    }
                }
            }
        }
        return $object;
    }
    
    public static function import($type, $data) {
        $class = ucfirst($type).'Entity';
        $fields = Zord::value('import', ['fields',$type]);
        $key = [];
        $_data = $data;
        foreach ($data as $field => $value) {
            if (in_array($field, $fields)) {
                $key[$field] = $value;
                unset($_data[$field]);
            }
        }
        $object = (new $class())->retrieveOne($key);
        if ($object === false) {
            $object = self::create(new $class(), array_merge($key, $_data));
        } else {
            $object = self::update(new $class(), $object, $_data);
        }
        return $object;
    }
    
    public static function url($context, $issue = null, $paper = null, $type = 'meta') {
        $ean    = ($issue->ean    ?? ($issue['ean']    ?? null));
        $volume = ($issue->volume ?? ($issue['volume'] ?? null));
        $number = ($issue->number ?? ($issue['number'] ?? null));
        $pages  = ($paper->pages  ?? ($paper['pages']  ?? null));
        $short  = $issue ? JournalsUtils::short($context, $volume, $number, $pages) : null;
        $baseURL = Zord::getContextURL($context);
        switch ($type) {
            case 'meta': {
                if ($short && $pages) {
                    return $baseURL.'/article/view/'.$short;
                } else if ($short) {
                    return $baseURL.'/issue/view/'.$short.(is_string($paper) ? '#'.$paper : '');
                } else {
                    return $baseURL;
                }
            }
            case 'html': 
            case 'pdf': {
                return $baseURL.'/article/view/'.$short.'/'.$type;
            }
            case 'shop': {
                return SHOP_BASE_URL.'/product/'.$ean.'/'.$short;
            }
        }
    }
    
    public static function serial($issue) {
        $serial = 'Vol. '.$issue->volume;
        if ($issue->number) {
            $serial .= ' n° '.$issue->number;
        }
        if ($issue->year) {
            $serial .= ' ('.$issue->year.')';
        }
        return $serial;
    }
    
    public static function settings($type, $object, $locales) {
        if (!is_array($locales)) {
            if (is_string($locales)) {
                $locales = [$locales];
            } else {
                $locales = [];
            }
        }
        if (!in_array(DEFAULT_LANG, $locales)) {
            $locales[] = DEFAULT_LANG;
        }
        $settings = [];
        if (isset($object) && $object !== false) {
            $criteria = ['object' => $object->id, 'order' => ['asc' => 'name']];
            $settings['type'] = $type;
            foreach ($object->as_array() as $key => $value) {
                $settings[$key] = $value;
            }
            if ($type === 'paper') {
                foreach ((new GalleyEntity())->retrieveAll(['paper' => $object->id]) as $galley) {
                    $settings['galleys'][] = $galley->type;
                }
            }
            foreach ($locales as $_locale) {
                $criteria['locale'] = $_locale;
                $entities = (new SettingEntity($type))->retrieveAll($criteria);
                foreach ($entities as $entity) {
                    if (!isset($settings[$entity->name])) {
                        $value = $entity->value;
                        switch ($entity->content) {
                            case 'object': {
                                $value = unserialize(base64_decode($value));
                                break;
                            }
                            case 'bool': {
                                $value = (boolean) $value;
                                break;
                            }
                            case 'int': {
                                $value = (int) $value;
                                break;
                            }
                        }
                        $settings[$entity->name] = $value;
                    }
                }
            }
        }
        return $settings;
    }
    
    public static function place($pages, $dossier) {
        $place = explode('-', $pages)[0];
        if ($dossier) {
            $place = $place + 3000;
        } else {
            if (!is_numeric($place)) {
                $place = Zord::roman2number($place);
                $place = $place + 1000;
            } else {
                $place = $place + 2000;
            }
        }
        return $place;
    }
    
    public static function export($issue) {
        $content = [
            'volume'    => $issue->volume,
            'number'    => $issue->number,
            'year'      => $issue->year,
            'ean'       => $issue->ean,
            'published' => $issue->published,
            'modified'  => $issue->modified,
            'open'      => $issue->open
        ];
        $papers = (new PaperEntity())->retrieveAll(['issue' => $issue->id]);
        foreach ($papers as $paper) {
            $section = (new SectionEntity())->retrieveOne($paper->section);
            $settings = (new SettingEntity('section'))->retrieveAll(['object' => $section->id]);
            $_settings = [];
            foreach ($settings as $setting) {
                $_settings[$setting->name][$setting->locale] = [
                    'content' => $setting->content,
                    'value'   => $setting->value
                ];
            }
            $_section = [
                'name'     => $section->name,
                'settings' => $_settings
            ];
            $parent = (new SectionEntity())->retrieveOne($section->parent);
            if ($parent) {
                $_section['parent'] = $parent->name;
            }
            $authors = (new AuthorEntity())->retrieveAll(['paper' => $paper->id]);
            $_authors = [];
            foreach ($authors as $author) {
                $settings = (new SettingEntity('author'))->retrieveAll(['object' => $author->id]);
                $_settings = [];
                foreach ($settings as $setting) {
                    $_settings[$setting->name][$setting->locale] = [
                        'content' => $setting->content,
                        'value'   => $setting->value
                    ];
                }
                $_author = [
                    'first'    => $author->first,
                    'middle'   => $author->middle,
                    'last'     => $author->last,
                    'email'    => $author->email,
                    'place'    => $author->place,
                    'settings' => $_settings
                ];
                $_authors[] = $_author;
            }
            $galleys = (new GalleyEntity())->retrieveAll(['paper' => $paper->id]);
            $_galleys = [];
            foreach ($galleys as $galley) {
                $_galleys[] = $galley->type;
            }
            $settings = (new SettingEntity('paper'))->retrieveAll(['object' => $paper->id]);
            $_settings = [];
            foreach ($settings as $setting) {
                $_settings[$setting->name][$setting->locale] = [
                    'content' => $setting->content,
                    'value'   => $setting->value
                ];
            }
            $_paper = [
                'pages'    => $paper->pages,
                'status'   => $paper->status,
                'section'  => $_section,
                'place'    => $paper->place,
                'views'    => $paper->views,
                'authors'  => $_authors,
                'galleys'  => $_galleys,
                'settings' => $_settings
            ];
            $content['papers'][] = $_paper;
        }
        $settings = (new SettingEntity('issue'))->retrieveAll(['object' => $issue->id]);
        foreach ($settings as $setting) {
            $content['settings'][$setting->name][$setting->locale] = [
                'content' => $setting->content,
                'value'   => $setting->value
            ];
        }
        return $content;
    }
    
    public static function flat($name) {
        return strip_tags(str_replace(['<br/>','&nbsp;'], ' ', $name));
    }
    
    public static function find($short) {
        $none = [false, false, false];
        $journal = null;
        $issue = null;
        $pages = null;
        $tokens = explode('_', $short);
        if (!in_array(count($tokens), [1,2,3])) {
            return $none;
        }
        if (count($tokens) > 2) {
            list($journal, $issue, $pages) = $tokens;
        } else if (count($tokens) > 1) {
            list($journal, $issue) = $tokens;
        } else {
            $journal = $short;
        }
        $journal = (new JournalEntity())->retrieveOne(['context' => $journal]);
        if ($journal && $issue) {
            $volume = null;
            $number = null;
            $tokens = explode('.', $issue);
            if (!in_array(count($tokens), [1,2])) {
                return $none;
            }
            if (count($tokens) === 2) {
                list($volume, $number) = $tokens;
            } else {
                $volume = $issue;
            }
            $issue = (new IssueEntity())->retrieveOne([
                'journal' => $journal->id,
                'volume'  => $volume,
                'number'  => $number
            ]);
            if ($issue && $pages) {
                $paper = (new PaperEntity())->retrieveOne([
                    'issue' => $issue->id,
                    'pages' => $pages
                ]);
                if ($paper === false) {
                    return $none;
                }
            }
        }
        return [$journal, $issue, $paper ?? null];
    }
    
    public static function expand($short) {
        $tokens = explode('_', $short);
        $journal = $tokens[0];
        $issue = null;
        $pages = null;
        if (count($tokens) > 1) {
            $issue = $tokens[1];
            if (strpos($issue, '.') > 0) {
                $issue = explode('.', $issue);
                $issue = Zord::str_pad($issue[0], 3, '0', STR_PAD_LEFT).'.'.Zord::str_pad($issue[1], 2, '0', STR_PAD_LEFT);
            } else {
                $issue = Zord::str_pad($issue, 3, '0', STR_PAD_LEFT);
            }
            if (count($tokens) > 2) {
                $pages = $tokens[2];
                if (strpos($pages, '-') > 0) {
                    $pages = explode('-', $pages);
                    $pages = Zord::str_pad($pages[0], 3, '0', STR_PAD_LEFT).'_'.Zord::str_pad($pages[1], 3, '0', STR_PAD_LEFT);
                } else {
                    $pages = Zord::str_pad($pages, 3, '0', STR_PAD_LEFT);
                }
            }
        }
        return $journal.(isset($issue) ? '_'.$issue : '').(isset($pages) ? '_'.$pages: '');
    }
    
    public static function shrink($short) {
        $tokens = explode('_', $short);
        $journal = $tokens[0];
        $issue = null;
        $pages = null;
        if (count($tokens) > 1) {
            $issue = ltrim($tokens[1], '0');
            if (strpos($issue, '.') > 0) {
                $issue = explode('.', $issue);
                $issue = $issue[0].'.'.ltrim($issue[1], '0');
            }
            if (count($tokens) > 2) {
                $pages = ltrim($tokens[2], '0');
                if (count($tokens) > 3) {
                    $pages .= '-'.ltrim($tokens[3], '0');
                } else {
                    $pages = explode('-', $pages);
                    if (count($pages) > 1) {
                        $pages = $pages[0].'-'.ltrim($pages[1], '0');
                    } else {
                        $pages = $pages[0];
                    }
                }
            }
        }
        return $journal.(isset($issue) ? '_'.$issue : '').(isset($pages) ? '_'.$pages : '');
    }
    
    public static function stats($journal, $year) {
        $tabs = [
            'Données brutes',
            'Par mois',
            'Par mois et par numéro',
            'Par mois et par article',
            'Par format et par numéro',
            'Par format et par article'
        ];
        $queries = (new UserHasQueryEntity())->retrieveAll([
            'journal' => $journal->context,
            'paper'   => '__NOT_NULL__',
            'when'    => ['>=' => $year.'-01-01'],
            'when'    => ['<' => ((int) $year + 1).'-01-01']
        ]);
        $counts = array_combine($tabs, array_fill(0, count($tabs), []));
        foreach ($queries as $query) {
            $month = date('m', strtotime($query->when));
            $counts['Données brutes'][$month][$query->paper][$query->display] = ($counts['Données brutes'][$month][$query->paper][$query->display] ?? 0) + 1;
            $counts['Par mois'][$month] = ($counts['Par mois'][$month] ?? 0) + 1;
            $counts['Par mois et par numéro'][$query->issue][$month] = ($counts['Par mois et par numéro'][$query->issue][$month] ?? 0) + 1;
            $counts['Par mois et par article'][$query->paper][$month] = ($counts['Par mois et par article'][$query->paper][$month] ?? 0) + 1;
            $counts['Par format et par numéro'][$query->issue][$query->display] = ($counts['Par format et par numéro'][$query->issue][$query->display] ?? 0) + 1;
            $counts['Par format et par article'][$query->paper][$query->display] = ($counts['Par format et par article'][$query->paper][$query->display] ?? 0) + 1;
        }
        $array = $counts['Par mois'];
        ksort($array);
        $counts['Par mois'] = $array;
        $stats = array_combine($tabs, array_fill(0, count($tabs), []));
        foreach ($tabs as $tab) {
            switch ($tab) {
                case 'Données brutes': {
                    foreach (['01','02','03','04','05','06','07','08','09','10','11','12'] as $month) {
                        foreach ((new IssueEntity())->retrieveAll([
                            'journal' => $journal->id,
                            'order'   => [['ASC' => 'volume'],['ASC' => 'number']]
                        ]) as $issue) {
                            foreach ((new PaperEntity())->retrieveAll([
                                'journal' => $journal->id,
                                'issue'   => $issue->id,
                                'order'   => ['ASC' => 'place']
                            ]) as $paper) {
                                $short = JournalsUtils::short($journal->context, $issue->volume, $issue->number, $paper->pages);
                                foreach (['','html','pdf'] as $display) {
                                    $count = $counts[$tab][$month][$short][$display] ?? null;
                                    if (!empty($count)) {
                                        $stats[$tab][] = [strtoupper($display),$short,$journal->context,$year.$month,$count,JournalsUtils::short($journal->context, $issue->volume, $issue->number)];
                                    }
                                }
                            }
                        }
                    }
                    break;
                }
                case 'Par mois': {
                    foreach ($counts[$tab] as $month => $count) {
                        $stats[$tab][] = [$year.$month,$count];
                    }
                    break;
                }
                case 'Par mois et par numéro': {
                    foreach ((new IssueEntity())->retrieveAll([
                        'journal' => $journal->id,
                        'order'   => [['ASC' => 'volume'],['ASC' => 'number']]
                    ]) as $issue) {
                        $short = JournalsUtils::short($journal->context, $issue->volume, $issue->number);
                        if (!empty($counts[$tab][$short])) {
                            $values = [$short];
                            foreach (['01','02','03','04','05','06','07','08','09','10','11','12'] as $month) {
                                $values[] = $counts[$tab][$short][$month] ?? '';
                            }
                            $stats[$tab][] = $values;
                        }
                    }
                    break;
                }
                case 'Par mois et par article': {
                    foreach ((new IssueEntity())->retrieveAll([
                        'journal' => $journal->id,
                        'order'   => [['ASC' => 'volume'],['ASC' => 'number']]
                    ]) as $issue) {
                        foreach ((new PaperEntity())->retrieveAll([
                            'journal' => $journal->id,
                            'issue'   => $issue->id,
                            'order'   => ['ASC' => 'place']
                        ]) as $paper) {
                            $short = JournalsUtils::short($journal->context, $issue->volume, $issue->number, $paper->pages);
                            if (!empty($counts[$tab][$short])) {
                                $values = [$short];
                                foreach (['01','02','03','04','05','06','07','08','09','10','11','12'] as $month) {
                                    $values[] = $counts[$tab][$short][$month] ?? '';
                                }
                                $stats[$tab][] = $values;
                            }
                        }
                    }
                    break;
                }
                case 'Par format et par numéro': {
                    foreach ((new IssueEntity())->retrieveAll([
                        'journal' => $journal->id,
                        'order'   => [['ASC' => 'volume'],['ASC' => 'number']]
                    ]) as $issue) {
                        $short = JournalsUtils::short($journal->context, $issue->volume, $issue->number);
                        if (!empty($counts[$tab][$short])) {
                            $values = [$short];
                            foreach (['html','pdf',''] as $format) {
                                $values[] = $counts[$tab][$short][$format] ?? '';
                            }
                            $stats[$tab][] = $values;
                        }
                    }
                    break;
                }
                case 'Par format et par article': {
                    foreach ((new IssueEntity())->retrieveAll([
                        'journal' => $journal->id,
                        'order'   => [['ASC' => 'volume'],['ASC' => 'number']]
                    ]) as $issue) {
                        foreach ((new PaperEntity())->retrieveAll([
                            'journal' => $journal->id,
                            'issue'   => $issue->id,
                            'order'   => ['ASC' => 'place']
                        ]) as $paper) {
                            $short = JournalsUtils::short($journal->context, $issue->volume, $issue->number, $paper->pages);
                            if (!empty($counts[$tab][$short])) {
                                $values = [$short];
                                foreach (['html','pdf',''] as $format) {
                                    $values[] = $counts[$tab][$short][$format] ?? '';
                                }
                                $stats[$tab][] = $values;
                            }
                        }
                    }
                    break;
                }
            }
        }
        return $stats;
    }
    
}

?>