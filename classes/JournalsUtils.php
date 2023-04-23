<?php

class JournalsUtils {
    
    public static function short($context, $volume, $number, $pages = null, $align = false) {
        if ($align) {
            $volume = Zord::str_pad($volume, 3, '0', STR_PAD_LEFT);
        }
        $short = $context.'_'.$volume;
        if ($number) {
            if ($align) {
                $number = Zord::str_pad($number, 2, '0', STR_PAD_LEFT);
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
    
    public static function status($issue, $paper) {
        return (($issue->open ?? $issue['open']) < date('Y-m-d') || ($paper->status ?? $paper['status']) === 'free') ? 'free' : 'subscription';
    }
    
    public static function reader($user, $journal) {
        return is_bool($user) ? $user : $user->hasRole('reader', $journal->context);
    }
    
    public static function readable($user, $journal, $issue, $paper) {
        return self::reader($user, $journal) || self::status($issue, $paper) === 'free';
    }
    
    public static function path($journal, $volume, $number, $pages, $type) {
        $short = JournalsUtils::short($journal, $volume, $number, $pages, true);
        return STORE_FOLDER.'journals'.DS.$journal.DS.$volume.(isset($number) ? '.'.$number : '').DS.$short.'.'.$type;
    }
    
    public static function create($entity, $data) {
        $object = $entity->create($data);
        if ($object) {
            foreach ($data['settings'] ?? [] as $name => $locales) {
                foreach ($locales as $locale => $item) {
                    (new SettingEntity($entity->_type))->create([
                        "object"  => $object->id,
                        "name"    => $name,
                        "value"   => $item['value'],
                        "content" => $item['content'],
                        "locale"  => $locale
                    ]);
                }
            }
        }
        return $object;
    }
    
    public static function update($entity, $object, $data) {
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
                        'content' => $item['content']
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
    
    public static function url($context, $issue, $paper, $type) {
        $ean    = ($issue->ean    ?? $issue['ean']);
        $volume = ($issue->volume ?? $issue['volume']);
        $number = ($issue->number ?? $issue['number']);
        $pages  = ($paper->pages  ?? $paper['pages']);
        $short  = JournalsUtils::short($context, $volume, $number, $pages);
        switch ($type) {
            case 'shop': {
                return SHOP_BASE_URL.'/'.$ean.'/'.$short;
            }
            case 'html': 
            case 'pdf': {
                return '/'.$context.'/article/view/'.$short.'/'.$type;
            }
            default: {
                return '/'.$context;
            }
        }
    }
    
}

?>