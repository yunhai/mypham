<?php

namespace Mp\Model;

use Mp\App;
use Mp\Core\Model\Model;

class Page extends Model {
    protected $category = [];

    public function __construct($table = 'page', $alias = 'page') {
        parent::__construct($table, $alias);
    }

    public function baseCondition() {
        $login = App::mp('login');
        $alias = $this->alias();

        return "{$alias}.locale = " . $login->targetLocale() . " AND " . parent::baseConditionWithAppId();
    }

    public function beforeSave(&$data = []) {
        parent::beforeSave($data);

        $login = App::mp('login');

        $data['app_id'] = $login->targetId();
        $data['locale'] = $login->targetLocale();
    }
}