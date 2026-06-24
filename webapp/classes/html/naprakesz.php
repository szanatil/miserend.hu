<?php

namespace Html;

class Naprakesz extends Html {

    public $churches;

    public function __construct($path) {
        $token  = \Request::Text('token');
        $result = \Eloquent\ChurchUpdateToken::redeem((string) $token);

        if (!$result['success']) {
            $this->template     = 'exception.twig';
            $this->errorMessage = $result['message'];
            return;
        }

        if (!empty($result['churches'])) {
            $this->churches = $result['churches'];
            $this->template = 'naprakesz.twig';
            return;
        }

        header('Location: /templom/' . $result['church_id']);
        exit;
    }
}
