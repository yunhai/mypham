<?php

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Controller\Backend\Order;

class OrderController extends Order {

    protected function makeHandle($id, $status = 0) {
        $target = $this->target($id);
        $oldStatus = $target['status'];

        $fields = [
            'status' => $status
        ];

        $condition = 'id = ' . $id ;
        $this->model()->modify($fields, $condition);

        if (($oldStatus == 2 OR $status == 2) && $oldStatus != $status) {
            $operator = '+';
            if ($oldStatus == 2) {
                $operator = '-';
            }

            $oldStatus = $this->updateUserBalance($target, $operator);
        }

        return true;
    }

    protected function target($id) {
        $option = [
            'select' => 'id, status, user_id',
            'where' => 'id=' . $id
        ];

        $target = $this->model()->find($option, 'first');
        return current($target);
    }

    protected function updateUserBalance($order, $operator = '') {
        if ($operator) {
            $this->model()->attactCart();
            $cart = $this->model()->cart($order['id']);

            $targetId = Hash::combine($cart, '{n}.target_id', '{n}.target_id');

            $model = App::load('product', 'model');
            $model->extend($model->field());

            $alias = $model->alias();

            $option = [
                'select' => 'id',
                'where' => 'id IN (' . implode(',', $targetId) . ')'
            ];
            $tmp = $model->find($option);

            $delta = 0;
            foreach ($tmp as $id => $item) {
                $delta += empty($item[$alias]['point']) ? 0 : $item[$alias]['point'];
            }

            $model = App::load('user', 'model');
            $model->extension(['string_3' => 'balance']);

            $update = [
                'id' => $order['user_id'],
                'balance' => 'exp.string_3' . $operator . $delta
            ];

            $model->save($update);

            return true;
        }
    }
}
