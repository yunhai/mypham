<?php

use Mp\Model\Extension;

class ProductFaqModel extends Extension {

    public function field() {
        return [
            'string_1' => 'fullname',
            'string_2' => 'email',
            'string_3' => 'category',
            'string_4' => 'private',
            'string_5' => 'status',
            'text_1' => [
                'question',
                'answer'
            ]
        ];
    }

    public function init($fields = []) {
        $this->virtualField($fields);
    }
}