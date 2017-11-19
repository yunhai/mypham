<?php

namespace Mp\Service;

use Mp\Lib\Utility\Hash;
use Mp\Service\Category;

class Menu extends Category
{
    public function retrieve($branch = '')
    {
        $alias = $this->model()->alias();

        $option = [
            'select' => "{$alias}.id, {$alias}.title, {$alias}.url, {$alias}.caption, {$alias}.parent_id",
            'where' => 'status > 0'
        ];
        $data = $this->branch($branch, true, 'title', '', $option);

        $data = Hash::combine($data, '{n}.' . $alias . '.id', '{n}.' . $alias);
        $root = current($data);
        $root = $root['parent_id'];

        return Hash::nest($data, [
            'idPath' => '{n}.id',
            'parentPath' => '{n}.parent_id',
            'root' => $root
        ]);
    }
}
