<?php

namespace Mp\Model;

use Mp\App;
use Mp\Core\Model\Model;

class MailRecipient extends Model {

    public function __construct() {
        parent::__construct('mail_recipient', 'MailRecipient');
    }

    public function save($target = []) {
        $existed = $this->id($target);

        if ($existed) {
            return true;
            $existed = current($existed);
            $target = array_merge($target, $existed);
        }

        return parent::save($target);
    }

    public function id($target = []) {
        $default = [
            'select' => 'id',
            'where' => "email = '{$target['email']}'",
        ];
        return $this->find($default, 'first');
    }


    public function beforeSave(&$data = []) {
        parent::beforeSave($data);
        $data['app_id'] = App::mp('login')->targetId();
    }

    public function baseCondition() {
        return parent::baseConditionWithAppId();
    }
}