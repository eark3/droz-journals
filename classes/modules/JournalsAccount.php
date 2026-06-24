<?php

class JournalsAccount extends Account {
    
    use JournalsModule, SushiService, Counter;
    
    protected function form($action = 'connect', $models = []) {
        $page = 'login';
        switch ($action) {
            case 'reset': {
                if (!empty($models['message'])) {
                    foreach (explode('|', $models['message']) as $message) {
                        if (substr($message, 0, strlen('danger=')) === 'danger=') {
                            $page = 'login/lostPassword';
                        }
                    }
                }
                break;
            }
            case 'password': {
                $models['needform'] = empty($models['message']) || substr($models['message'], 0, strpos($models['message'], '=')) !== 'success';
                $models['token']    = $this->params['token'] ?? null;
                $page = 'login/changePassword';
            }
        }
        return $this->page($page, $models);
    }
    
    public function notifyReset($user, $models = []) {
        $choose = $this->params['choose'] ?? false;
        $models['mode'] = empty($choose) ? 'new' : 'choose';
        return parent::notifyReset($user, $models);
    }
    
    protected function getCounterCriteria($user, $context) {
        return [
            'user'    => $user,
            'journal' => $context,
            'paper'   => '__NOT_NULL__'
        ];
    }
    
    protected function getReportItems($user, $context, $begin, $end, $template) {
        $items = [];
        $timezone = new DateTimeZone(DEFAULT_TIMEZONE);
        $platform = Zord::value('context', [$context,'title',DEFAULT_LANG]);
        $first = new DateTime($begin, $timezone);
        $last = new DateTime($end, $timezone);
        $journal = (new JournalEntity())->retrieveOne($context);
        $_journal = $this->_journal($journal);
        foreach ((new UserHasQueryEntity())->retrieveDistinct($this->getCounterBetweenMonthsQuery($user->login, $context, $first, $last), 'paper') as $entity) {
            $paper = (new PaperEntity())->retrieveOne($entity->paper);
            $issue = (new IssueEntity())->retrieveOne($paper->issue);
            $_paper = $this->_paper($paper, $issue);
            $totalItemRequests = [];
            $uniqueTitleRequests = [];
            for ($month = clone $first ; $month <= $last ; $month->modify('+1 month')) {
                $key = $month->format('Y-m');
                $totalQuery = array_merge($this->getCounterBetweenMonthsQuery($user->login, $context, $month, $month), [
                    'paper' => $entity->paper
                ]);
                $totalItemRequests[$key] = (new UserHasQueryEntity())->retrieveAll($totalQuery)->count();
                $uniqueQuery = array_merge($totalQuery, [
                    'display' => '__NULL__'
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
    
    protected function _password($login) {
        $mode = $this->params['mode'] ?? 'new';
        if ($mode === 'choose') {
            return parent::_password($login);
        }
        $characters = RANDOM_PASSWORD_CHARACTERS;
        $password = '';
        for ($index = 0; $index < RANDOM_PASSWORD_LENGTH; $index++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }
        $user = (new UserEntity())->update($login, ['password' => $password]);
        $models = ['password' => $password];
        $send = $this->sendMail([
            'category'  => 'account'.DS.$user->login,
            'principal' => ['email' => $user->email, 'name' => $user->name],
            'subject'   => $this->locale->mail->reset_password->receive,
            'text'      => Zord::substitute($this->locale->mail->reset_password->new, $models)."\n".$this->locale->mail->noreply,
            'content'   => '/mail/account/password',
            'models'    => $models,
            'styles'    => Zord::value('mail', ['styles','account']) ?? null
        ]);
        if ($send !== true) {
            return $this->error(500);
        }
        return $this->page('login', ['message' => $this->message('success', $this->locale->mail->reset_password->sent)]);
    }
    
}

?>