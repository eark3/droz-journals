<?php

class JournalsStats extends ProcessExecutor {
    
    public function parameters($string) {
        list($journal, $year) = explode(' ', $string);
        $parameters = ['journal' => $journal, 'year' => $year];
        $this->setParameters($parameters);
        return $parameters;
    }
    
    public function execute($parameters = []) {
        $journal = (new JournalEntity())->retrieveOne($parameters['journal'] ?? null);
        $year = $parameters['year'] ?? null;
        $stats = JournalsUtils::stats($journal, $year);
        foreach ($stats as $tab => $data) {
            $this->info(0, $tab);
            foreach ($data as $line) {
                $this->info(0, implode("\t", $line));
            }
            $this->info();
        }
    }
    
}

?>