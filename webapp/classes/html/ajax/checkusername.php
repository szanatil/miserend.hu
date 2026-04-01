<?php

namespace Html\Ajax;

class CheckUsername extends Ajax {

    public function __construct() {
        if (CheckUsername(\Request::Simpletext('text'))) {
            $this->content = 1;
        } else {
            $this->content = 0;
        }
    }

}
