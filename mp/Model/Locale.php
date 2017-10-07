<?php

namespace Mp\Model;

use Mp\App;
use Mp\Core\Model\Model;
use Mp\Lib\Utility\Hash;

class Locale extends Model {

    public function __construct($table = 'locale', $alias = 'locale') {
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

    // public function baseCondition() {
    //      return $this->alias() . ".app_id = " . App::mp('login')->targetId() . ' AND ' . parent::baseCondition();
    // }
}