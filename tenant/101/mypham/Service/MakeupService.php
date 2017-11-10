<?php

use Mp\App;
use Mp\Core\Service\Service;
use Mp\Lib\Utility\Hash;

class MakeupService extends Service {
    public function __construct($model = 'makeup', $table = 'post', $alias = 'makeup') {
        $this->model(App::load($model, 'model', compact('table', 'alias')));
        $this->model()->category(App::category()->flat($alias));
    }

    public function lastest() {
        $model = $this->model();

        $query = [
            'select' => 'id, title, seo_id',
            'where' => 'status > 0',
            'order' => 'id desc',
            'limit' => 6
        ];

        $alias = $model->alias();

        $result = $model->find($query);
        $result = Hash::combine($result, "{n}.{$alias}.id", "{n}.{$alias}");

        $this->associate($result);
        return $result;
    }
}