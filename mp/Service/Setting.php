<?php

namespace Mp\Service;

use Mp\Core\Service\Service;

class Setting extends Service {

    public function __construct() {
        $this->model(new \Mp\Model\Setting('setting', 'setting'));
    }

    public function all() {
        $alias = $this->model()->alias();

        $option = [
            'select' => "{$alias}.key, {$alias}.value",
            'where' => "{$alias}.status > 0",
        ];

        return $this->model()->find($option, 'list');
    }
}