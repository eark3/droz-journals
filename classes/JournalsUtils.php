<?php

class JournalsUtils {
    
    public static function journal($journal, $locale) {
        $settings = self::settings('journal', $journal, $locale, $journal);
        $models = [
            'path'      => '/'.$journal->context,
            'thumbnail' => '/public/journals/images/'.$journal->context.'/'.$settings['homepageImage']['uploadName'],
            'settings'  => $settings
        ];
        return $models;
    }
    
    public static function short($context, $issue, $paper = null) {
        $short = $context.'_'.$issue->volume;
        if ($issue->number) {
            $short .= '.'.$issue->number;
        }
        if ($paper) {
            $short .= '_'.$paper->pages;
        }
        return $short;
    }
    
    public static function pages($paper) {
        $pages = $paper->pages;
        $tokens = explode('-', $pages);
        if (count($tokens) === 2 && $tokens[0] === $tokens[1]) {
            $pages = $tokens[0];
        }
        return $pages;
    }
    
    public static function name($author, $reverse = false) {
        $middle = !empty($author->middle) ? $author->middle.' ' : '';
        return $reverse 
            ? $middle.$author->last.', '.$author->first
            : $author->first.' '.$middle.$author->last;
    }
    
    public static function settings($type, $object, $locale, $journal) {
        $_type = 'settings'.DS.$type.DS.$object->id;
        $key = str_replace('-', '_' ,$locale);
        if (Cache::hasItem($_type, $key)) {
            return Cache::getItem($_type, $key);
        }
        $locales = [];
        foreach ([$locale, $journal->locale, DEFAULT_LANG] as $_locale) {
            if (!in_array($_locale, $locales)) {
                $locales[] = $_locale;
            }
        }
        $settings = [];
        $criteria = ['object' => $object->id];
        foreach ($locales as $_locale) {
            $criteria['locale'] = $_locale;
            $entities = (new SettingEntity($type))->retrieveAll($criteria);
            foreach ($entities as $entity) {
                if (!isset($settings[$entity->name])) {
                    $value = $entity->value;
                    switch ($entity->content) {
                        case 'object': {
                            $value = unserialize($value);
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
        Cache::setItem($_type, $key, $settings);
        return $settings;
    }
    
}

?>