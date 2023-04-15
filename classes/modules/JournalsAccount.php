<?php

class JournalsAccount extends Account {
    
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
        }
        return $this->page($page, $models);
    }
    
    protected function message($type, $content) {
        if ($type === 'error') {
            $type = 'danger';
        }
        return parent::message($type, $content);
    }
    
    public function notifyReset($user) {
        $characters = RANDOM_PASSWORD_CHARACTERS;
        $password = '';
        for ($index = 0; $index < RANDOM_PASSWORD_LENGTH; $index++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }
        //(new UserEntity())->update($user->login, ['password' => $password]);
        $models = [
            'login'    => $user->login,
            'password' => $password
        ];
        $send = $this->sendMail([
            'category'  => 'account'.DS.$user->login,
            'principal' => ['email' => $user->email, 'name' => $user->name],
            'subject'   => $this->locale->mail->reset_password->subject.' ('.$user->login.')',
            'text'      => Zord::substitute($this->locale->mail->reset_password->new, $models)."\n".$this->locale->mail->noreply,
            'content'   => '/mail/account/reset',
            'models'    => $models,
            'styles'    => Zord::value('mail', ['styles','account']) ?? null
        ]);
        $result = ['account' => htmlspecialchars($user->name.' <'.$user->email.'>')];
        if ($send !== true) {
            $result['error'] = $this->message('error', $this->locale->messages->mail_error).'|'.$this->message('error', $send);
        }
        return $result;
    }
    
}

?>