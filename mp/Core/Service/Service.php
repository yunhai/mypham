<?php

namespace Mp\Core\Service;

use Mp\App;
use Mp\Core\Master;
use Mp\Lib\Utility\Hash;

class Service extends Master{

    protected $model = null;

    public function __construct($model = '', $table = '', $alias = '') {
        if ($model) {
            $this->model = App::load($model, 'model', compact('table', 'alias'));
        }
    }

    public function category($name = '', $func = 'flat') {
        $this->model->category(App::category()->$func($name));
    }

    public function get($option = [], $extend = [], $association = []) {
        $alias = $this->model()->alias();

        $default = [
            'select' => "{$alias}.id, {$alias}.title",
        ];

        $default = array_merge($default, $option);

        if ($extend) {
            $this->model()->extend($extend);
        }

        $result = $this->model()->find($default);

        $result = Hash::combine($result, "{n}.{$alias}.id", "{n}.{$alias}");

        if ($extend) {
            $this->associate($result);
        }

        if ($association) {
            return $this->model()->associate($result, $association);
        }

        return $result;
    }
}