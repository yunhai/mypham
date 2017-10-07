<?php

namespace Mp\Model;

use Mp\App;
use Mp\Core\Model\Model;

class MailTemplate extends Model {

    public function __construct() {
        parent::__construct('mail_template', 'mailTemplate');
    }

    public function beforeSave(&$data = []) {
        parent::beforeSave($data);
        $data['lang_id'] = App::mp('login')->locale();
        $data['app_id'] = App::mp('login')->appId();
    }

    public function baseCondition() {
        return "app_id = " . App::mp('login')->targetId();
    }
}