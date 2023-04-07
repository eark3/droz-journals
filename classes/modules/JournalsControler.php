<?php

class JournalsControler extends Controler {
    
    public function models() {
        $models = parent::models();
        foreach ((new JournalEntity())->retrieve(['many' => true, 'order' => ['asc' => 'place']]) as $journal) {
            $settings = [];
            $criteria = [
                'type'   => 'journal',
                'object' => $journal->id
            ];
            foreach (['name','description'] as $property) {
                $criteria['name'] = $property;
                $setting = null;
                foreach ([$this->lang, DEFAULT_LANG] as $locale) {
                    $criteria['locale'] = $locale;
                    $setting = (new SettingEntity())->retrieve(["where" => $criteria]);
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
                'name'        => $settings['name'] ?? null,
                'description' => $settings['description'] ?? null,
                'image'       => '/public/journals/thumbnails/'.$journal->context.'.jpg'
            ];
        }
        if ($this->context !== 'root') {
            $models['layout'] = Zord::value('layout', $this->context);
            $journal = (new JournalEntity())->retrieve(['where' => ['context' => $this->context]]);
            if ($journal !== false) {
                $models['journal'] = [
                    'name' => $journal->name
                ];
            }
        }
        return $models;
    }
        
}

?>