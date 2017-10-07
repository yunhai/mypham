<?php

namespace Mp\Model;

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Core\Model\Model;

class Setting extends Model {

    public function __construct($table = 'setting', $alias = 'setting') {
        parent::__construct($table, $alias);
    }

    public function beforeSave(&$data = []) {
        parent::beforeSave($data);

        $appId = App::mp('login')->targetId();
        if (Hash::dimensions($data) > 1) {
            foreach ($data as $key => $item) {
                $data[$key]['app_id'] = $appId;
            }
        } else {
            $data['app_id'] = $appId;
        }
    }

    public function baseCondition() {
        return parent::baseConditionWithAppId();
    }
}