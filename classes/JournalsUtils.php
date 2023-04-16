<?php

class JournalsUtils {
    
    public static function short($context, $issue, $paper = null, $align = false) {
        $volume = $issue->volume;
        if ($align) {
            $volume = Zord::str_pad($volume, 3, '0', STR_PAD_LEFT);
        }
        $short = $context.'_'.$volume;
        if ($issue->number) {
            $number = $issue->number;
            if ($align) {
                $number = Zord::str_pad($number, 2, '0', STR_PAD_LEFT);
            }
            $short .= '.'.$number;
        }
        if ($paper) {
            $pages = $paper->pages;
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
        return $user->hasRole('reader', $journal->context);
    }
    
    public static function readable($user, $journal, $issue, $paper) {
        return self::reader($user, $journal) || self::status($issue, $paper) === 'free';
    }
}

?>