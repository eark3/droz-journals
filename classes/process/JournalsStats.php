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
        $queries = (new UserHasQueryEntity())->retrieveAll([
            'journal' => $journal->context,
            'paper'   => '__NOT_NULL__',
            'when'    => ['>=' => $year.'-01-01'],
            'when'    => ['<=' => $year.'-12-31']
        ]);
        $counts = [];
        foreach ($queries as $query) {
            $month = date('m', strtotime($query->when));
            $value = $counts[$month][$query->paper][$query->display] ?? 0;
            $counts[$month][$query->paper][$query->display] = $value + 1;
        }
        foreach ((new PaperEntity())->retrieveAll(['journal' => $journal->id]) as $paper) {
            $issue = (new IssueEntity())->retrieveOne($paper->issue);
            $short = JournalsUtils::short($journal->context, $issue->volume, $issue->number, $paper->pages);
            foreach (['01','02','03','04','05','06','07','08','09','10','11','12'] as $month) {
                foreach (['','html','pdf'] as $display) {
                    $count = $counts[$month][$short][$display] ?? null;
                    if (!empty($count)) {
                        $this->info(0, $display."\t".Zord::str_pad($short, 16)."\t".$journal->context."\t".$year.$month."\t".$count."\t".JournalsUtils::short($journal->context, $issue->volume, $issue->number));
                    }
                }
            }
        }
    }
    
}

?>