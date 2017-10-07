<?php

namespace Mp\Model;

use Mp\App;
use Mp\Core\Model\Model;

class Mail extends Model {

    public function __construct() {
        parent::__construct('mail', 'mail');
    }

    public function beforeSave(&$data = []) {
        parent::beforeSave($data);
        $data['app_id'] = App::mp('login')->targetId();
    }

    public function baseCondition() {
        return 'app_id = ' . App::mp('login')->targetId();
    }
}