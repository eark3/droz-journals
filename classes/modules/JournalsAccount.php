<?php

class JournalsAccount extends Account {
    
    use JournalsModule;
    
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
    
    protected function _password($login) {
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