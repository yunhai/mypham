<?php

namespace Mp\Model;

use Mp\App;
use Mp\Core\Model\Model;
use Mp\Lib\Utility\Hash;

class Order extends Model {

    public function __construct($table = 'order', $alias = 'order') {
        parent::__construct($table, $alias);
    }

    public function attactCart() {
        $this->cart = new Cart();
    }

    public function baseCondition() {
        $request = App::mp('request');
        $branch = $request->branch();

        $userId = App::mp('login')->userId() ?? 0;
        if ($branch == 1) {
            return 'user_id = ' . $userId . ' AND ' . parent::baseConditionWithAppId();
        }

        return parent::baseConditionWithAppId();
    }

    public function beforeSave(&$data = []) {
        parent::beforeSave($data);
        $data['app_id'] = App::mp('login')->targetId();
    }

    public function cart($orderId = 0) {
        $tmp = $this->cart->orderDetail($orderId);
        return Hash::combine($tmp, '{n}.cart.id', '{n}.cart');
    }
}
