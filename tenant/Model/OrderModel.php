<?php

use Mp\Model\Order;

use Mp\App;
class OrderModel extends Order {

    public function attactCart() {
        $this->cart = App::load('cart', 'model');

        $this->cart->extension();
    }
}