<?php

namespace Mp\Service;

use Mp\App;
use Mp\Lib\Utility\Sanitize;
use Mp\Core\Service\Service;

class Mail extends Service {
    public function __construct($model = 'mail', $table = 'mail', $alias = 'mail') {
        $this->model(new \Mp\Model\Mail($table, $alias));
    }

    public function save($target = []) {
        $target = $this->format($target);
        return $this->model()->save($target, false);
    }

    private function format($target = []) {
        $target['mail_id'] = $target['id'];
        $target['_to'] = $target['to'];
        $target['_from'] = $target['from'];

        unset($target['id']);
        unset($target['to']);
        unset($target['from']);
        unset($target['status']);

        return $target;
    }
}