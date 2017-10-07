<?php

namespace Mp\Model;

use Mp\App;
use Mp\Core\Model\Model;

class Search extends Model {

    public function __construct() {
        parent::__construct('search', 'search');
    }

    public function beforeSave(&$data = []) {
        parent::beforeSave($data);
        $data['app_id'] = App::mp('login')->targetId();
    }

    public function baseCondition() {
        return parent::baseConditionWithAppId();
    }

    public function id($target = []) {
        $default = [
            'select' => 'id',
            'where' => "target_id = {$target['target_id']} AND target_model = '{$target['target_model']}'",
        ];
        return $this->find($default, 'first');
    }

    public function save($target = []) {
        $existed = $this->id($target);

        if ($existed) {
            $target = array_merge($existed[$this->alias()], $target);
        }

        return parent::save($target);
    }
}