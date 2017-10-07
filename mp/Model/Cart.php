<?php

namespace Mp\Model;

use Mp\App;
use Mp\Core\Model\Model;
use Mp\Lib\Utility\Hash;

class Cart extends Model {
    public function __construct($table = 'cart', $alias = 'cart') {
        parent::__construct($table, $alias);
    }

    public function orderDetail($orderId = 0) {
        $select = 'id, order_id, target_id, target_type, quantity, price, tax_rate, tax, sub_total, total, currency';
        $where = 'order_id = ' . $orderId;
        $order = 'id desc';

        $list = $this->find(compact('select', 'where', 'order'));
        $group = Hash::combine($list, '{n}.cart.id', '{n}.cart.target_id', '{n}.cart.target_type');

        foreach ($group as $type => $tmp) {
            $ref = $this->target($tmp, $type);
            foreach ($tmp as $id => $e) {
               $list[$id]['cart']['target'] = isset($ref[$e]) ? $ref[$e] : [];
            }
        }
        return $list;
    }

    public function target($list = [], $type = 1) {
        $model = $this->mapModel($type);
        $id = implode(',', $list);
        $select = 'id, title, file_id, seo_id';
        $where = 'id IN (' . $id . ')';
        $order = 'id desc';
        $result = $model->find(compact('select', 'where', 'order'));
        $alias = $model->alias();

        return Hash::combine($result, '{n}.' . $alias . '.id', '{n}.' . $alias);
    }

    private function mapModel($type = 1) {
        switch ($type) {
            case 1:
                $model = 'product';
                break;
            case 2:
                $model = 'post';
                break;
        }
        $model = App::load($model, 'model');
        $model->category(App::category()->flat($model->alias()));
        return $model;
    }
}