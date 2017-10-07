<?php

namespace Mp\Model;

use Mp\App;
use Mp\Core\Model\Model;

class Group extends Model {

    public function __construct($table = 'group', $alias = 'group') {
        parent::__construct($table, $alias);
    }

    public function beforeSave(&$data = []) {
        parent::beforeSave($data);
        $data['app_id'] = App::mp('login')->targetId();
    }

    public function baseCondition() {
        return parent::baseConditionWithAppId();
    }

    public function base($type = 1, $channel = 1) {
        $option = [
            'select' => 'id, title',
            'where' => 'status > 0 AND type = ' . $type . ' AND channel = ' . $channel,
            'limit' => 1
        ];
        return $this->find($option, 'list');
    }

    public function available($channel = 1) {
        $option = [
            'select' => 'id, title',
            'where' => "status > 0 AND channel = {$channel}"
        ];
        return $this->find($option, 'list');
    }
}