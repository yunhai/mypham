<?php

namespace Mp\Model;

use Mp\App;
use Mp\Core\Model\Model;
use Mp\Lib\Utility\Hash;

class Category extends Model {
    use \Mp\Lib\Traits\Tree;

    public function __construct($table = 'category', $alias = 'category') {
        parent::__construct($table, $alias);
    }

    public function baseCondition() {
        $login = App::mp('login');
        $alias = $this->alias();

        return "{$alias}.locale = " . $login->targetLocale() . " AND " . parent::baseConditionWithAppId();
    }

    public function getBySlug($slug = '', $type = 'first', $option = []) {
        $alias = $this->alias();

        $default = [
            'select' => "{$alias}.id",
            'where' => "{$alias}.slug = '{$slug}' AND {$alias}.status > 0",
        ];

        if (!empty($option)) {
            $default = array_merge($default, $option);
        }

        return $this->find($default, $type);
    }

    public function beforeSave(&$data = []) {
        parent::beforeSave($data);

        $login = App::mp('login');

        $data['app_id'] = $login->targetId();
        $data['locale'] = $login->targetLocale();
    }

    public function delete($condition = '', $branch = '', $association = []) {
        $flag = parent::delete($condition, $association);

        if ($flag && !empty($branch)) {
            $tmp = $this->getBySlug($branch);
            $root = $tmp[$this->alias()]['id'];

            $this->rebuild($root);
        }

        return true;
    }
}