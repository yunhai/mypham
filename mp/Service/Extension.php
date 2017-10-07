<?php

namespace Mp\Service;

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Core\Service\Service;

class Extension extends Service {

    public function __construct($model = '', $table = '', $alias = '') {
        if ($model) {
            $this->model = App::load($model, 'model', compact('table', 'alias'));
        }
    }

    public function detail($id = 0, $option = []) {
        $field = $this->model()->virtualField();

        $default = [
            'select' => 'id, created, target_id, target_model, ' . implode(',', array_keys($field)),
            'where' => "id = {$id}",
        ];

        $option = array_merge($default, $option);

        $list = $this->model()->find($option);
        if ($list) {
            $alias = $this->model()->alias();
            $list = Hash::combine($list, '{n}.' . $alias . '.id', '{n}.' . $alias);

            $target = current($list);
            $this->model()->mapExtension($target, $list);
        }

        return $target;
    }

    public function get($target = 0, $model = [], $option = []) {
        $default = [
            'select' => 'id',
            'order' => 'id desc',
            'limit' => 10
        ];

        $option = array_merge($default, $option);

        $option['select'] .= ', string_1, string_2, string_3, string_4, string_5, text_1, target_id, target_model';

        $targetModel = '';
        foreach($model as $key => $f) {
            $targetModel .= ",'{$key}'";
        }
        $targetModel = trim($targetModel, ',');

        if ($target) {
            $where = "target_id IN ({$target}) AND target_model IN ({$targetModel})";
        } else {
            $where = "target_model IN ({$targetModel})";
        }

        if (empty($option['where'])) {
            $option['where'] = $where;
        } else {
            $option['where'] = $where . ' AND ' . $option['where'];
        }

        $m = current($model);
        $list = $m->find($option);

        if (!empty($list)) {
            $alias = $m->alias();
            $tmp = Hash::combine($list, '{n}.' . $alias . '.id', '{n}.' . $alias);

            $result = [];
            foreach ($tmp as $id => $item) {
                $result[$id] = $item;

                if (isset($model[$item['target_model']])) {
                    $model[$item['target_model']]->mapExtension($result[$id], $tmp);
                }
            }

            $list = $result;
        }

        return $list;
    }

    public function paginate($model = [], $option = [], $target = 0) {
        $default = [
            'select' => 'id',
            'order' => 'id desc',
            'limit' => 10
        ];

        $option = array_merge($default, $option);

        $option['select'] .= ', string_1, string_2, string_3, string_4, string_5, text_1, target_id, target_model';

        $targetModel = '';
        foreach($model as $key => $f) {
            $targetModel .= ",'{$key}'";
        }
        $targetModel = trim($targetModel, ',');

        if ($target) {
            $where = "target_id IN ({$target}) AND target_model IN ({$targetModel})";
        } else {
            $where = "target_model IN ({$targetModel})";
        }

        if (empty($option['where'])) {
            $option['where'] = $where;
        } else {
            $option['where'] = $where . ' AND ' . $option['where'];
        }

        $m = current($model);
        $option = App::load('paginator')->paginate($option, $m, true);

        if (!empty($option['list'])) {
            $alias = $m->alias();
            $tmp = Hash::combine($option['list'], '{n}.' . $alias . '.id', '{n}.' . $alias);

            $result = [];
            foreach ($tmp as $id => $item) {
                $result[$id] = $item;

                if (isset($model[$item['target_model']])) {
                    $model[$item['target_model']]->mapExtension($result[$id], $tmp);
                }
            }

            $option['list'] = $result;
        }

        return $option;
    }

    public function update($fields = [], $target = []) {
        if ($target) {
            $target = implode(',', $target);

            $condition = 'id IN (' . $target . ')';
            $this->model()->modify($fields, $condition);
        }

        return true;
    }

    public function delete($target = []) {
        if ($target) {
            $target = implode(',', $target);
            $condition = 'id IN (' . $target . ')';
            $this->model()->delete($condition);
        }

        return true;
    }

    public function save($target = [], &$error = [], $validate = true, $rule = 'def') {
        if ($target) {
            if ($validate) {
                $alias = $this->model()->alias();
                $flag = $this->validate($alias, $target, $error, 1, $rule);
                if (!$flag) {
                    $error = [
                        $alias => $error
                    ];

                    return false;
                }
            }
            $this->model()->extend($this->model()->virtualField());
            return $this->model()->saveRaw($target);
        }

        return true;
    }
}