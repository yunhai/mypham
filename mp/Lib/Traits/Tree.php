<?php

namespace Mp\Lib\Traits;

use Mp\Lib\Utility\Hash;

//http://www.sitepoint.com/hierarchical-data-database-3/
trait Tree {

    private $left = 'lft';
    private $right = 'rght';
    private $parent = 'parent_id';
    private $unique = 'tree_id';

    public function extract($id = '', $childOnly = false, $display = 'title', $indent = '', $option = [], &$excerpts = []) {
        $alias = $this->alias();
        $default = [
            'select' => "{$alias}.lft, {$alias}.rght, {$alias}.{$this->unique}",
            'where' => "{$alias}.id = {$id}",
        ];

        $parent = $this->find($default, 'first');
        if (empty($parent)) {
            return [];
        }

        extract($parent[$alias]);

        $where = "{$alias}.{$this->left} BETWEEN {$lft} AND {$rght}";
        if ($childOnly) {
            $where = "{$lft} < {$alias}.{$this->left} AND {$this->left} < {$rght}";
        }

        $unique = $this->unique;
        $where .= " AND {$this->unique} = " . $$unique;

        $default = [
            'select' => "{$alias}.id, {$alias}.title",
            'where' => $where,
            'order' => "{$alias}.lft",
        ];

        if (isset($option['where'])) {
            $option['where'] = $where . ' AND ' . $option['where'];
        }

        $default = array_merge($default, $option);

        if ($indent) {
            $default['select'] .= ", {$alias}.lft, {$alias}.rght";

            $result = $this->find($default);
            return $this->indent($result, $display, $indent, $excerpts);
        }

        return $this->find($default);
    }

    public function indent($data = [], $display = 'title', $spacer = '', &$excerpts = []) {
        $right = $excerpts = [];
        $alias = $this->alias();

        foreach ($data as $key => $row) {
            if (count($right) > 0) {
                // check if we should remove a node from the stack

                $countRight = count($right) - 1;
                while (isset($right[$countRight]) && $right[$countRight] < $row[$alias][$this->right]) {
                    array_pop($right);
                    $countRight = count($right) - 1;
                }
            }

            // display indented node title
            $modified = str_repeat($spacer, count($right)) . $row[$alias][$display];
            $data[$key][$alias][$display] = $excerpts[$key] = $modified;

            $right[] = $row[$alias][$this->right];
        }

        return $data;
    }

    /**
     * rebuild mttp tree
     */
    public function rebuild($tree = 0) {
        $alias = $this->alias();
        $options = [
            'select' => "{$alias}.id, {$alias}.lft",
            'where' => "{$alias}.id = " . $tree,
        ];

        $root = $this->find($options, 'first');
        if (empty($root)) {
            return [];
        }

        $lft = empty($root[$alias]['lft']) ? 1 : $root[$alias]['lft'];
        $this->makeRebuild($root[$alias]['id'], $lft);

        return true;
    }

    private function makeRebuild($parent, $left) {
        $alias = $this->alias();

        // the right value of this node is the left value + 1
        $right = $left + 1;

        // get all children of this node
        $option = [
            'select' => "{$alias}.id",
            'where' => "{$alias}.{$this->parent} = '{$parent}'", // AND {$alias}.deleted = 0
            'order' => "{$alias}.idx"
        ];

        $result = $this->find($option);

        foreach ($result as $row) {
            $right = $this->makeRebuild($row[$alias]['id'], $right);
        }

        $option = [
            'fields' => [$this->left => $left, $this->right => $right],
            'where' => "id = " . $parent
        ];

        $this->update($option);

        return $right + 1;
    }

    public function add($target, $info = []) {
        $alias = $this->alias();

        $option = [
            'select' => "{$alias}.id, {$alias}.{$this->right}, {$alias}.{$this->unique}",
            'where' => "{$alias}.id = '{$target}'", // AND {$alias}.deleted = 0
        ];

        $result = $this->find($option, "first");

        $flag = $result[$alias][$this->right];
        $treeId = $result[$alias][$this->unique];

        $option = [
            'fields' => array($this->right => "`" . $this->right . "`+ 2"),
            'where' => $this->right . " >= " . $flag . " AND {$alias}.{$this->unique} = " . $treeId
        ];

        $this->update($option);

        $option = [
            'fields' => [$this->left => "`" . $this->left . "` + 2"],
            'where' => $this->left . " >= " . $flag . " AND {$alias}.{$this->unique} = " . $treeId
        ];

        $this->update($option);

        $info[$this->left] = $flag;
        $info[$this->right] = $flag + 1;
        $info[$this->parent] = $target;

        $this->create($info);
    }

    public function remove($target = '') {
        $alias = $this->alias();
        $option = [
            'select' => "{$alias}.{$this->left}, {$alias}.{$this->right}"
        ];

        $tmp = $result = $this->extract($target, false, $option);
        $result = Hash::extract($result, $alias);
        $delta = count($result) * 2;

        $flag = $tmp[$target][$alias][$this->right];

        $option = [
            'fields' => [$this->right => "`" . $this->right . "`- " . $delta],
            'where' => $this->right . " >= " . $flag
        ];
        $this->update($option);

        $option = [
            'fields' => [$this->left => "`" . $this->left . "` - " . $delta],
            'where' => $this->left . " >= " . $flag
        ];
        $this->update($option);

        $update[$this->left] = 0;
        $update[$this->right] = 0;
        $update['deleted'] = 1;

        $option = [
            'fields' => $this->tracklog(0),
            'where' => 'id IN (' . implode($result, ',') . ')'
        ];

        $this->update($option);
    }

    /**
     * build tree from parent id
     */
    public function build($data = [], $root = 0) {
        $alias = $this->alias();

        $new = [];
        foreach ($data as $item) {
            $new[$item[$alias][$this->parent]][] = $item;
        }

        return $this->makeBuild($new, array($data[$root]));
    }

    private function makeBuild(&$list, $parent) {
        $alias = $this->alias();
        $tree = [];

        foreach ($parent as $k => $l) {
            if (isset($list[$l[$alias]['id']])) {
                $l['children'] = $this->makeBuild($list, $list[$l[$alias]['id']]);
            }

            $tree[] = $l;
        }

        return $tree;
    }
}