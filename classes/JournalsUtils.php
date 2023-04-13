<?php

class JournalsUtils {
    
    public static function journal($journal, $locale, $names = null) {
        $settings = self::settings($journal, $locale, $journal, $names);
        $thumbnail = json_decode($settings['homepageImage'], true);
        $models = [
            'path'      => '/'.$journal->context,
            'thumbnail' => '/public/journals/thumbnails/'.$journal->context.'.jpg',
        ];
        if (isset($names) && is_string($names)) {
            $models[$names] = $settings;
        } else {
            $models['settings'] = $settings;
        }
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
    
    public static function name($author) {
        return $author->first.' '.(!empty($author->middle) ? $author->middle.' ' : '').$author->last;
    }
    
    public static function settings($object, $locale, $journal, $names = null) {
        $locales = [$locale, $journal->locale, DEFAULT_LANG];
        $settings = [];
        $setting = null;
        $many = true;
        $criteria = ['object' => $object->id];
        if (isset($names)) {
            if (is_array($names) && !Zord::is_associative($names)) {
                $criteria['name'] = ['in' => $names];
            } else if (is_string($names)) {
                $criteria['name'] = $names;
                $many = false;
            }
        }
        foreach ($locales as $_locale) {
            $criteria['locale'] = $_locale;
            $entity = (new SettingEntity($object->_type));
            if ($many) {
                $entities = $entity->retrieveAll($criteria);
                foreach ($entities as $entity) {
                    if (!isset($settings[$entity->name])) {
                        $settings[$entity->name] = $entity->value;
                    }
                }
            } else {
                $entity = $entity->retrieveOne($criteria);
                if ($entity !== false) {
                    $setting = $entity->value;
                    break;
                }
            }
        }
        return $many ? $settings : $setting;
    }
    
}

?>