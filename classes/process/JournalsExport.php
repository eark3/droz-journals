<?php

class JournalsExport extends ProcessExecutor {
    
    public function parameters($string) {
        $journals = explode(',', $string);
        $parameters = ['journals' => $journals];
        $this->setParameters($parameters);
        return $parameters;
    }
    
    public function execute($parameters = []) {
        foreach ($parameters['journals'] ?? [] as $journal) {
            $_journal = (new JournalEntity())->retrieveOne($journal);
            if ($_journal === false) {
                $this->error(0, 'Unknown journal '.$journal);
                continue;
            }
            $issues = (new IssueEntity())->retrieveAll(['journal' => $_journal->id]);
            foreach ($issues as $issue) {
                $short = JournalsUtils::short($_journal->context, $issue->volume, $issue->number);
                $this->info(0, $short);
                file_put_contents(Zord::liveFolder('export').$short.'.json', Zord::json_encode(JournalsUtils::export($issue)));
            }
        }
    }
    
}

?>