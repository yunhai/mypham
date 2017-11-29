<?php

use Mp\Model\Cart;

class CartModel extends Cart
{
    use \Mp\Lib\Traits\Extension;

    public function __construct()
    {
        parent::__construct();
        $this->extension();
    }
    
    public function extension()
    {
        $this->loadExtension(new \Mp\Model\Extension());
        $this->virtualField([
            'string_1' => 'title',
            'string_2' => 'property_text',
            'string_3' => 'property_detail_text',
        ]);
    }
}
