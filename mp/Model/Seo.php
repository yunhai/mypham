<?php

namespace Mp\Model;

use Mp\App;
use Mp\Core\Model\Model;

class Seo extends Model {

    public function __construct($table = 'seo', $alias = 'seo') {
        parent::__construct($table, $alias);
    }

    public function baseCondition() {
        return parent::baseCondition() . " AND app_id = " . App::mp('login')->targetId();
    }

    public function beforeSave(&$data = []) {
        parent::beforeSave($data);
        $data['app_id'] = App::mp('login')->targetId();
    }
}