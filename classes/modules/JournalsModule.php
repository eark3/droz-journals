<?php

trait JournalsModule {
    
    use JournalsCommon;
    
    public function configure() {
        $this->cache = Cache::instance();
        $theme = strtolower($this->context);
        $default = strtolower(DEFAULT_THEME);
        $css = $this->checkCSS('bootstrap3', $theme);
        if (!isset($css)) {
            $css = $this->checkCSS('bootstrap3', $default);
        }
        $this->addStyle($css);
        $this->addStyle('/journals/css/common.css');
        $css = $this->checkCSS($theme, 'index');
        if (!isset($css)) {
            $css = $this->checkCSS($default, 'index');
        }
        $this->addStyle($css);
        $extra = '/build/css/'.$this->context.'/extra.css';
        if (file_exists(Zord::liveFolder(substr(dirname($extra), 1)).basename($extra))) {
            $this->addStyle($extra);
        }
        if ($this->context !== 'root') {
            $this->addScript('/journals/js/journal.js');
        }
    }
    
    public function models($models) {
        $_page = $models['page'] ?? null;
        $models['host'] = $this->controler->getHost();
        $models['langs'] = Zord::value('portal', 'lang');
        foreach ((new JournalEntity())->retrieveAll(['order' => ['asc' => 'place']]) as $journal) {
            if (!empty(Zord::value('context', [$journal->context,'url']))) {
                $models['journals'][] = $this->_journal($journal, false);
            }
        }
        if (isset($this->controler->journal)) {
            $models['aside'] = Zord::value('portal', ['aside', 'layout', $this->context]) ?? Zord::value('portal', ['aside', 'layout', 'default']);
            $models['journal'] = $this->_journal($this->controler->journal, false);
        }
        if (isset($_page) && isset($this->locale->pages->$_page) && !isset($models['ariadne'])) {
            $models['ariadne'] = [
                'home'   => '/'.$this->context,
                'active' => $this->locale->pages->$_page
            ];
        }
        return $models;
    }
    
    protected function checkCSS($name, $root) {
        $sourceFolder = Zord::getComponentPath('web'.DS.'themes'.DS).$name;
        $source = $sourceFolder.DS.'styles'.DS.$root.'.less';
        if (!file_exists($source)) {
            return null;
        }
        $target = Zord::liveFolder('build'.DS.'css'.DS.$this->context).$name.'.css';
        if (Zord::needsUpdate($target, Zord::listRecursive($sourceFolder))) {
            $parser = new Less_Parser();
            $parser->parseFile($source, $this->baseURL);
            file_put_contents($target, $parser->getCss());
        }
        return '/build/css/'.$this->context.'/'.$name.'.css';
    }
    
    protected function message($type, $content) {
        if ($type === 'error') {
            $type = 'danger';
        }
        return parent::message($type, $content);
    }
    
}

?>