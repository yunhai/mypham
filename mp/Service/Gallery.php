<?php

namespace Mp\Service;

use Mp\App;
use Mp\Core\Service\Service;

use Mp\Lib\Utility\Hash;

class Gallery extends Service {

    public function __construct($model = 'gallery', $table = 'post', $alias = 'gallery') {
        if ($model) {
            parent::__construct($model, $table, $alias);
            $this->model()->detail(App::load('file', 'model'));
        }
    }

    public function save($gallery = [], $detail = [], &$targetId = 0) {
        $model = $this->model();

        $flag = $model->save($gallery);
        if (!$flag) {
            return false;
        }

        $targetId = isset($gallery['id']) ? $gallery['id'] : $model->lastInsertId();

        return $this->saveDetail($detail, $ref, $this->model()->alias());
    }

    public function saveDetail($detail = [], $refKey = 0, $model = '') {
        $index = 0;
        $object = $this->model()->detail();
        foreach ($detail as $id => $item) {
            $item['idx'] = ++$index * 10;
            $item['target_id'] = $refKey;
            $item['target_model'] = $model;

            $object->save($item);
        }

        $condition = "id NOT IN (" . implode(',', array_keys($detail)) . ") AND target_id = {$refKey} AND target_model = '{$model}'";
        return $object->delete($condition);
    }

    public function detail($gallerId = 0, $targetModel = 'gallery') {
        $alias = $this->model()->detail()->alias();
        $option = [
            'select' => "{$alias}.id, {$alias}.name, {$alias}.directory",
            'where' => "target_model = {$targetModel} AND target_id = {$gallerId}",
            'order' => "{$alias}.id",
        ];

        $result = $this->model()->detail()->find($option);
        return Hash::combine($result, "{n}.{$alias}.id", "{n}.{$alias}");
    }
}