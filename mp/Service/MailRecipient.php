<?php

namespace Mp\Service;

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Core\Service\Service;

class MailRecipient extends Service {
    public function __construct($model = 'mailRecipient', $table = 'mail_repicient', $alias = 'mailRecipient') {
        parent::__construct($model, $table, $alias);
    }

    public function subcribe($info = []) {
        $model = new \Mp\Model\MailRecipient();

        $flag = $this->validate($this->model()->alias(), $info);
        if ($flag) {
            return $this->model()->save($info);
        }

        return false;
    }
}