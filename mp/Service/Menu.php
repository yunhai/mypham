<?php

namespace Mp\Service;

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Service\Category;

class Menu extends Category {

    public function retrieve($branch = '') {
        $alias = $this->model()->alias();

        $option = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.url, {$alias}.caption, {$alias}.parent_id",
        ];
        $data = $this->branch($branch, true, 'title', '', $option);
        return Hash::nest($data);
    }
}