<?php

use Mp\Model\Extension;

class ProductRatingModel extends Extension {

    public function field() {
        return [
            'string_1' => 'price',
            'string_2' => 'quantity',
            'string_3' => 'shipping',
            'string_5' => 'status',
            'text_1' => [
                'content',
                'fullname',
                'email'
            ]
        ];
    }

    public function init($fields = []) {
        $this->virtualField($fields);
    }
}