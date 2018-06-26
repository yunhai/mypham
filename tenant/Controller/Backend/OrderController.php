<?php

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Controller\Backend\Order;

class OrderController extends Order
{
    protected function makeHandle($id, $status = 0)
    {
        $target = $this->getDetail($id);
        $oldStatus = $target['order']['status'];

        if (($oldStatus == 2 or $status == 2) && $oldStatus != $status) {
            $positive = ($status === 2);
            $flag = $this->updateInventory($target['detail'], $positive);
            if (!$flag) {
                $this->flash('edit', 'Không đủ hàng trong kho.', 'error');
                return false;
            }
            $this->updateUserBalance($target['order'], $positive);
        }

        $fields = [
            'status' => $status
        ];

        $condition = 'id = ' . $id;
        $this->model()->modify($fields, $condition);

        return true;
    }

    protected function updateInventory($cart_list, $positive = true)
    {
        $model = App::load('product', 'model');

        foreach ($cart_list as $id => $item) {
            $product_id = $item['target_id'];
            $property_id = $item['property_id'];
            $amount = $item['quantity'];
            $flag = $model->updateInventory($product_id, $amount, $property_id, $positive);
            if (!$flag) {
                return false;
            }
            break;
        }
        return true;
    }

    protected function updateUserBalance($order, $position = true)
    {
        if (empty($order['user_id'])) {
            return true;
        }

        $model = App::load('user', 'model');
        $model->extension(['string_3' => 'balance']);
        $user = $model->findById($order['user_id']);
        $user = array_shift($user);

        $point_rate = 500000;
        $total = $order['total'];
        $point = intval($order['total'] / $point_rate);

        $balance = empty($user['balance']) ? 0 : $user['balance'];

        if ($position) {
            $balance += $point;
        } else {
            $balance -= $point;
        }
        $user['balance'] = ($balance < 0) ? 0 : $balance;

        $model->save($user);

        return true;
    }
}
