<?php

use Mp\Core\Controller\Frontend;
class ErrorController extends Frontend {

    public function __construct() {
        parent::__construct('', '', '', 'error');
    }

    public function error404() {
        $this->render('404');
    }

    public function error500() {
        $this->render('500');
    }
}
