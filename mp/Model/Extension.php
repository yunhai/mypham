<?php

namespace Mp\Model;

use Mp\App;
use Mp\Core\Model\Model;

class Extension extends Model {
    use \Mp\Lib\Traits\Extension;

    public function __construct($table = 'extension', $alias = 'extension') {
        parent::__construct($table, $alias);
    }

    public function saveByTarget($target = []) {
        $extension = $this->id($target);

        if ($extension) {
            $target = array_merge($target, $extension['extension']);
        }

        return $this->save($target);
    }

    public function id($target = []) {
        $default = [
            'select' => 'id',
            'where' => "target_id = {$target['target_id']} AND target_model = '{$target['target_model']}'",
        ];
        return $this->find($default, 'first');
    }

    public function deleteByList($target = [], $model = '') {
        $target = array_filter($target);
        if ($target) {
            $target = implode(',', $target);
            $condition = "target_model = '{$model}' AND target_id IN ({$target})";

            return $this->delete($condition);
        }

        return true;
    }

    public function beforeDelete(&$condition = '', $association = []) {
        $flag = parent::beforeDelete($condition, $association);
        if ($flag) {
            $condition .= ' AND app_id = ' . App::mp('login')->targetId();
            return true;
        }

        return false;
    }

    public function baseCondition() {
        return parent::baseConditionWithAppId();
    }

    public function beforeSave(&$data = []) {
        $data['app_id'] = App::mp('login')->targetId();
        parent::beforeSave($data);
    }
}