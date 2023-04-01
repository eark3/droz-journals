<?php

class JournalsPortal extends Portal {
    
    public function home() {
        $models = [];
        if ($this->context === 'root') {
            foreach ((new JournalEntity())->retrieve() as $journal) {
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
        }
        return $this->page('home', $models);
    }
        
}

?>