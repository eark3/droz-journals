<?php

class JournalsStats extends ProcessExecutor {
    
    public function parameters($string) {
        list($journal, $year, $file) = explode(' ', $string);
        $parameters = ['journal' => $journal, 'year' => $year, 'file' => $file];
        $this->setParameters($parameters);
        return $parameters;
    }
    
    public function execute($parameters = []) {
        $journal = (new JournalEntity())->retrieveOne($parameters['journal'] ?? null);
        $year = $parameters['year'] ?? null;
        $file = $parameters['file'] ?? null;
        $stats = JournalsUtils::stats($journal, $year);
        foreach ($stats as $tab => $data) {
            file_put_contents($file, $tab."\n");
            foreach ($data as $line) {
                file_put_contents($file, implode("\t", $line)."\n");
            }
            file_put_contents($file, "\n");
        }
    }
    
}

?>