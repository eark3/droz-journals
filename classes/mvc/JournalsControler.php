<?php

class JournalsControler extends Controler {
    
    public $journal = null;
    public $issue = null;
    public $section = null;
    public $paper = null;
    public $authors = [];
    
    public function handle($target, $replay = false) {
        if (!empty($target['context']) && $target['context'] !== 'root') {
            $journal = (new JournalEntity())->retrieveOne(['context' => $target['context']]);
            if ($journal !== false) {
                $this->journal = $journal;
            }
        }
        parent::handle($target, $replay);
    }
    
    public function models() {
        $models = parent::models();
        foreach ((new JournalEntity())->retrieveAll(['order' => ['asc' => 'place']]) as $journal) {
            $settings = [];
            $criteria = ['object' => $journal->id];
            foreach (['description'] as $property) {
                $criteria['name'] = $property;
                $setting = null;
                foreach ([$this->lang, DEFAULT_LANG] as $locale) {
                    $criteria['locale'] = $locale;
                    $setting = (new SettingEntity('journal'))->retrieveOne($criteria);
                    if ($setting !== false) {
                        break;
                    }
                }
                if ($setting) {
                    $settings[$property] = $setting->value;
                }
            }
            $models['journals'][] = [
                'path'        => '/'.$journal->context,
                'name'        => $journal->name ?? null,
                'description' => $settings['description'] ?? null,
                'image'       => '/public/journals/thumbnails/'.$journal->context.'.jpg'
            ];
        }
        if ($this->context !== 'root') {
            $models['layout'] = Zord::value('layout', $this->context) ?? Zord::value('layout', 'default');
        }
        return $models;
    }
    
    public function getTarget($url, $redirect = false) {
        $useless = '/index.php';
        $path = $_SERVER['REQUEST_URI'];
        if (substr($path, 0, strlen($useless)) === $useless) {
            $url = str_replace($useless, '', $url);
        }
        return parent::getTarget($url, $redirect);
    }
    
}

?>