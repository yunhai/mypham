<?php

namespace Mp\Controller\Frontend;

use Mp\App;
use Mp\Lib\Session;
use Mp\Core\Controller\Frontend;

class Order extends Frontend {

    public function __construct($model = 'order', $table = 'order', $alias = 'order', $template = 'order') {
        parent::__construct($model, $table, $alias, $template);
        $this->model()->cart = App::load('cart', 'model');
    }

    public function navigator() {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'review':
                $this->review();
                break;
            case 'checkout':
                $this->checkout();
                break;
            default :
                $this->detail();
                break;
        }
    }

    public function review() {
        $this->render('review');
    }

    public function checkout() {
        $this->render('checkout');
    }

    protected function save($cart = []) {
        $total = $cart['total']['total'];
        $order = [
            'user_id' => App::load('login')->userId(),
            'receipt' => json_encode($this->receipt(), true),
            'sub_total' => $total,
            'total' => $total,
            'status' => 1
        ];
        $flag = $this->model()->save($order);
        if (!$flag) {
            return false;
        }

        $detail = $cart['detail'];
        $orderId = $this->model()->lastInsertId();

        $data = [];
        foreach ($detail as $id => $item) {
            $sub_total = $item['price'] * $item['price'];
            $data = [
                'order_id' => $orderId,
                'target_id' => $item['id'],
                'target_model' => $item['model'],
                'price' => $item['price'],
                'quantity' => $item['amount'],
                'sub_total' => $item['sub_total'],
                'total' => $item['sub_total']
            ];
        }

        $this->model()->cart->saveMany($data);

        $code = 'ORD' . (1000 + $orderId);
        $this->model()->modify(compact('code'), 'id = ' . $orderId);

        return true;
    }

}