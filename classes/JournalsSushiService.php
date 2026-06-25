<?php

trait JournalsSushiService {
    
    use SushiService;
    
    protected function getCounterReportCriteria() {
        return [
            'user'    => $this->customer->login,
            'journal' => $this->platform,
            'paper'   => '__NOT_NULL__'
        ];
    }
    
    protected function getCounterReportItems($template) {
        $items = [];
        $timezone = new DateTimeZone(DEFAULT_TIMEZONE);
        $platform = Zord::value('context', [$this->platform,'title',DEFAULT_LANG]);
        $first = new DateTime($this->begin, $timezone);
        $last = new DateTime($this->end, $timezone);
        $journal = (new JournalEntity())->retrieveOne($this->platform);
        $_journal = $this->_journal($journal);
        foreach ((new UserHasQueryEntity())->retrieveDistinct($this->getCounterReportBetweenMonthsQuery($first, $last), 'paper') as $entity) {
            $paper = (new PaperEntity())->retrieveOne($entity->paper);
            $issue = (new IssueEntity())->retrieveOne($paper->issue);
            $_paper = $this->_paper($paper, $issue, $journal);
            $totalItemRequests = [];
            $uniqueTitleRequests = [];
            for ($month = clone $first ; $month <= $last ; $month->modify('+1 month')) {
                $key = $month->format('Y-m');
                $totalQuery = array_merge($this->getCounterReportBetweenMonthsQuery($month, $month), [
                    'paper' => $entity->paper
                ]);
                $totalItemRequests[$key] = (new UserHasQueryEntity())->retrieveAll($totalQuery)->count();
                $uniqueQuery = array_merge($totalQuery, [
                    'display' => '__NOT_NULL__'
                ]);
                $uniqueTitleRequests[$key] = (new UserHasQueryEntity())->retrieveAll($uniqueQuery)->count();
            }
            $item = array_merge($template, [
                "Title" => $_paper['settings']['title'],
                "Platform" => $platform,
                "Item_ID" => [
                    "DOI" => $_paper['settings']['pub-id::doi'],
                    "Online_ISSN" => $_journal['settings']['onlineIssn'],
                    "URI" => JournalsUtils::url($journal->context, $issue, $paper)
                ],
                "Attribute_Performance" => [
                    array_merge($template['Attribute_Performance'][0], [
                        "Performance" => [
                            "Total_Item_Requests" => $totalItemRequests,
                            "Unique_Title_Requests" => $uniqueTitleRequests
                        ]
                    ])
                ]
            ]);
            $items[] = $item;
        }
        return $items;
    }
    
}

?>