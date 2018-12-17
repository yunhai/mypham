<?php

use Mp\Model\Extension;

class ProductRatingModel extends Extension {

    public function field() {
        return [
            'string_1' => 'rating',
            'string_5' => 'status',
            'text_1' => [
                'title',
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