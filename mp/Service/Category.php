<?php

namespace Mp\Service;

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Core\Service\Service;

class Category extends Service {

    public function __construct($model = null) {
        parent::__construct();
        $this->model($model);
    }

    public function root($branch = '') {
        $tmp = $this->model()->getBySlug($branch, 'list');

        if (empty($tmp)) {
            return 0;
        }

        return current($tmp);
    }

    public function flat($group, $childOnly = false, $display = 'title', $indent = '', $option = []) {
        $root = $this->root($group);

        if ($root) {
            $excerpts = [];
            $result = $this->extract($root, $childOnly, $display, $indent, $option, $excerpts);
            if ($indent) {
                return $excerpts;
            }
            $alias = $this->model()->alias();
            return Hash::combine($result, "{n}.{$alias}.id", "{n}.{$alias}.{$display}");
        }

        return [];
    }

    public function extract($id = '', $childOnly = false, $display = 'title', $indent = '', $option = [], &$excerpts = []) {
        return $this->model()->extract($id, $childOnly, $display, $indent, $option, $excerpts);
    }

    public function branch($group = '', $childOnly = false, $display = 'title', $indent = '', $option = []) {
        $root = $this->root($group);

        if ($root) {
            return $this->extract($root, $childOnly, $display, $indent, $option);
        }

        return [];
    }

    public function tree($group = '', $option = []) {
        $fields = 'id, title, parent_id';
        if (empty($option['select'])) {
            $option['select'] = $fields;
        } else {
            $option['select'] = $fields . ', ' . $option['select'];
        }

        $alias =  $this->model()->alias();
        $result = $this->branch($group, false, '', '', $option);
        return Hash::combine($result, "{n}.{$alias}.id", "{n}.{$alias}");
    }

    public function node($tree = [], $node = 0) {
        return $tree;
    }

    // public function associate($target = []) {
    //     $association = [
    //         'seo' => 'id, alias',
    //     ];

    //     return $this->model()->associate($target, $association);
    // }

    public function seoId($target) {
        $option = [
            'select' => 'id, seo_id',
            'where' => 'id IN (' . implode(',', $target) . ')'
        ];

        return $this->model()->find($option, 'list');
    }
}
