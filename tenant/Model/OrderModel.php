<?php

use Mp\Model\Order;

use Mp\App;

class OrderModel extends Order
{
    use \Mp\Lib\Traits\Extension;

    public function __construct()
    {
        parent::__construct();
        $this->extension();
    }

    public function attactCart()
    {
        $this->cart = App::load('cart', 'model');

        $this->cart->extension();
    }

    public function extension()
    {
        $this->loadExtension(new \Mp\Model\Extension());
        $this->virtualField([
            'string_1' => 'shipping',
            'string_2' => 'delivery_day',
        ]);
    }
}
