<?php

use Mp\Model\Cart;

class CartModel extends Cart {
    use \Mp\Lib\Traits\Extension;

    public function extension() {
        $this->loadExtension(new \Mp\Model\Extension());
        $this->virtualField([
            'string_1' => 'option',
        ]);
    }
}